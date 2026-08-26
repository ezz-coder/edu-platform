<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

/**
 * Sprint 66 — Teacher Reports + Grades Export + Student Import (CSV/Excel).
 */
class Sprint66TeacherReportsExportImportTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private StudyGroup $groupA;
    private StudyGroup $groupB;   // other teacher's group
    private User $studentA;
    private User $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'Teacher', 'email' => 'teacher@test.local',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->otherTeacher = User::create([
            'name' => 'Other Teacher', 'email' => 'other@test.local',
            'password' => 'password', 'role' => 'teacher', 'status' => 'active',
        ]);

        $this->groupA = StudyGroup::create([
            'teacher_id' => $this->teacher->id, 'title' => 'مجموعة أ',
            'day' => 'sunday', 'start_time' => '10:00', 'end_time' => '12:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
            'max_students' => 30,
        ]);

        $this->groupB = StudyGroup::create([
            'teacher_id' => $this->otherTeacher->id, 'title' => 'مجموعة المدرس الآخر',
            'day' => 'monday', 'start_time' => '13:00', 'end_time' => '15:00',
            'gender_type' => 'boys', 'grade_level' => 'grade7', 'status' => 'active',
            'max_students' => 30,
        ]);

        $this->studentA = $this->makeStudent('طالب أول', '0111111111', $this->groupA);
        $this->studentB = $this->makeStudent('طالب ثانٍ', '0122222222', $this->groupA);
    }

    private function makeStudent(string $name, string $mobile, StudyGroup $group): User
    {
        $student = User::create([
            'name' => $name,
            'email' => 'student_' . $mobile . '@test.local',
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

    private function makeSession(StudyGroup $group): AttendanceSession
    {
        return AttendanceSession::create([
            'study_group_id' => $group->id,
            'teacher_id' => $group->teacher_id,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'closed',
        ]);
    }

    private function makeRecord(AttendanceSession $session, User $student, string $type = 'regular'): void
    {
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'group_id' => $session->study_group_id,
            'attendance_type' => $type,
            'scanned_at' => now(),
        ]);
    }

    private function makeExam(): Exam
    {
        $exam = Exam::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'امتحان الفصل الأول',
            'duration_minutes' => 30,
            'status' => 'visible',
        ]);
        $exam->targetGroups()->attach($this->groupA->id);

        return $exam;
    }

    // ===================== Reports =====================

    public function test_teacher_can_open_reports_page(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.reports.index'));

        $response->assertOk();
        $response->assertSee('التقارير');
    }

    public function test_report_shows_correct_attendance_and_grades_data(): void
    {
        $session = $this->makeSession($this->groupA);
        $this->makeRecord($session, $this->studentA);
        $this->makeRecord($session, $this->studentB, 'absent');

        $exam = $this->makeExam();
        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now()->subHour(), 'finished_at' => now(),
            'score' => 8, 'total_marks' => 10, 'status' => 'finished',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.reports.index', ['group_id' => $this->groupA->id]));

        $response->assertOk();
        $response->assertSee('مجموعة أ');
        $response->assertSee('طالب أول');
        // Grades summary aggregates reflect the finished attempt (8/10).
        $response->assertSee('عدد المحاولات المنتهية');
        $response->assertSee('>1<', false);
        $response->assertSee('>8<', false);
    }

    public function test_report_group_filter_excludes_other_groups_data(): void
    {
        $session = $this->makeSession($this->groupA);
        $this->makeRecord($session, $this->studentA);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.reports.index', ['group_id' => $this->groupA->id]));

        $response->assertOk();
        $response->assertDontSee('مجموعة المدرس الآخر');
    }

    public function test_report_date_filter_limits_sessions(): void
    {
        $session = $this->makeSession($this->groupA);
        $this->makeRecord($session, $this->studentA);

        // Future range excludes today's session.
        $response = $this->actingAs($this->teacher)->get(route('teacher.reports.index', [
            'date_from' => now()->addWeek()->toDateString(),
            'date_to' => now()->addWeeks(2)->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('لا توجد جلسات حضور مطابقة للفلاتر.');
    }

    public function test_teacher_cannot_see_other_teachers_data_in_reports(): void
    {
        $otherSession = $this->makeSession($this->groupB);
        $this->makeRecord($otherSession, $this->studentA);

        $response = $this->actingAs($this->teacher)->get(route('teacher.reports.index'));

        $response->assertOk();
        $response->assertDontSee('مجموعة المدرس الآخر');
    }

    public function test_student_and_guest_cannot_access_reports(): void
    {
        $this->actingAs($this->studentA)->get(route('teacher.reports.index'))->assertForbidden();
        $this->get(route('teacher.reports.index'))->assertForbidden();
    }

    // ===================== Grades Export =====================

    public function test_export_produces_csv_with_correct_values(): void
    {
        $exam = $this->makeExam();
        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentA->id,
            'started_at' => now()->subHour(), 'finished_at' => now(),
            'score' => 8, 'total_marks' => 10, 'status' => 'finished',
        ]);
        // Running attempt must NOT be exported.
        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->studentB->id,
            'started_at' => now(), 'status' => 'running',
        ]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.grades.export', $exam));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('طالب أول', $content);
        $this->assertStringContainsString('8', $content);
        $this->assertStringContainsString('80', $content); // percentage
        $this->assertStringNotContainsString('طالب ثانٍ', $content);
    }

    public function test_export_respects_ownership(): void
    {
        $exam = $this->makeExam();

        $this->actingAs($this->otherTeacher)
            ->get(route('teacher.grades.export', $exam))
            ->assertForbidden();
    }

    public function test_export_contains_no_out_of_scope_students(): void
    {
        $exam = $this->makeExam();
        $outsider = $this->makeStudent('طالب خارجي', '0144444444', $this->groupB);
        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $outsider->id,
            'started_at' => now()->subHour(), 'finished_at' => now(),
            'score' => 5, 'total_marks' => 10, 'status' => 'finished',
        ]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.grades.export', $exam));
        $response->assertOk();
        $this->assertStringNotContainsString('طالب خارجي', $response->streamedContent());
    }

    // ===================== Student Import =====================

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('students.csv', $content);
    }

    public function test_import_valid_file_creates_students(): void
    {
        $csv = "name,mobile,parent_mobile,password
"
            . "طالب جديد,0155555555,0988888888,secret123
"
            . "طالب آخر,0166666666,0977777777,secret456
";

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect(route('teacher.students.import'));
        $this->assertDatabaseHas('users', ['mobile' => '0155555555', 'role' => 'student']);
        $this->assertDatabaseHas('users', ['mobile' => '0166666666', 'role' => 'student']);

        $student = User::where('mobile', '0155555555')->first();
        $this->assertDatabaseHas('group_enrollments', [
            'student_id' => $student->id,
            'group_id' => $this->groupA->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_import_rejects_invalid_row_with_clear_reason(): void
    {
        $csv = "name,mobile,parent_mobile,password
"
            . ",0155555555,0988888888,secret123
"; // missing name

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_results', function ($results) {
            return $results['success'] === 0
                && $results['rejected'] === 1
                && str_contains($results['errors'][0], 'الاسم مطلوب');
        });
        $this->assertDatabaseMissing('users', ['mobile' => '0155555555']);
    }

    public function test_import_rejects_duplicate_mobile_in_same_group(): void
    {
        $csv = "name,mobile,parent_mobile,password
"
            . "مكرر,0111111111,0988888888,secret123
";

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_results', function ($results) {
            return str_contains($results['errors'][0] ?? '', 'مسجل بالفعل في هذه المجموعة');
        });
    }

    public function test_import_rejects_invalid_mobile(): void
    {
        $csv = "name,mobile,parent_mobile,password
"
            . "طالب,abc,0988888888,secret123
";

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_results', function ($results) {
            return str_contains($results['errors'][0] ?? '', 'رقم الجوال غير صالح');
        });
    }

    public function test_import_rejects_wrong_group_ownership(): void
    {
        $csv = "name,mobile,parent_mobile,password
"
            . "طالب,0177777777,0988888888,secret123
";

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupB->id, // belongs to another teacher
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['mobile' => '0177777777']);
    }

    public function test_import_empty_file_shows_error(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile(""),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_import_unsupported_file_type_is_rejected(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => UploadedFile::fake()->createWithContent('students.pdf', '%PDF-1.4'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', function ($error) {
            return str_contains($error, 'نوع الملف غير مدعوم');
        });
    }

    public function test_import_mixed_file_processes_valid_and_documents_invalid(): void
    {
        $csv = "name,mobile,parent_mobile,password
"
            . "صحيح,0188888888,0988888888,secret123
"
            . "خاطئ,bad-mobile,0988888888,secret123
";

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['mobile' => '0188888888']);
        $this->assertDatabaseMissing('users', ['mobile' => 'bad-mobile']);
        $response->assertSessionHas('import_results', function ($results) {
            return $results['success'] === 1 && $results['rejected'] === 1;
        });
    }

    public function test_import_xlsx_file_creates_students(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            . '<row r="1"><c r="A1"><v>name</v></c><c r="B1"><v>mobile</v></c><c r="C1"><v>parent_mobile</v></c><c r="D1"><v>password</v></c></row>'
            . '<row r="2"><c r="A2"><v>طالب إكسل</v></c><c r="B2"><v>0199999999</v></c><c r="C2"><v>0966666666</v></c><c r="D2"><v>secret789</v></c></row>'
            . '</sheetData></worksheet>');
        $zip->close();

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => new UploadedFile($path, 'students.xlsx', null, null, true),
        ]);

        $response->assertRedirect(route('teacher.students.import'));
        $this->assertDatabaseHas('users', ['mobile' => '0199999999', 'role' => 'student']);
        @unlink($path);
    }

    public function test_import_capacity_limit_is_enforced(): void
    {
        $this->groupA->update(['max_students' => 3]); // 2 enrolled already → 1 seat left

        $csv = "name,mobile,parent_mobile,password
"
            . "الأول,0121212121,0988888888,secret123
"
            . "الثاني,0131313131,0988888888,secret123
";

        $response = $this->actingAs($this->teacher)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['mobile' => '0121212121']);
        $this->assertDatabaseMissing('users', ['mobile' => '0131313131']);
        $response->assertSessionHas('import_results', function ($results) {
            return $results['success'] === 1 && $results['rejected'] === 1
                && str_contains($results['errors'][0], 'ممتلئة');
        });
    }

    public function test_student_cannot_access_import(): void
    {
        $this->actingAs($this->studentA)->get(route('teacher.students.import'))->assertForbidden();
        $this->actingAs($this->studentA)->post(route('teacher.students.import.store'), [
            'group_id' => $this->groupA->id,
        ])->assertForbidden();
    }
}