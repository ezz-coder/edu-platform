<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\ResourceTarget;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 69 — Student Dashboard content, Groups stage/gender filters,
 * Announcements targeting, privacy & multi-account compatibility.
 */
class Sprint69StudentDashboardGroupsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $teacherB;
    private User $studentA;   // enrolled in groupA (teacher)
    private User $studentC;   // enrolled only with teacherB (privacy checks)
    private StudyGroup $groupA;
    private StudyGroup $groupA2;
    private StudyGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'Teacher', 'email' => 'teacher@test.local', 'mobile' => '0500000000',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->teacherB = User::create([
            'name' => 'Teacher B', 'email' => 'teacherb@test.local', 'mobile' => '0500000001',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->groupA = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'Group A Boys G7',
            'day' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
        ]);

        $this->groupA2 = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'Group A2 Girls G8',
            'day' => 'monday', 'start_time' => '13:00', 'end_time' => '15:00',
            'gender_type' => 'girls', 'grade_level' => 'grade8', 'status' => 'active',
        ]);

        $this->groupB = StudyGroup::create([
            'teacher_id' => $this->teacherB->id, 'title' => 'Group B Boys G7',
            'day' => 'tuesday', 'start_time' => '16:00', 'end_time' => '18:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
        ]);

        $this->studentA = $this->makeStudent('Student A', '0111111111', $this->groupA);
        $this->studentC = $this->makeStudent('Student C', '0133333333', $this->groupB);
    }

    private function makeStudent(string $name, string $mobile, StudyGroup $group): User
    {
        $student = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => $mobile,
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

    // ==================== Dashboard ====================

    public function test_guest_is_rejected_from_dashboard(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect('/login');
    }

    public function test_authenticated_student_gets_dashboard_with_content(): void
    {
        Resource::create([
            'teacher_id' => $this->teacher->id, 'lesson_id' => null,
            'title' => 'مورد تجريبي', 'type' => 'pdf', 'file_path' => 'x.pdf',
            'status' => 'visible', 'publish_date' => now(),
        ]);
        ResourceTarget::create([
            'resource_id' => Resource::first()->id, 'scope' => 'all',
        ]);

        Notification::create([
            'teacher_id' => $this->teacher->id, 'student_id' => $this->studentA->id,
            'group_id' => $this->groupA->id, 'type' => 'system',
            'title' => 'إشعار غير مقروء', 'message' => 'نص الإشعار',
        ]);

        $response = $this->asStudent($this->studentA)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('مورد تجريبي')
            ->assertSee('إشعار غير مقروء')
            ->assertSee('الإشعارات غير المقروءة (1)');
    }

    public function test_dashboard_uses_active_account(): void
    {
        // Second account for the same student (same mobile), different group.
        $accountB = User::create([
            'name' => 'Student A B', 'email' => 'studentab@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => '0111111111',
        ]);
        GroupEnrollment::create([
            'student_id' => $accountB->id, 'group_id' => $this->groupA2->id, 'status' => 'enrolled',
        ]);

        $response = $this->actingAs($this->studentA)
            ->withSession(['active_student_id' => $accountB->id])
            ->get(route('student.dashboard'));

        $response->assertOk()->assertSee($accountB->name)->assertSee($this->groupA2->title);
    }

    public function test_announcement_visible_only_to_targeted_student(): void
    {
        Announcement::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'إعلان خاص بمجموعة A',
            'body' => 'محتوى الإعلان',
            'published_at' => now(),
        ])->groups()->attach($this->groupA->id);

        // Targeted student sees it on the dashboard.
        $this->asStudent($this->studentA)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('إعلان خاص بمجموعة A');

        // Unrelated student (other teacher) does NOT see it.
        $this->asStudent($this->studentC)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('إعلان خاص بمجموعة A');
    }

    public function test_unread_notifications_are_scoped_to_active_account(): void
    {
        // Sibling account of the same user gets its own notification.
        $sibling = User::create([
            'name' => 'Sibling', 'email' => 'sibling@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => '0111111111',
        ]);
        GroupEnrollment::create([
            'student_id' => $sibling->id, 'group_id' => $this->groupA2->id, 'status' => 'enrolled',
        ]);

        Notification::create([
            'teacher_id' => $this->teacher->id, 'student_id' => $sibling->id,
            'group_id' => $this->groupA2->id, 'type' => 'system',
            'title' => 'إشعار الأخ فقط', 'message' => null,
        ]);

        // Active account is studentA → must not see sibling's notification.
        $this->asStudent($this->studentA)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('إشعار الأخ فقط');
    }

    // ==================== Groups ====================

    public function test_groups_index_shows_only_active_groups(): void
    {
        StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'Archived Group',
            'day' => 'friday', 'start_time' => '08:00', 'end_time' => '09:00',
            'gender_type' => 'mixed', 'grade_level' => 'grade9', 'status' => 'archived',
        ]);

        $response = $this->asStudent($this->studentA)->get(route('student.groups.index'));

        $response->assertOk()
            ->assertSee('Group A Boys G7')
            ->assertSee('Group A2 Girls G8')
            ->assertDontSee('Archived Group');
    }

    public function test_groups_stage_filter(): void
    {
        $response = $this->asStudent($this->studentA)
            ->get(route('student.groups.index', ['stage' => 'grade8']));

        $response->assertOk()
            ->assertSee('Group A2 Girls G8')
            ->assertDontSee('Group A Boys G7');
    }

    public function test_groups_gender_filter(): void
    {
        $response = $this->asStudent($this->studentA)
            ->get(route('student.groups.index', ['gender' => 'girls']));

        $response->assertOk()
            ->assertSee('Group A2 Girls G8')
            ->assertDontSee('Group A Boys G7');
    }

    public function test_groups_combined_stage_and_gender_filter(): void
    {
        $response = $this->asStudent($this->studentA)
            ->get(route('student.groups.index', ['stage' => 'grade7', 'gender' => 'boys']));

        $response->assertOk()
            ->assertSee('Group A Boys G7')
            ->assertSee('Group B Boys G7')
            ->assertDontSee('Group A2 Girls G8');
    }
}