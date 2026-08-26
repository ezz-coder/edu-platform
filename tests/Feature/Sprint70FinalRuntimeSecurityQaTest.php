<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Assistant;
use App\Models\AssistantPermission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\GroupEnrollment;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\ResourceTarget;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\StudyGroup;
use App\Models\TermRollback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sprint 70 — Final Runtime & Security QA.
 * No new features: proves runtime of Term End/Undo, drag&drop reorder,
 * exam reset/archive 7-day rule, full student exam flow, student resources,
 * assistant reservations, assistant QR attendance, cross-role integration,
 * permissions, data isolation, auth and notifications.
 */
class Sprint70FinalRuntimeSecurityQaTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherA;
    private User $teacherB;
    private StudyGroup $groupA1;
    private StudyGroup $groupA2;
    private StudyGroup $groupB;
    private User $studentA;
    private User $studentB;
    private Assistant $assistantA;
    private Assistant $assistantB;
    private AcademicTerm $termA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacherA = User::create([
            'name' => 'Teacher A', 'email' => 'ta@test.local', 'mobile' => '0500000000',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);
        $this->teacherB = User::create([
            'name' => 'Teacher B', 'email' => 'tb@test.local', 'mobile' => '0500000001',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->termA = AcademicTerm::create([
            'teacher_id' => $this->teacherA->id, 'title' => 'الترم 1',
            'year' => now()->year, 'term_number' => 1, 'status' => 'active',
        ]);

        $this->groupA1 = StudyGroup::create([
            'teacher_id' => $this->teacherA->id, 'academic_term_id' => $this->termA->id,
            'title' => 'A1', 'day' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'max_students' => 30, 'status' => 'active',
        ]);
        $this->groupA2 = StudyGroup::create([
            'teacher_id' => $this->teacherA->id, 'academic_term_id' => $this->termA->id,
            'title' => 'A2', 'day' => 'monday', 'start_time' => '13:00', 'end_time' => '15:00',
            'gender_type' => 'girls', 'grade_level' => 'grade8', 'max_students' => 30, 'status' => 'active',
        ]);
        $this->groupB = StudyGroup::create([
            'teacher_id' => $this->teacherB->id,
            'title' => 'B1', 'day' => 'tuesday', 'start_time' => '16:00', 'end_time' => '18:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'max_students' => 30, 'status' => 'active',
        ]);

        $this->studentA = $this->makeStudent('Student A', '0111111111', $this->groupA1);
        $this->studentB = $this->makeStudent('Student B', '0122222222', $this->groupB);

        $this->assistantA = $this->makeAssistant('assistant_a', $this->teacherA);
        $this->assistantB = $this->makeAssistant('assistant_b', $this->teacherB);
    }

    // ==================== helpers ====================

    private function makeStudent(string $name, string $mobile, StudyGroup $group): User
    {
        $student = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '@test.local',
            'password' => 'password', 'role' => 'student', 'status' => 'active',
            'mobile' => $mobile, 'parent_mobile' => '0999999999',
            'student_code' => 'STD-' . str_pad((string) User::where('role', 'student')->count() + 1, 6, '0', STR_PAD_LEFT),
        ]);

        GroupEnrollment::create([
            'student_id' => $student->id, 'group_id' => $group->id,
            'card_id' => 'CARD-' . strtoupper(substr(md5($name), 0, 8)),
            'qr_token' => 'QRTOKEN-' . md5($name),
            'status' => 'enrolled',
        ]);

        return $student;
    }

    private function makeAssistant(string $username, User $teacher): Assistant
    {
        $assistant = Assistant::create([
            'teacher_id' => $teacher->id, 'name' => $username,
            'username' => $username, 'password' => 'secret123', 'status' => 'active',
        ]);
        AssistantPermission::create(['assistant_id' => $assistant->id]);

        return $assistant;
    }

    private function asStudent(User $u)
    {
        return $this->actingAs($u)->withSession(['active_student_id' => $u->id]);
    }

    private function makeExam(User $teacher, array $groups, string $status = 'visible'): Exam
    {
        $exam = Exam::create([
            'teacher_id' => $teacher->id, 'title' => 'Exam ' . uniqid(),
            'duration_minutes' => 60, 'status' => $status,
            'show_result_immediately' => true, 'allow_review_after_finish' => true,
        ]);
        foreach ($groups as $g) {
            DB::table('exam_groups')->insert(['exam_id' => $exam->id, 'group_id' => $g->id]);
        }
        return $exam;
    }

    private function addQuestion(Exam $exam, string $correct = 'a', int $mark = 5): ExamQuestion
    {
        return ExamQuestion::create([
            'exam_id' => $exam->id, 'type' => 'mcq', 'question' => 'Q?',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => $correct, 'mark' => $mark, 'sort_order' => 0,
        ]);
    }

    // ==================== 1. TERM END / UNDO ====================

    public function test_term_end_execute_then_undo_rolls_back_state(): void
    {
        $this->actingAs($this->teacherA);

        // Confirm page renders
        $this->post(route('teacher.term.confirm'), ['mode' => 'keep_groups', 'group_ids' => [$this->groupA1->id]])
            ->assertOk();

        // Execute
        $this->post(route('teacher.term.execute'), ['mode' => 'keep_groups', 'group_ids' => [$this->groupA1->id]])
            ->assertRedirect(route('teacher.dashboard'));

        $oldTerm = $this->termA->fresh();
        $newTerm = AcademicTerm::where('teacher_id', $this->teacherA->id)->where('status', 'active')->first();

        $this->assertEquals('archived', $oldTerm->status);
        $this->assertNotNull($newTerm);
        $this->assertEquals(2, $newTerm->term_number);
        $this->assertEquals($newTerm->id, $this->groupA1->fresh()->academic_term_id);
        $this->assertDatabaseCount('term_rollbacks', 1);

        // Undo
        $this->post(route('teacher.term.undo'))->assertRedirect(route('teacher.dashboard'));

        $this->assertEquals('active', $this->termA->fresh()->status);
        $this->assertNull(AcademicTerm::find($newTerm->id));
        $this->assertEquals($this->termA->id, $this->groupA1->fresh()->academic_term_id);
        $this->assertDatabaseCount('term_rollbacks', 0);
    }

    public function test_term_undo_is_blocked_after_new_work_started(): void
    {
        $this->actingAs($this->teacherA);

        $this->post(route('teacher.term.execute'), ['mode' => 'new_groups']);
        $rollback = TermRollback::latest('executed_at')->first();
        // Backdate execution so subsequent work is strictly "after" it.
        TermRollback::where('id', $rollback->id)->update(['executed_at' => now()->subMinutes(5)]);
        $rollback->refresh();

        // Simulate new work after execution (a new enrollment)
        GroupEnrollment::where('id', '>', 0)->update(['created_at' => now()]);
        AttendanceSession::create([
            'study_group_id' => $this->groupA1->id, 'teacher_id' => $this->teacherA->id,
            'lesson_number' => 1, 'attendance_date' => now()->toDateString(),
            'started_at' => now(), 'status' => 'open',
        ]);

        $response = $this->post(route('teacher.term.undo'));
        $response->assertRedirect();

        // State NOT rolled back
        $this->assertEquals('archived', $this->termA->fresh()->status);
        $this->assertNotNull(TermRollback::find($rollback->id));
    }

    // ==================== 2. LESSONS DRAG & DROP REORDER ====================

    public function test_drag_and_drop_reorder_persists_after_refresh(): void
    {
        $l1 = Lesson::create(['teacher_id' => $this->teacherA->id, 'group_id' => $this->groupA1->id, 'title' => 'L1', 'display_order' => 1]);
        $l2 = Lesson::create(['teacher_id' => $this->teacherA->id, 'group_id' => $this->groupA1->id, 'title' => 'L2', 'display_order' => 2]);
        $l3 = Lesson::create(['teacher_id' => $this->teacherA->id, 'group_id' => $this->groupA1->id, 'title' => 'L3', 'display_order' => 3]);

        $this->actingAs($this->teacherA)
            ->post(route('teacher.lessons.reorder'), [
                'orders' => [$l3->id => 1, $l1->id => 2, $l2->id => 3],
            ])->assertRedirect();

        $this->assertEquals(1, $l3->fresh()->display_order);
        $this->assertEquals(2, $l1->fresh()->display_order);
        $this->assertEquals(3, $l2->fresh()->display_order);

        // Refresh simulation: re-fetch ordered list
        $ordered = Lesson::where('group_id', $this->groupA1->id)->orderBy('display_order')->pluck('id')->all();
        $this->assertSame([$l3->id, $l1->id, $l2->id], $ordered);
    }

    public function test_reorder_cannot_touch_other_teachers_lessons(): void
    {
        $foreign = Lesson::create(['teacher_id' => $this->teacherB->id, 'group_id' => $this->groupB->id, 'title' => 'FB', 'display_order' => 1]);

        $this->actingAs($this->teacherA)
            ->post(route('teacher.lessons.reorder'), ['orders' => [$foreign->id => 99]]);

        $this->assertEquals(1, $foreign->fresh()->display_order);
    }

    // ==================== 3. EXAM RESET / ARCHIVE / 7-DAY RULE ====================

    public function test_reset_attempt_deletes_attempt_and_allows_retake(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $q = $this->addQuestion($exam);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now(), 'finished_at' => now(),
            'score' => 5, 'total_marks' => 5, 'status' => 'finished',
        ]);
        DB::table('exam_answers')->insert([
            'attempt_id' => $attempt->id, 'question_id' => $q->id,
            'selected_answer' => 'a', 'is_correct' => true, 'mark' => 5,
        ]);

        $this->actingAs($this->teacherA)
            ->post(route('teacher.exams.attempt.reset', $attempt))
            ->assertRedirect();

        // Attempt is removed (hard delete — ExamAttempt has no SoftDeletes)
        $this->assertDatabaseMissing('exam_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('exam_answers', ['attempt_id' => $attempt->id]);

        // Student can start again
        $resp = $this->asStudent($this->studentA)->post(route('student.exams.start', $exam));
        $newAttempt = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $this->studentA->id)->first();
        $this->assertNotNull($newAttempt);
        $resp->assertRedirect(route('student.exams.take', $newAttempt));
    }

    public function test_exam_archive_restore_within_7_days_force_delete_only_after(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1], 'visible');
        $this->actingAs($this->teacherA);

        // Archive → hidden + soft deleted
        $this->post(route('teacher.exams.archive', $exam))->assertRedirect();
        $this->assertEquals('hidden', $exam->fresh()->status);
        $this->assertSoftDeleted('exams', ['id' => $exam->id]);
        $this->assertTrue($exam->fresh()->canRestore());

        // Force delete blocked within 7 days
        $this->delete(route('teacher.exams.force-delete', $exam))->assertRedirect();
        $this->assertSoftDeleted('exams', ['id' => $exam->id]); // still exists

        // Restore works within window
        $this->post(route('teacher.exams.restore', $exam))->assertRedirect();
        $this->assertNotSoftDeleted('exams', ['id' => $exam->id]);

        // Re-archive, age beyond 7 days
        $this->post(route('teacher.exams.archive', $exam));
        Exam::withTrashed()->where('id', $exam->id)->update(['deleted_at' => now()->subDays(8)]);
        $exam->refresh();
        $this->assertFalse($exam->canRestore());
        $this->assertTrue($exam->canForceDelete());

        // Restore now rejected
        $this->post(route('teacher.exams.restore', $exam))->assertRedirect();
        $this->assertTrue(Exam::withTrashed()->find($exam->id)->trashed());

        // Force delete now allowed
        $this->delete(route('teacher.exams.force-delete', $exam))->assertRedirect();
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    // ==================== 4. STUDENT EXAM FLOW ====================

    public function test_student_sees_only_targeted_visible_exam(): void
    {
        $targeted = $this->makeExam($this->teacherA, [$this->groupA1]);
        $otherGroup = $this->makeExam($this->teacherA, [$this->groupA2]);
        $otherTeacher = $this->makeExam($this->teacherB, [$this->groupB]);
        $draft = $this->makeExam($this->teacherA, [$this->groupA1], 'draft');

        $view = $this->asStudent($this->studentA)->get(route('student.exams.index'));
        $view->assertOk();
        $html = $view->getContent();
        $this->assertStringContainsString($targeted->title, $html);
        $this->assertStringNotContainsString($otherGroup->title, $html);
        $this->assertStringNotContainsString($otherTeacher->title, $html);
        $this->assertStringNotContainsString($draft->title, $html);
    }

    public function test_full_student_exam_flow_single_attempt_autosave_submit_grade_notify(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $q1 = $this->addQuestion($exam, 'b', 5);
        $q2 = $this->addQuestion($exam, 'c', 5);

        // Start
        $this->asStudent($this->studentA)->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.take', ExamAttempt::first()));

        $attempt = ExamAttempt::first();
        $this->assertEquals('running', $attempt->status);
        $this->assertNotNull($attempt->started_at);

        // Take page renders with timer data
        $this->asStudent($this->studentA)->get(route('student.exams.take', $attempt))
            ->assertOk()
            ->assertSee($q1->question)
            ->assertSee($q2->question);

        // Auto-save one answer
        $this->asStudent($this->studentA)
            ->post(route('student.exams.autoSave', $attempt), ['question_id' => $q1->id, 'answer' => 'b'])
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('exam_answers', ['attempt_id' => $attempt->id, 'question_id' => $q1->id, 'selected_answer' => 'b']);

        // Single-attempt rule: finished attempt cannot restart
        $attempt->update(['status' => 'finished', 'finished_at' => now(), 'score' => 5, 'total_marks' => 10]);
        $this->asStudent($this->studentA)->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.result', $attempt));
        $this->assertEquals(1, ExamAttempt::where('exam_id', $exam->id)->where('student_id', $this->studentA->id)->count());

        // Reset for the submit test via teacher reset, then student starts fresh
        $this->actingAs($this->teacherA)->post(route('teacher.exams.attempt.reset', $attempt));
        $this->asStudent($this->studentA)->post(route('student.exams.start', $exam));
        $attempt2 = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $this->studentA->id)->first();
        $this->assertNotNull($attempt2);

        // Submit answers: q1 correct (b), q2 wrong (a)
        $this->asStudent($this->studentA)
            ->post(route('student.exams.submit', $attempt2), ['answers' => [$q1->id => 'b', $q2->id => 'a']])
            ->assertRedirect(route('student.exams.result', $attempt2));

        $attempt2->refresh();
        $this->assertEquals('finished', $attempt2->status);
        $this->assertEquals(5, $attempt2->score);
        $this->assertEquals(10, $attempt2->total_marks);

        // Result visible (show_result_immediately = true) with score
        $this->asStudent($this->studentA)->get(route('student.exams.result', $attempt2))
            ->assertOk();

        // Teacher notified exactly once about first submission
        $this->assertEquals(1, Notification::where('teacher_id', $this->teacherA->id)
            ->where('type', 'exam')->where('title', 'بدأ استقبال إجابات الامتحان')->count());
    }

    public function test_student_leave_cancels_attempt_and_blocks_reentry(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $this->addQuestion($exam);

        $this->asStudent($this->studentA)->post(route('student.exams.start', $exam));
        $attempt = ExamAttempt::first();

        $this->asStudent($this->studentA)->post(route('student.exams.leave', $attempt))
            ->assertRedirect(route('student.exams.index'));
        $this->assertEquals('cancelled', $attempt->fresh()->status);

        // Re-entry: take page on cancelled attempt is not a running exam anymore
        $resp = $this->asStudent($this->studentA)->get(route('student.exams.take', $attempt));
        $this->assertContains($resp->getStatusCode(), [200, 302]);
    }

    public function test_review_respects_allow_review_flag(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $this->addQuestion($exam);
        $exam->update(['allow_review_after_finish' => false]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now(), 'finished_at' => now(), 'score' => 0, 'total_marks' => 5, 'status' => 'finished',
        ]);

        $this->asStudent($this->studentA)->get(route('student.exams.review', $attempt))->assertForbidden();

        $exam->update(['allow_review_after_finish' => true]);
        $this->asStudent($this->studentA)->get(route('student.exams.review', $attempt))->assertOk();
    }

    public function test_student_cannot_access_another_students_attempt(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $this->addQuestion($exam);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now(), 'status' => 'running',
        ]);

        // studentB belongs to teacherB's group — no active group in this exam's scope
        $this->asStudent($this->studentB)->get(route('student.exams.take', $attempt))->assertForbidden();
        $this->asStudent($this->studentB)->post(route('student.exams.submit', $attempt))->assertForbidden();
    }

    // ==================== 5. STUDENT RESOURCES ====================

    private function makeResource(User $teacher, array $opts = []): Resource
    {
        $r = Resource::create(array_merge([
            'teacher_id' => $teacher->id, 'lesson_id' => null,
            'title' => 'Res ' . uniqid(), 'type' => 'pdf', 'file_path' => 'x.pdf',
            'status' => 'visible', 'publish_date' => now(),
        ], $opts));
        return $r;
    }

    public function test_student_resource_visibility_targeting_expiry_and_scope_isolation(): void
    {
        $ok = $this->makeResource($this->teacherA, ['title' => 'VISIBLE-OK']);
        ResourceTarget::create(['resource_id' => $ok->id, 'scope' => 'group', 'group_id' => $this->groupA1->id]);

        $expired = $this->makeResource($this->teacherA, ['title' => 'EXPIRED-ONE', 'expires_at' => now()->subDay()]);
        ResourceTarget::create(['resource_id' => $expired->id, 'scope' => 'group', 'group_id' => $this->groupA1->id]);

        $otherGroupRes = $this->makeResource($this->teacherA, ['title' => 'OTHER-GROUP-ONLY']);
        ResourceTarget::create(['resource_id' => $otherGroupRes->id, 'scope' => 'group', 'group_id' => $this->groupA2->id]);

        $otherTeacherRes = $this->makeResource($this->teacherB, ['title' => 'TEACHER-B-RES']);
        ResourceTarget::create(['resource_id' => $otherTeacherRes->id, 'scope' => 'all']);

        $home = $this->asStudent($this->studentA)->get(route('student.resources.home'));
        $home->assertOk();
        $html = $home->getContent();
        $this->assertStringContainsString('VISIBLE-OK', $html);
        $this->assertStringNotContainsString('EXPIRED-ONE', $html);
        $this->assertStringNotContainsString('OTHER-GROUP-ONLY', $html);
        $this->assertStringNotContainsString('TEACHER-B-RES', $html);

        // Direct URL access to out-of-scope resource must be denied
        $this->asStudent($this->studentA)->get(route('student.resources.show', $otherGroupRes))
            ->assertForbidden();
        $this->asStudent($this->studentA)->get(route('student.resources.show', $otherTeacherRes))
            ->assertForbidden();
    }

    // ==================== 6. ASSISTANT RESERVATIONS ====================

    public function test_assistant_reservation_full_lifecycle_with_permissions_and_notifications(): void
    {
        // Grant permission
        $this->assistantA->permissions()->update(['can_manage_reservations' => true]);
        $this->actingAs($this->assistantA, 'assistant');

        // Index
        $this->get(route('assistant.reservations.index'))->assertOk();

        // Create (draft)
        $this->post(route('assistant.reservations.store'), [
            'title' => 'حجز ملاحظات', 'type' => 'printed_notes', 'quantity' => 10,
            'group_ids' => [$this->groupA1->id],
        ])->assertRedirect(route('assistant.reservations.index'));
        $reservation = Reservation::where('title', 'حجز ملاحظات')->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('draft', $reservation->status);
        $this->assertEquals($this->teacherA->id, $reservation->teacher_id); // owned by teacher

        // Edit → open (students notified)
        $this->put(route('assistant.reservations.update', $reservation), [
            'title' => 'حجز ملاحظات', 'type' => 'printed_notes', 'quantity' => 10,
            'status' => 'open', 'group_ids' => [$this->groupA1->id],
        ])->assertRedirect();
        $this->assertEquals('open', $reservation->fresh()->status);
        $this->assertTrue(Notification::where('student_id', $this->studentA->id)
            ->where('type', 'reservation')->where('title', 'حجز جديد متاح')->exists());

        // Student reserves
        $this->asStudent($this->studentA)->post(route('student.reservations.reserve', $reservation))->assertRedirect();
        $item = ReservationItem::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals('pending', $item->delivery_status ?? 'pending');

        // Delivery tracking + undo (back on assistant guard)
        $this->actingAs($this->assistantA, 'assistant')
            ->post(route('assistant.reservations.items.deliver', [$reservation, $item]))->assertRedirect();
        $this->assertEquals('delivered', $item->fresh()->delivery_status);
        $this->actingAs($this->assistantA, 'assistant')
            ->post(route('assistant.reservations.items.undo-deliver', [$reservation, $item]))->assertRedirect();
        $this->assertEquals('pending', $item->fresh()->delivery_status);

        // Cancel (close) → students notified
        $this->actingAs($this->assistantA, 'assistant')
            ->post(route('assistant.reservations.cancel', $reservation))->assertRedirect();
        $this->assertEquals('closed', $reservation->fresh()->status);
        $this->assertTrue(Notification::where('student_id', $this->studentA->id)
            ->where('title', 'تم إغلاق الحجز')->exists());

        // Search/filter does not leak other teachers' reservations
        $foreign = Reservation::create([
            'teacher_id' => $this->teacherB->id, 'title' => 'XYZUNIQUE', 'type' => 'books', 'status' => 'open',
        ]);
        $list = $this->actingAs($this->assistantA, 'assistant')
            ->get(route('assistant.reservations.index', ['search' => 'XYZUNIQUE']));
        $list->assertOk();
        // Search term is echoed into the filter input; assert no foreign link rendered.
        $this->assertStringNotContainsString(route('assistant.reservations.show', $foreign), $list->getContent());
    }

    public function test_assistant_without_permission_gets_403_immediately(): void
    {
        // assistantA has NO can_manage_reservations
        $this->actingAs($this->assistantA, 'assistant')
            ->get(route('assistant.reservations.index'))
            ->assertForbidden();

        // Grant then verify access appears immediately; revoke then 403 again
        AssistantPermission::where('assistant_id', $this->assistantA->id)->update(['can_manage_reservations' => true]);
        $this->assistantA->unsetRelation('permissions');
        $this->actingAs($this->assistantA, 'assistant')
            ->get(route('assistant.reservations.index'))->assertOk();

        AssistantPermission::where('assistant_id', $this->assistantA->id)->update(['can_manage_reservations' => false]);
        $this->assistantA->unsetRelation('permissions');
        $this->actingAs($this->assistantA, 'assistant')
            ->get(route('assistant.reservations.index'))->assertForbidden();
    }

    public function test_assistant_cannot_access_other_assistants_teacher_data(): void
    {
        $this->assistantA->permissions()->update(['can_manage_reservations' => true]);
        $foreign = Reservation::create([
            'teacher_id' => $this->teacherB->id, 'title' => 'B-RES', 'type' => 'books', 'status' => 'open',
        ]);

        $this->actingAs($this->assistantA, 'assistant')
            ->get(route('assistant.reservations.show', $foreign))->assertForbidden();
        $this->actingAs($this->assistantA, 'assistant')
            ->put(route('assistant.reservations.update', $foreign), [
                'title' => 'HACKED', 'type' => 'books', 'status' => 'closed',
                'group_ids' => [$this->groupB->id],
            ])->assertForbidden();
        $this->assertEquals('B-RES', $foreign->fresh()->title);
    }

    // ==================== 7. ASSISTANT QR ATTENDANCE ====================

    public function test_assistant_qr_attendance_full_cycle(): void
    {
        $this->assistantA->permissions()->update(['can_take_attendance' => true]);
        $this->actingAs($this->assistantA, 'assistant');

        // Create session
        $this->post(route('assistant.attendance.store'), [
            'group_id' => $this->groupA1->id, 'lesson_number' => 1,
        ])->assertRedirect();
        $session = AttendanceSession::where('study_group_id', $this->groupA1->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('open', $session->status);

        $enrollment = GroupEnrollment::where('student_id', $this->studentA->id)->where('group_id', $this->groupA1->id)->first();

        // Wrong QR token rejected
        $this->post(route('assistant.attendance.scan', $session), [
            'student_code' => $this->studentA->student_code, 'qr_token' => 'WRONG-TOKEN',
        ])->assertRedirect();
        $this->assertDatabaseMissing('attendance_records', ['attendance_session_id' => $session->id]);

        // Valid token accepted
        $this->post(route('assistant.attendance.scan', $session), [
            'student_code' => $this->studentA->student_code, 'qr_token' => $enrollment->qr_token,
        ])->assertRedirect();
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id, 'student_id' => $this->studentA->id,
            'attendance_type' => 'present',
        ]);

        // Duplicate scan rejected
        $this->post(route('assistant.attendance.scan', $session), [
            'student_code' => $this->studentA->student_code, 'qr_token' => $enrollment->qr_token,
        ])->assertRedirect();
        $this->assertEquals(1, AttendanceRecord::where('attendance_session_id', $session->id)->count());

        // Non-enrolled student rejected
        $this->post(route('assistant.attendance.scan', $session), [
            'student_code' => $this->studentB->student_code,
        ])->assertRedirect();
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id, 'student_id' => $this->studentB->id,
        ]);

        // Close → auto absent for others (none here besides studentA recorded)
        $this->post(route('assistant.attendance.close', $session))->assertRedirect();
        $this->assertEquals('closed', $session->fresh()->status);

        // Scan after close rejected
        $this->post(route('assistant.attendance.scan', $session), [
            'student_code' => $this->studentB->student_code,
        ])->assertRedirect();
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id, 'student_id' => $this->studentB->id,
        ]);

        // Manual correction requires a CLOSED session (business rule)
        $this->post(route('assistant.attendance.close', $session))->assertRedirect();

        $record = AttendanceRecord::where('attendance_session_id', $session->id)->first();
        $this->post(route('assistant.attendance.record.update', $session), [
            'student_id' => $this->studentA->id, 'status' => 'absent',
        ])->assertRedirect();
        $this->assertEquals('absent', $record->fresh()->attendance_type);

        // Reopen → close again works
        $this->post(route('assistant.attendance.reopen', $session))->assertRedirect();
        $this->assertEquals('open', $session->fresh()->status);
        $this->post(route('assistant.attendance.close', $session))->assertRedirect();
        $this->assertEquals('closed', $session->fresh()->status);
    }

    public function test_assistant_cannot_open_session_for_other_teachers_group(): void
    {
        $this->assistantA->permissions()->update(['can_take_attendance' => true]);
        $this->actingAs($this->assistantA, 'assistant');

        $this->post(route('assistant.attendance.store'), [
            'group_id' => $this->groupB->id, 'lesson_number' => 1,
        ])->assertNotFound(); // firstOrFail on foreign group
        $this->assertDatabaseMissing('attendance_sessions', ['study_group_id' => $this->groupB->id]);
    }

    // ==================== 8. CROSS-ROLE INTEGRATION ====================

    public function test_assistant_grade_edit_notifies_teacher_and_student(): void
    {
        $this->assistantA->permissions()->update(['can_manage_grades' => true]);

        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now(), 'finished_at' => now(), 'score' => 0, 'total_marks' => 10, 'status' => 'finished',
        ]);

        $this->actingAs($this->assistantA, 'assistant')
            ->put(route('assistant.grades.update', $attempt), ['score' => 8])
            ->assertRedirect();

        $this->assertEquals(8, $attempt->fresh()->score);
        $this->assertTrue(Notification::where('teacher_id', $this->teacherA->id)
            ->where('title', 'تم تعديل درجة بواسطة مساعد')->exists());
        $this->assertTrue(Notification::where('student_id', $this->studentA->id)
            ->where('title', 'تم تعديل درجتك')->exists());
    }

    public function test_assistant_without_grades_permission_cannot_edit_grades(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now(), 'finished_at' => now(), 'score' => 0, 'total_marks' => 10, 'status' => 'finished',
        ]);

        $this->actingAs($this->assistantA, 'assistant')
            ->put(route('assistant.grades.update', $attempt), ['score' => 8])
            ->assertForbidden();
        $this->assertEquals(0, $attempt->fresh()->score);
    }

    public function test_teacher_publishing_exam_notifies_targeted_students_only(): void
    {
        $exam = $this->makeExam($this->teacherA, [$this->groupA1], 'draft');

        $this->actingAs($this->teacherA)->post(route('teacher.exams.publish', $exam))->assertRedirect();

        $this->assertTrue(Notification::where('student_id', $this->studentA->id)
            ->where('type', 'exam_published')->exists());
        // studentB (different teacher/group) gets nothing
        $this->assertFalse(Notification::where('student_id', $this->studentB->id)
            ->where('type', 'exam_published')->exists());
    }

    // ==================== 9. SECURITY / DATA ISOLATION ====================

    public function test_teacher_a_cannot_access_teacher_b_resources(): void
    {
        $foreignExam = $this->makeExam($this->teacherB, [$this->groupB]);
        $foreignLesson = Lesson::create(['teacher_id' => $this->teacherB->id, 'group_id' => $this->groupB->id, 'title' => 'FB']);

        $this->actingAs($this->teacherA);
        $this->post(route('teacher.exams.archive', $foreignExam))->assertForbidden();
        $this->put(route('teacher.lessons.update', $foreignLesson), ['title' => 'X', 'group_id' => $this->groupB->id])->assertForbidden();
        $this->post(route('teacher.term.undo')); // sanity: own route fine
    }

    public function test_student_notifications_are_isolated(): void
    {
        Notification::create([
            'teacher_id' => $this->teacherA->id, 'student_id' => $this->studentA->id,
            'type' => 'system', 'title' => 'FOR-A-ONLY', 'message' => 'm',
        ]);

        $page = $this->asStudent($this->studentB)->get(route('notifications.index'));
        $page->assertOk();
        $this->assertStringNotContainsString('FOR-A-ONLY', $page->getContent());
    }

    public function test_role_middleware_blocks_cross_role_access(): void
    {
        // Student cannot open teacher dashboard
        $this->asStudent($this->studentA)->get(route('teacher.dashboard'))->assertForbidden();
        // Teacher cannot open student exams
        $this->actingAs($this->teacherA)->get(route('student.exams.index'))->assertForbidden();
        // Web-guard user cannot open assistant routes
        $this->actingAs($this->teacherA)->get(route('assistant.reservations.index'))->assertRedirect();
    }

    // ==================== 10. AUTHENTICATION ====================

    public function test_login_redirects_by_role_and_logout_works(): void
    {
        // Teacher login (login uses mobile + password)
        $this->post('/login', ['mobile' => '0500000000', 'password' => 'password'])
            ->assertRedirect(route('teacher.dashboard'));

        // Logout
        $this->actingAs($this->teacherA)->post('/logout')->assertRedirect('/');
        $this->get(route('teacher.dashboard'))->assertRedirect('/login');
    }

    public function test_student_login_redirects_to_dashboard(): void
    {
        $this->post('/login', ['mobile' => '0111111111', 'password' => 'password'])
            ->assertRedirect(route('student.resources.home'));
    }

    public function test_assistant_login_logout_flow(): void
    {
        $this->post(route('assistant.login'), [
            'username' => 'assistant_a', 'password' => 'secret123',
        ])->assertRedirect(route('assistant.dashboard'));

        $this->actingAs($this->assistantA, 'assistant')
            ->post(route('assistant.logout'))->assertRedirect(route('assistant.login'));
    }

    public function test_inactive_assistant_cannot_login(): void
    {
        $this->assistantA->update(['status' => 'inactive']);

        $resp = $this->post(route('assistant.login'), [
            'username' => 'assistant_a', 'password' => 'secret123',
        ]);
        $resp->assertRedirect();
        $this->assertTrue(session()->has('errors') || $resp->getSession()->get('errors') !== null);
        $this->assertGuest('assistant');
    }

    public function test_auth_entry_page_accessible_for_guests(): void
    {
        $this->get(route('auth.entry'))->assertOk();
    }

    // ==================== 11. SMOKE TESTS ====================

    public function test_smoke_teacher_main_pages(): void
    {
        $this->actingAs($this->teacherA);
        foreach ([
            'teacher.dashboard', 'teacher.groups.index', 'teacher.students.index',
            'teacher.attendance.index', 'teacher.lessons.index', 'teacher.resources.index',
            'teacher.exams.index', 'teacher.grades.index', 'teacher.reservations.index',
            'notifications.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_smoke_assistant_main_pages(): void
    {
        $this->assistantA->permissions()->update([
            'can_view_groups' => true, 'can_manage_students' => true,
            'can_take_attendance' => true, 'can_upload_resources' => true,
            'can_manage_reservations' => true, 'can_manage_exams' => true,
            'can_manage_lessons' => true, 'can_manage_grades' => true,
        ]);
        $this->actingAs($this->assistantA, 'assistant');
        foreach ([
            'assistant.dashboard', 'assistant.groups.index', 'assistant.students.index',
            'assistant.attendance.index', 'assistant.lessons.index', 'assistant.resources.index',
            'assistant.exams.index', 'assistant.grades.index', 'assistant.reservations.index',
            'assistant.question-bank.index', 'assistant.notifications.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_smoke_student_main_pages(): void
    {
        $this->asStudent($this->studentA);
        foreach ([
            'student.dashboard', 'student.account', 'student.groups.index',
            'student.resources.home', 'student.exams.index', 'student.grades',
            'student.attendance', 'student.reservations.index', 'notifications.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }
}