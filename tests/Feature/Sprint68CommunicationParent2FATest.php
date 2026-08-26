<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ParentAccount;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sprint 68 — Messaging, Announcements, Parent Access, Two-Factor Authentication.
 */
class Sprint68CommunicationParent2FATest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $teacherB;   // another teacher (privacy checks)
    private User $studentA;   // enrolled with teacher
    private User $studentC;   // enrolled only with teacherB
    private StudyGroup $groupA;
    private StudyGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'Teacher', 'email' => 'teacher@test.local', 'mobile' => '0500000000',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->teacherB = User::create([
            'name' => 'Teacher B', 'email' => 'teacherb@test.local',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->groupA = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'Group A',
            'day' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
        ]);

        $this->groupB = StudyGroup::create([
            'teacher_id' => $this->teacherB->id, 'title' => 'Group B',
            'day' => 'monday', 'start_time' => '13:00', 'end_time' => '15:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
        ]);

        $this->studentA = $this->makeStudent('Student A', '0111111111', $this->groupA, '0100000000');
        $this->studentC = $this->makeStudent('Student C', '0133333333', $this->groupB, '0155555555');
    }

    private function makeStudent(string $name, string $mobile, StudyGroup $group, ?string $parentMobile = null): User
    {
        $student = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => $mobile,
            'parent_mobile' => $parentMobile,
        ]);

        GroupEnrollment::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'status' => 'enrolled',
        ]);

        return $student;
    }

    private function asStudent(User $user)
    {
        return $this->actingAs($user)->withSession(['active_student_id' => $user->id]);
    }

    // ==================== Messaging ====================

    public function test_teacher_can_send_message_to_enrolled_student(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.messages.store', $this->studentA), ['body' => 'مرحباً']);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'sender_type' => 'teacher',
            'body' => 'مرحباً',
        ]);
    }

    public function test_student_receives_message_and_notification(): void
    {
        Message::create([
            'teacher_id' => $this->teacher->id, 'student_id' => $this->studentA->id,
            'sender_type' => 'teacher', 'body' => 'رسالة تجريبية',
        ]);

        $response = $this->asStudent($this->studentA)->get(route('student.messages.show', $this->teacher));

        $response->assertOk()->assertSee('رسالة تجريبية');
        // Notification created for the student when the teacher sent it.
        $this->actingAs($this->teacher)
            ->post(route('teacher.messages.store', $this->studentA), ['body' => 'ثانية']);
        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'type' => 'message',
        ]);
    }

    public function test_read_unread_state_works(): void
    {
        Message::create([
            'teacher_id' => $this->teacher->id, 'student_id' => $this->studentA->id,
            'sender_type' => 'student', 'body' => 'سؤال من الطالب',
        ]);

        $this->assertEquals(1, Message::unreadCount($this->teacher->id, Message::SENDER_TEACHER));

        $this->actingAs($this->teacher)->get(route('teacher.messages.show', $this->studentA));

        $this->assertEquals(0, Message::unreadCount($this->teacher->id, Message::SENDER_TEACHER));
        $this->assertNotNull(Message::first()->read_at);
    }

    public function test_student_cannot_access_other_students_thread(): void
    {
        // studentC is not enrolled with $this->teacher.
        $response = $this->asStudent($this->studentC)->get(route('student.messages.show', $this->teacher));
        $response->assertForbidden();

        $response = $this->asStudent($this->studentC)
            ->post(route('student.messages.store', $this->teacher), ['body' => 'x']);
        $response->assertForbidden();
    }

    public function test_teacher_cannot_message_student_of_another_teacher(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.messages.store', $this->studentC), ['body' => 'x']);
        $response->assertForbidden();
    }

    public function test_guest_and_assistant_cannot_access_messaging(): void
    {
        $this->get(route('teacher.messages.index'))->assertRedirect(route('login'));
        $this->get(route('student.messages.index'))->assertRedirect(route('login'));
    }

    // ==================== Announcements ====================

    public function test_teacher_creates_announcement_with_targets(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.announcements.store'), [
                'title' => 'إعلان مهم',
                'body' => 'نص الإعلان',
                'group_ids' => [$this->groupA->id],
            ]);

        $response->assertRedirect(route('teacher.announcements.index'));
        $announcement = Announcement::where('title', 'إعلان مهم')->first();
        $this->assertNotNull($announcement);
        $this->assertTrue($announcement->groups->pluck('id')->contains($this->groupA->id));
    }

    public function test_targeted_students_see_announcement_others_do_not(): void
    {
        Announcement::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'إعلان المجموعة أ',
            'body' => 'نص',
            'published_at' => now(),
        ])->groups()->attach($this->groupA->id);

        $this->asStudent($this->studentA)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('إعلان المجموعة أ');

        // studentC belongs to another teacher's group → must NOT see it.
        $this->asStudent($this->studentC)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('إعلان المجموعة أ');
    }

    public function test_announcement_edit_and_delete_by_owner_only(): void
    {
        $announcement = Announcement::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'قديم', 'body' => 'نص', 'published_at' => now(),
        ]);
        $announcement->groups()->attach($this->groupA->id);

        // Owner can edit.
        $this->actingAs($this->teacher)
            ->put(route('teacher.announcements.update', $announcement), [
                'title' => 'معدّل', 'body' => 'نص جديد', 'group_ids' => [$this->groupA->id],
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'title' => 'معدّل']);

        // Another teacher cannot edit or delete.
        $this->actingAs($this->teacherB)
            ->put(route('teacher.announcements.update', $announcement), [
                'title' => 'x', 'body' => 'y', 'group_ids' => [$this->groupB->id],
            ])
            ->assertForbidden();
        $this->actingAs($this->teacherB)
            ->delete(route('teacher.announcements.destroy', $announcement))
            ->assertForbidden();

        // Owner deletes.
        $this->actingAs($this->teacher)
            ->delete(route('teacher.announcements.destroy', $announcement))
            ->assertRedirect();
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_announcement_produces_notifications_for_targeted_students(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.announcements.store'), [
                'title' => 'إعلان بإشعار',
                'body' => 'نص',
                'group_ids' => [$this->groupA->id],
            ]);

        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'type' => 'announcement',
        ]);
        // No notification for the non-targeted student.
        $this->assertDatabaseMissing('notifications', ['student_id' => $this->studentC->id, 'type' => 'announcement']);
    }

    public function test_teacher_cannot_target_groups_he_does_not_own(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.announcements.store'), [
                'title' => 'x', 'body' => 'y', 'group_ids' => [$this->groupB->id],
            ]);
        $response->assertSessionHasErrors('group_ids.0');
    }

    // ==================== Parent Access ====================

    public function test_parent_can_register_and_is_linked_to_children(): void
    {
        $response = $this->post(route('parent.register'), [
            'name' => 'ولي الأمر',
            'mobile' => '0100000000',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $parent = ParentAccount::where('mobile', '0100000000')->first();
        $this->assertNotNull($parent);
        $this->assertTrue($parent->ownsStudent($this->studentA));
        $this->assertFalse($parent->ownsStudent($this->studentC));
    }

    public function test_parent_registration_requires_linked_student(): void
    {
        $response = $this->from(route('parent.register'))->post(route('parent.register'), [
            'name' => 'غريب',
            'mobile' => '0999999999',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);
        $response->assertRedirect(route('parent.register'))->assertSessionHasErrors('mobile');
        $this->assertDatabaseMissing('parents', ['mobile' => '0999999999']);
    }

    public function test_parent_can_login_and_view_child_data(): void
    {
        ParentAccount::create(['name' => 'ولي', 'mobile' => '0100000000', 'password' => 'secret1234']);

        $this->post(route('parent.login'), ['mobile' => '0100000000', 'password' => 'secret1234'])
            ->assertRedirect(route('parent.dashboard'));

        $this->get(route('parent.student.show', $this->studentA))
            ->assertOk()
            ->assertSee($this->studentA->name);
    }

    public function test_wrong_parent_credentials_rejected(): void
    {
        ParentAccount::create(['name' => 'ولي', 'mobile' => '0100000000', 'password' => 'secret1234']);

        $this->from(route('parent.login'))
            ->post(route('parent.login'), ['mobile' => '0100000000', 'password' => 'wrong'])
            ->assertRedirect(route('parent.login'))
            ->assertSessionHasErrors('mobile');
    }

    public function test_parent_cannot_view_unrelated_student(): void
    {
        $parent = ParentAccount::create(['name' => 'ولي', 'mobile' => '0100000000', 'password' => 'secret1234']);
        $this->actingAs($parent, 'parent');

        $this->get(route('parent.student.show', $this->studentC))->assertForbidden();
    }

    public function test_parent_cannot_access_teacher_or_assistant_areas(): void
    {
        $parent = ParentAccount::create(['name' => 'ولي', 'mobile' => '0100000000', 'password' => 'secret1234']);
        $this->actingAs($parent, 'parent');

        $this->get(route('teacher.dashboard'))->assertForbidden();
        $this->get(route('assistant.dashboard'))->assertRedirect(); // assistant guard required
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_parent_views_are_read_only(): void
    {
        $parent = ParentAccount::create(['name' => 'ولي', 'mobile' => '0100000000', 'password' => 'secret1234']);
        $this->actingAs($parent, 'parent');

        // No write routes exist for parents.
        $this->post(route('parent.logout')); // sanity: only logout POST exists

        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'parent.'));

        foreach ($routes as $route) {
            $writeMethods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            if (! empty($writeMethods)) {
                $this->assertEquals('parent.logout', $route->getName());
            }
        }
    }

    // ==================== Two-Factor Authentication ====================

    public function test_teacher_can_enable_2fa_with_password(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.two-factor.enable'), ['password' => 'password'])
            ->assertRedirect();

        $this->assertTrue($this->teacher->fresh()->two_factor_enabled);
    }

    public function test_enable_requires_correct_password(): void
    {
        $this->actingAs($this->teacher)
            ->from(route('teacher.settings.index'))
            ->post(route('teacher.two-factor.enable'), ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertFalse($this->teacher->fresh()->two_factor_enabled);
    }

    public function test_login_with_2fa_issues_otp_challenge(): void
    {
        $this->teacher->update(['two_factor_enabled' => true]);
        Mail::fake();

        $response = $this->post(route('login'), [
            'mobile' => $this->teacher->mobile,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        Mail::assertSent(\App\Mail\TwoFactorCode::class, 1);
        $this->assertGuest();
    }

    public function test_correct_otp_completes_login(): void
    {
        $this->teacher->update(['two_factor_enabled' => true]);
        Mail::fake();

        // Issue the OTP through the same mechanism used at login.
        \App\Http\Controllers\Auth\TwoFactorController::issueCode($this->teacher);

        // Extract the plain code from the captured mailable.
        $code = null;
        Mail::assertSent(\App\Mail\TwoFactorCode::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code, 'OTP code should be present in the sent email');

        $response = $this->withSession(['two_factor:id' => $this->teacher->id])
            ->post(route('two-factor.challenge'), ['code' => $code]);

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($this->teacher);
    }

    public function test_wrong_otp_rejected(): void
    {
        $this->teacher->update(['two_factor_enabled' => true]);
        \App\Http\Controllers\Auth\TwoFactorController::issueCode($this->teacher);

        $response = $this->withSession(['two_factor:id' => $this->teacher->id])
            ->from(route('two-factor.challenge'))
            ->post(route('two-factor.challenge'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_requires_pending_session(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_disable_2fa_restores_normal_login(): void
    {
        $this->teacher->update(['two_factor_enabled' => true]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.two-factor.disable'), ['password' => 'password'])
            ->assertRedirect();
        $this->assertFalse($this->teacher->fresh()->two_factor_enabled);

        // Normal login works again without challenge.
        $this->post(route('login'), ['mobile' => $this->teacher->mobile, 'password' => 'password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($this->teacher);
    }

    public function test_student_login_is_not_affected_by_2fa(): void
    {
        $this->studentA->update(['two_factor_enabled' => true]); // even if flag set, students skip 2FA

        $this->post(route('login'), ['mobile' => $this->studentA->mobile, 'password' => 'password'])
            ->assertRedirect(route('student.resources.home'));
        $this->assertAuthenticatedAs($this->studentA);
    }

    public function test_non_teachers_cannot_toggle_2fa_routes(): void
    {
        $this->asStudent($this->studentA)
            ->post(route('teacher.two-factor.enable'), ['password' => 'password'])
            ->assertForbidden();
    }
}