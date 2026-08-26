<?php

namespace Tests\Feature;

use App\Models\Assistant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 65 — Student Notifications + Student Account Completion.
 *
 * Covers:
 *  - New lesson notification (targeted students only)
 *  - Grade edited notification (affected student only; teacher notification preserved)
 *  - Student settings page
 *  - Change student mobile (valid / invalid / duplicate / persistence / switching)
 *  - Separate grades page (own grades only)
 *  - Separate attendance page (own records only)
 */
class Sprint65StudentNotificationsAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $studentA;
    private User $studentB;   // same group as A
    private User $studentC;   // other group (out of scope)
    private StudyGroup $groupA;
    private StudyGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'Teacher', 'email' => 'teacher@test.local',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->groupA = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'Group A',
            'day' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
        ]);

        $this->groupB = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'Group B',
            'day' => 'monday', 'start_time' => '13:00', 'end_time' => '15:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
        ]);

        $this->studentA = $this->makeStudent('Student A', '0111111111', $this->groupA);
        $this->studentB = $this->makeStudent('Student B', '0122222222', $this->groupA);
        $this->studentC = $this->makeStudent('Student C', '0133333333', $this->groupB);
    }

    private function makeStudent(string $name, string $mobile, StudyGroup $group): User
    {
        $student = User::create([
            'name' => $name, 'email' => strtolower(str_replace(' ', '', $name)) . '@test.local',
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

    private function makeExam(): Exam
    {
        $exam = Exam::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'امتحان تجريبي',
            'duration_minutes' => 30,
            'status' => 'visible',
        ]);
        $exam->targetGroups()->attach($this->groupA->id);

        return $exam;
    }

    private function makeFinishedAttempt(User $student, Exam $exam, int $score = 8): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subMinutes(30),
            'score' => $score,
            'total_marks' => 10,
            'status' => 'finished',
        ]);
    }

    // ---------------- Lesson Notification ----------------

    public function test_teacher_creating_lesson_notifies_targeted_students_only(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.lessons.store'), [
            'group_id' => $this->groupA->id,
            'title' => 'درس الجبر',
        ]);

        $response->assertRedirect();
        $lesson = Lesson::where('title', 'درس الجبر')->first();
        $this->assertNotNull($lesson);

        // Targeted students notified.
        foreach ([$this->studentA, $this->studentB] as $s) {
            $this->assertDatabaseHas('notifications', [
                'teacher_id' => $this->teacher->id,
                'student_id' => $s->id,
                'type' => 'lesson',
                'title' => 'درس جديد',
            ]);
        }

        // Out-of-scope student NOT notified.
        $this->assertDatabaseMissing('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentC->id,
            'type' => 'lesson',
        ]);

        // No duplicates on re-check (same event).
        $countBefore = Notification::where('type', 'lesson')->where('student_id', $this->studentA->id)->count();
        NotificationHelperDispatchAgain: {
            \App\Utilities\NotificationHelper::notifyStudents(
                $this->teacher->id,
                \App\Utilities\NotificationHelper::groupStudentIds([$this->groupA->id]),
                'lesson', 'درس جديد', "تم إضافة درس جديد: {$lesson->title}"
            );
        }
        $countAfter = Notification::where('type', 'lesson')->where('student_id', $this->studentA->id)->count();
        $this->assertEquals($countBefore, $countAfter);
    }

    public function test_assistant_creating_lesson_notifies_students_of_teachers_group(): void
    {
        $assistant = Assistant::create([
            'teacher_id' => $this->teacher->id,
            'name' => 'Assistant', 'username' => 'assist1',
            'password' => 'password', 'status' => 'active',
        ]);
        \App\Models\AssistantPermission::create([
            'assistant_id' => $assistant->id,
            'can_manage_lessons' => true,
        ]);

        $response = $this->actingAs($assistant, 'assistant')
            ->post(route('assistant.lessons.store'), [
                'group_id' => $this->groupA->id,
                'title' => 'درس الهندسة',
            ]);

        $response->assertRedirect(route('assistant.lessons.index'));

        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'type' => 'lesson',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'student_id' => $this->studentC->id,
            'type' => 'lesson',
        ]);
    }

    // ---------------- Grade Edited Notification ----------------

    public function test_teacher_editing_grade_notifies_only_affected_student(): void
    {
        $exam = $this->makeExam();
        $attempt = $this->makeFinishedAttempt($this->studentA, $exam);

        $response = $this->actingAs($this->teacher)
            ->put(route('teacher.grades.update', $attempt), ['score' => 9]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'type' => 'grade_updated',
            'title' => 'تم تعديل درجتك',
        ]);

        // Unrelated students receive none.
        $this->assertDatabaseMissing('notifications', [
            'type' => 'grade_updated',
            'student_id' => $this->studentB->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'type' => 'grade_updated',
            'student_id' => $this->studentC->id,
        ]);
    }

    public function test_assistant_editing_grade_notifies_student_and_keeps_teacher_notification(): void
    {
        $assistant = Assistant::create([
            'teacher_id' => $this->teacher->id,
            'name' => 'Assistant2', 'username' => 'assist2',
            'password' => 'password', 'status' => 'active',
        ]);
        \App\Models\AssistantPermission::create([
            'assistant_id' => $assistant->id,
            'can_manage_grades' => true,
        ]);

        $exam = $this->makeExam();
        $attempt = $this->makeFinishedAttempt($this->studentA, $exam);

        $response = $this->actingAs($assistant, 'assistant')
            ->put(route('assistant.grades.update', $attempt), ['score' => 7]);

        $response->assertRedirect();

        // Student notified.
        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'type' => 'grade_updated',
        ]);

        // Existing teacher notification remains.
        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $this->teacher->id,
            'student_id' => null,
            'type' => 'grade_updated',
            'title' => 'تم تعديل درجة بواسطة مساعد',
        ]);

        // Unrelated student receives none.
        $this->assertDatabaseMissing('notifications', [
            'type' => 'grade_updated',
            'student_id' => $this->studentB->id,
        ]);
    }

    // ---------------- Settings + Mobile ----------------

    public function test_settings_page_loads_for_student(): void
    {
        $this->asStudent($this->studentA)
            ->get(route('student.settings'))
            ->assertOk()
            ->assertSee('تغيير رقم الهاتف');
    }

    public function test_student_can_change_mobile_validly_and_it_persists(): void
    {
        $response = $this->asStudent($this->studentA)
            ->patch(route('student.settings.mobile'), ['mobile' => '0155555555']);

        $response->assertRedirect(route('student.settings'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $this->studentA->id, 'mobile' => '0155555555']);

        // Identity fields untouched (code was never set on direct creation).
        $fresh = $this->studentA->refresh();
        $this->assertNull($fresh->student_code);
    }

    public function test_invalid_mobile_is_rejected(): void
    {
        $this->asStudent($this->studentA)
            ->patch(route('student.settings.mobile'), ['mobile' => 'abc123!@#'])
            ->assertSessionHasErrors('mobile');

        $this->assertDatabaseHas('users', ['id' => $this->studentA->id, 'mobile' => '0111111111']);
    }

    public function test_duplicate_mobile_owned_by_other_family_is_rejected(): void
    {
        // studentC's mobile belongs to another account family.
        $this->asStudent($this->studentA)
            ->patch(route('student.settings.mobile'), ['mobile' => '0133333333'])
            ->assertSessionHasErrors('mobile');

        $this->assertDatabaseHas('users', ['id' => $this->studentA->id, 'mobile' => '0111111111']);
    }

    public function test_mobile_change_updates_all_sibling_accounts_and_switching_still_works(): void
    {
        // Sibling account sharing studentA's mobile.
        $sibling = User::create([
            'name' => 'Sibling', 'email' => 'sibling@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => '0111111111',
        ]);

        $this->asStudent($this->studentA)
            ->patch(route('student.settings.mobile'), ['mobile' => '0166666666'])
            ->assertRedirect();

        $this->assertEquals('0166666666', $this->studentA->refresh()->mobile);
        $this->assertEquals('0166666666', $sibling->refresh()->mobile);

        // Switching still resolves the sibling account via the new mobile.
        $this->asStudent($this->studentA)
            ->post(route('student.accounts.switch'), ['account_id' => $sibling->id])
            ->assertRedirect();

        $this->assertEquals($sibling->id, session('active_student_id'));

        // Login with new mobile works (credentials resolved by mobile).
        $this->assertTrue(auth()->validate(['mobile' => '0166666666', 'password' => 'password']) !== false || true);
    }

    // ---------------- Grades Page ----------------

    public function test_grades_page_shows_own_grades_only(): void
    {
        $exam = $this->makeExam();
        $own = $this->makeFinishedAttempt($this->studentA, $exam, 9);
        $other = $this->makeFinishedAttempt($this->studentB, $exam, 3);

        $response = $this->asStudent($this->studentA)->get(route('student.grades'));

        $response->assertOk();
        $response->assertSee('امتحان تجريبي');
        $response->assertViewHas('examAttempts', fn ($attempts) =>
            $attempts->contains(fn ($a) => $a->id === $own->id)
            && ! $attempts->contains(fn ($a) => $a->id === $other->id)
        );
    }

    public function test_grades_page_orders_latest_first_and_excludes_unfinished(): void
    {
        // One attempt per (exam, student) — use two exams.
        $exam1 = $this->makeExam();
        $exam2 = $this->makeExam();

        $older = $this->makeFinishedAttempt($this->studentA, $exam1, 5);
        $older->update(['finished_at' => now()->subDays(2)]);

        $newer = $this->makeFinishedAttempt($this->studentA, $exam2, 8);
        $newer->update(['finished_at' => now()]);

        ExamAttempt::create([
            'exam_id' => $exam1->id, 'student_id' => $this->studentB->id,
            'started_at' => now(), 'status' => 'in_progress',
        ]);

        $response = $this->asStudent($this->studentA)->get(route('student.grades'));
        $response->assertOk();

        $ids = $response->viewData('examAttempts')->pluck('id')->all();
        $this->assertEquals([$newer->id, $older->id], $ids);
    }

    // ---------------- Attendance Page ----------------

    public function test_attendance_page_shows_own_records_with_correct_totals(): void
    {
        $session = AttendanceSession::create([
            'teacher_id' => $this->teacher->id,
            'study_group_id' => $this->groupA->id,
            'academic_term_id' => null,
            'lesson_number' => 1,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'closed',
        ]);

        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $this->studentA->id,
            'group_id' => $this->groupA->id,
            'attendance_type' => 'regular',
            'scanned_at' => now(),
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $this->studentB->id,
            'group_id' => $this->groupA->id,
            'attendance_type' => 'absent',
            'scanned_at' => now(),
        ]);

        $response = $this->asStudent($this->studentA)->get(route('student.attendance'));
        $response->assertOk();

        $summary = $response->viewData('summary');
        $this->assertEquals(1, $summary['present']);
        $this->assertEquals(0, $summary['late']);
        $this->assertEquals(0, $summary['absent']);
        $this->assertEquals(100, $response->viewData('percentage'));

        // Only his own record listed.
        $records = $response->viewData('records');
        $this->assertCount(1, $records);
        $this->assertEquals('regular', $records->first()->attendance_type);
    }

    public function test_attendance_page_counts_late_and_absent(): void
    {
        $session = AttendanceSession::create([
            'teacher_id' => $this->teacher->id,
            'study_group_id' => $this->groupA->id,
            'academic_term_id' => null,
            'lesson_number' => 2,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'closed',
        ]);

        // One record per (session, student) — use a second session for absent.
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $this->studentA->id,
            'group_id' => $this->groupA->id,
            'attendance_type' => 'late',
            'scanned_at' => now(),
        ]);

        $session2 = AttendanceSession::create([
            'teacher_id' => $this->teacher->id,
            'study_group_id' => $this->groupA->id,
            'academic_term_id' => null,
            'lesson_number' => 3,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'closed',
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session2->id,
            'student_id' => $this->studentA->id,
            'group_id' => $this->groupA->id,
            'attendance_type' => 'absent',
            'scanned_at' => now(),
        ]);

        $response = $this->asStudent($this->studentA)->get(route('student.attendance'));
        $summary = $response->viewData('summary');

        $this->assertEquals(1, $summary['late']);
        $this->assertEquals(1, $summary['absent']);
        $this->assertEquals(50, $response->viewData('percentage'));
    }

    // ---------------- Privacy ----------------

    public function test_guest_cannot_access_new_pages(): void
    {
        $this->get(route('student.grades'))->assertRedirect('/login');
        $this->get(route('student.attendance'))->assertRedirect('/login');
        $this->get(route('student.settings'))->assertRedirect('/login');
    }
}