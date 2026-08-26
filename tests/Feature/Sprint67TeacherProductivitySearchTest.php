<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\Lesson;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sprint 67 — Teacher Productivity + Search:
 * Advanced Search, Bulk Create Lessons, Teacher Calendar,
 * Student Progress Tracking, Teacher Settings.
 */
class Sprint67TeacherProductivitySearchTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private StudyGroup $groupA;   // own group (sunday)
    private StudyGroup $groupB;   // own group (monday, different grade)
    private StudyGroup $otherGroup; // other teacher's group
    private User $studentA;
    private User $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'المدرس', 'email' => 'teacher@test.local',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->otherTeacher = User::create([
            'name' => 'مدرس آخر', 'email' => 'other@test.local',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->groupA = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'مجموعة الأحد',
            'day' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
            'max_students' => 30,
        ]);

        $this->groupB = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'مجموعة الاثنين',
            'day' => 'monday', 'start_time' => '13:00', 'end_time' => '15:00',
            'gender_type' => 'girls', 'grade_level' => 'grade8', 'status' => 'active',
            'max_students' => 30,
        ]);

        $this->otherGroup = StudyGroup::create([
            'teacher_id' => $this->otherTeacher->id, 'title' => 'مجموعة المدرس الآخر',
            'day' => 'tuesday', 'start_time' => '09:00', 'end_time' => '11:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
            'max_students' => 30,
        ]);

        $this->studentA = $this->makeStudent('طالب أول', '0111111111', $this->groupA);
        $this->studentB = $this->makeStudent('طالب ثانٍ', '0122222222', $this->groupB);
    }

    private function makeStudent(string $name, string $mobile, StudyGroup $group): User
    {
        $student = User::create([
            'name' => $name,
            'email' => 's_' . $mobile . '@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => $mobile, 'parent_mobile' => '0999999999',
        ]);

        GroupEnrollment::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'status' => 'enrolled',
        ]);

        return $student;
    }

    // ===================== 1. Advanced Search =====================

    public function test_search_by_name_returns_matching_students(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.index', ['search' => 'أول']));

        $response->assertOk();
        $response->assertSee('طالب أول');
        $response->assertDontSee('طالب ثانٍ');
    }

    public function test_search_with_no_results_shows_empty_state(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.index', ['search' => 'غير موجود نهائياً']));

        $response->assertOk();
        $response->assertSee('لا يوجد طلاب مطابقون');
    }

    public function test_advanced_filter_by_grade_level(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.index', ['grade_level' => 'grade7']));

        $response->assertOk();
        $response->assertSee('طالب أول');
        $response->assertDontSee('طالب ثانٍ');
    }

    public function test_advanced_filter_by_gender_type(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.index', ['gender_type' => 'girls']));

        $response->assertOk();
        $response->assertSee('طالب ثانٍ');
        $response->assertDontSee('طالب أول');
    }

    public function test_search_never_exposes_other_teachers_students(): void
    {
        $outsider = $this->makeStudent('طالب خارجي', '0155555555', $this->otherGroup);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.students.index', ['search' => 'خارجي']));

        $response->assertOk();
        $response->assertSee('لا يوجد طلاب مطابقون');
        $response->assertDontSee($outsider->student_code);
    }

    // ===================== 2. Bulk Create Lessons =====================

    public function test_bulk_create_page_opens(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.lessons.bulk.create'))
            ->assertOk()
            ->assertSee('إنشاء دروس متعددة');
    }

    public function test_valid_bulk_creation_preserves_display_order(): void
    {
        // Existing lesson with display_order 5.
        Lesson::create([
            'teacher_id' => $this->teacher->id, 'group_id' => $this->groupA->id,
            'title' => 'درس قديم', 'display_order' => 5, 'is_active' => true,
        ]);

        $response = $this->actingAs($this->teacher)->post(route('teacher.lessons.bulk.store'), [
            'group_id' => $this->groupA->id,
            'titles' => "الدرس الأول\nالدرس الثاني\nالدرس الثالث",
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('teacher.lessons.index'));
        $this->assertDatabaseHas('lessons', ['title' => 'الدرس الأول', 'display_order' => 6]);
        $this->assertDatabaseHas('lessons', ['title' => 'الدرس الثاني', 'display_order' => 7]);
        $this->assertDatabaseHas('lessons', ['title' => 'الدرس الثالث', 'display_order' => 8]);

        $lesson = Lesson::where('title', 'الدرس الأول')->first();
        $this->assertTrue((bool) $lesson->is_active);
        $this->assertEquals($this->teacher->id, $lesson->teacher_id);
    }

    public function test_bulk_rejects_duplicate_title_within_submission(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.lessons.bulk.store'), [
            'group_id' => $this->groupA->id,
            'titles' => "درس مكرر\nدرس مكرر",
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, Lesson::where('group_id', $this->groupA->id)->where('title', 'درس مكرر')->count());
    }

    public function test_bulk_rejects_conflict_with_existing_lesson(): void
    {
        Lesson::create([
            'teacher_id' => $this->teacher->id, 'group_id' => $this->groupA->id,
            'title' => 'موجود', 'display_order' => 1, 'is_active' => true,
        ]);

        $response = $this->actingAs($this->teacher)->post(route('teacher.lessons.bulk.store'), [
            'group_id' => $this->groupA->id,
            'titles' => "جديد\nموجود",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('lessons', ['title' => 'جديد']);
        $this->assertEquals(1, Lesson::where('title', 'موجود')->count()); // not duplicated
    }

    public function test_bulk_requires_at_least_one_title(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.lessons.bulk.store'), [
            'group_id' => $this->groupA->id,
            'titles' => "\n\n",
        ]);

        $response->assertSessionHasErrors('titles');
        $this->assertEquals(0, Lesson::count());
    }

    public function test_bulk_enforces_group_ownership(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.lessons.bulk.store'), [
            'group_id' => $this->otherGroup->id, // belongs to another teacher
            'titles' => "درس غير مصرح\n",
        ]);

        $response->assertNotFound(); // authorizeGroup uses firstOrFail
        $this->assertDatabaseMissing('lessons', ['title' => 'درس غير مصرح']);
    }

    public function test_student_cannot_access_bulk_create(): void
    {
        $this->actingAs($this->studentA)
            ->get(route('teacher.lessons.bulk.create'))
            ->assertForbidden();

        $this->actingAs($this->studentA)
            ->post(route('teacher.lessons.bulk.store'), ['group_id' => $this->groupA->id])
            ->assertForbidden();
    }

    // ===================== 3. Teacher Calendar =====================

    public function test_calendar_page_opens_and_shows_group_schedule(): void
    {
        $month = now()->format('Y-m');

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.calendar.index', ['month' => $month]));

        $response->assertOk();
        $response->assertSee('التقويم');
        // Recurring weekly schedule event for sunday group.
        $response->assertSee('حصة مجموعة الأحد');
    }

    public function test_calendar_shows_attendance_sessions_on_correct_dates(): void
    {
        $date = now()->startOfMonth()->addDays(2)->toDateString();

        AttendanceSession::create([
            'study_group_id' => $this->groupA->id,
            'teacher_id' => $this->teacher->id,
            'attendance_date' => $date,
            'started_at' => $date . ' 10:00:00',
            'status' => 'closed',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.calendar.index', ['month' => substr($date, 0, 7)]));

        $response->assertOk();
        $response->assertSee('حضور مجموعة الأحد');
    }

    public function test_calendar_shows_exams(): void
    {
        $exam = Exam::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'امتحان منتصف الفصل',
            'duration_minutes' => 45,
            'starts_at' => now()->startOfMonth()->addDays(5),
            'ends_at' => now()->startOfMonth()->addDays(5)->addHours(2),
            'status' => 'visible',
        ]);
        $exam->targetGroups()->attach($this->groupA->id);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.calendar.index', ['month' => now()->format('Y-m')]));

        $response->assertOk();
        $response->assertSee('امتحان امتحان منتصف الفصل');
    }

    public function test_calendar_group_filter_works(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.calendar.index', ['group_id' => $this->groupB->id]));

        $response->assertOk();
        $response->assertSee('حصة مجموعة الاثنين');
        $response->assertDontSee('حصة مجموعة الأحد');
    }

    public function test_calendar_hides_other_teachers_data(): void
    {
        AttendanceSession::create([
            'study_group_id' => $this->otherGroup->id,
            'teacher_id' => $this->otherTeacher->id,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'closed',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.calendar.index'));

        $response->assertOk();
        $response->assertDontSee('مجموعة المدرس الآخر');
    }

    public function test_calendar_rejects_other_teachers_group_filter(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.calendar.index', ['group_id' => $this->otherGroup->id]))
            ->assertForbidden();
    }

    public function test_guest_cannot_open_calendar(): void
    {
        // Auth middleware redirects guests to the login page.
        $this->get(route('teacher.calendar.index'))->assertRedirect(route('login'));
    }

    // ===================== 4. Student Progress Tracking =====================

    private function makeExamWithAttempt(User $student, int $score, int $total, array $groups): Exam
    {
        $exam = Exam::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'امتحان تجريبي ' . uniqid(),
            'duration_minutes' => 30,
            'status' => 'visible',
        ]);
        foreach ($groups as $g) {
            $exam->targetGroups()->attach($g->id);
        }

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $student->id,
            'started_at' => now()->subHour(), 'finished_at' => now(),
            'score' => $score, 'total_marks' => $total, 'status' => 'finished',
        ]);

        return $exam;
    }

    public function test_progress_shows_correct_calculations_for_selected_group(): void
    {
        $this->makeExamWithAttempt($this->studentA, 8, 10, [$this->groupA]);
        $this->makeExamWithAttempt($this->studentA, 6, 10, [$this->groupA]); // avg = 70%

        $session = AttendanceSession::create([
            'study_group_id' => $this->groupA->id,
            'teacher_id' => $this->teacher->id,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(), 'status' => 'closed',
        ]);
        $secondSession = AttendanceSession::create([
            'study_group_id' => $this->groupA->id,
            'teacher_id' => $this->teacher->id,
            'attendance_date' => now()->subDay()->toDateString(),
            'started_at' => now()->subDay(), 'status' => 'closed',
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id, 'student_id' => $this->studentA->id,
            'group_id' => $this->groupA->id, 'attendance_type' => 'regular', 'scanned_at' => now(),
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $secondSession->id, 'student_id' => $this->studentA->id,
            'group_id' => $this->groupA->id, 'attendance_type' => 'absent', 'scanned_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.progress.index', ['group_id' => $this->groupA->id]));

        $response->assertOk();
        $response->assertSee('طالب أول');
        $response->assertSee('>70%', false);      // (8+6)/(10+10) = 70%
        $response->assertDontSee('طالب ثانٍ');     // enrolled in groupB only
    }

    public function test_progress_student_detail_scope_is_correct(): void
    {
        $this->makeExamWithAttempt($this->studentA, 9, 10, [$this->groupA]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.progress.index', [
            'group_id' => $this->groupA->id,
            'student_id' => $this->studentA->id,
        ]));

        $response->assertOk();
        $response->assertSee('نتائج الامتحانات');
        $response->assertSee('9 / 10');
    }

    public function test_progress_never_shows_other_teachers_students(): void
    {
        $outsider = $this->makeStudent('طالب خارجي', '0166666666', $this->otherGroup);
        $this->makeExamWithAttempt($outsider, 10, 10, [$this->otherGroup]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.progress.index', ['group_id' => $this->groupA->id]));

        $response->assertOk();
        $response->assertDontSee('طالب خارجي');
    }

    public function test_progress_rejects_other_teachers_group(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.progress.index', ['group_id' => $this->otherGroup->id]))
            ->assertForbidden();
    }

    public function test_running_attempts_are_not_counted_as_progress(): void
    {
        $exam = Exam::create([
            'teacher_id' => $this->teacher->id, 'title' => 'امتحان جارٍ',
            'duration_minutes' => 30, 'status' => 'visible',
        ]);
        $exam->targetGroups()->attach($this->groupA->id);

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now(), 'status' => 'running',
        ]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.progress.index', [
            'group_id' => $this->groupA->id,
            'student_id' => $this->studentA->id,
        ]));

        $response->assertOk();
        $response->assertSee('لم يكمل هذا الطالب أي امتحان بعد'); // empty detail state
    }

    // ===================== 5. Teacher Settings =====================

    public function test_settings_page_opens(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.settings.index'))
            ->assertOk()
            ->assertSee('تغيير كلمة المرور')
            ->assertSee($this->teacher->teacher_code);
    }

    public function test_settings_password_change_saves_and_persists(): void
    {
        $response = $this->actingAs($this->teacher)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertRedirect();
        $this->teacher->refresh();
        $this->assertTrue(Hash::check('new-secret-123', $this->teacher->password));
    }

    public function test_settings_password_change_validates_current_password(): void
    {
        $response = $this->actingAs($this->teacher)->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertSessionHasErrors(['current_password'], null, 'updatePassword');
        $this->teacher->refresh();
        $this->assertTrue(Hash::check('password', $this->teacher->password)); // unchanged
    }

    public function test_settings_password_change_validates_confirmation(): void
    {
        $this->actingAs($this->teacher)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'different-123',
        ])->assertSessionHasErrors(['password'], null, 'updatePassword');

        $this->teacher->refresh();
        $this->assertTrue(Hash::check('password', $this->teacher->password));
    }

    public function test_student_cannot_open_teacher_settings(): void
    {
        $this->actingAs($this->studentA)
            ->get(route('teacher.settings.index'))
            ->assertForbidden();
    }
}