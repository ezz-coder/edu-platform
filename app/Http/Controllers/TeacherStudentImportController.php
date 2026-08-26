<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Models\User;
use App\Services\XlsxReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sprint 66 — Bulk Student Import (CSV / Excel).
 *
 * Workflow (per specification): Students → Import → upload CSV/Excel →
 * validate every row → create accepted students only → reject invalid rows
 * with a clear reason → show the import result.
 *
 * Validation mirrors the existing Student Registration / Teacher Add Student
 * rules: required fields, mobile validity, duplicate enrollment in the same
 * group, group ownership + active status + capacity. A brand-new independent
 * student account is created per row (same model as registration).
 *
 * Files are processed from the PHP temp upload and never stored permanently.
 */
class TeacherStudentImportController extends Controller
{
    private const MAX_SIZE_KB = 5120; // 5MB

    public function create(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('title')
            ->get();

        return view('teacher.students.import', [
            'header' => 'استيراد الطلاب',
            'groups' => $groups,
            'results' => session('import_results'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:' . self::MAX_SIZE_KB],
        ]);

        // Group must belong to the current teacher and be active.
        $group = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', auth()->id())
            ->first();

        if (! $group) {
            return back()->with('error', 'المجموعة غير موجودة أو لا تتبعك.');
        }

        if ($group->status !== 'active') {
            return back()->with('error', 'لا يمكن الاستيراد إلى مجموعة غير نشطة.');
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            return back()->with('error', 'نوع الملف غير مدعوم. الصيغ المدعومة: CSV و XLSX فقط.');
        }

        try {
            $rows = $extension === 'csv'
                ? $this->parseCsv($file->getRealPath())
                : XlsxReader::read($file->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('error', 'الملف فارغ أو لا يحتوي على صفوف بيانات. يجب أن يحتوي على صف عناوين: name, mobile, parent_mobile, password');
        }

        // Header mapping (accept Arabic or English headers).
        $map = $this->mapHeader(array_shift($rows));
        if (! isset($map['name'], $map['mobile'], $map['parent_mobile'])) {
            return back()->with('error', 'ترويسة الملف غير صحيحة. الأعمدة المطلوبة: name, mobile, parent_mobile, password');
        }

        $results = ['success' => 0, 'rejected' => 0, 'errors' => []];
        $seatsLeft = $group->remainingSeats();

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $line = $index + 2; // header consumed; human-friendly line number
                $name = trim($row[$map['name']] ?? '');
                $mobile = trim($row[$map['mobile']] ?? '');
                $parentMobile = trim($row[$map['parent_mobile']] ?? '');
                $password = isset($map['password']) ? trim($row[$map['password']] ?? '') : '';

                $reason = $this->validateRow($group, $name, $mobile, $parentMobile, $password, $seatsLeft);

                if ($reason !== null) {
                    $results['rejected']++;
                    $results['errors'][] = "الصف {$line}: {$reason}";
                    continue;
                }

                $student = User::create([
                    'name' => $name,
                    'email' => 'student_' . $mobile . '@placeholder.local',
                    'mobile' => $mobile,
                    'parent_mobile' => $parentMobile,
                    'password' => $password,
                    'role' => 'student',
                    'status' => 'active',
                    'student_code' => User::generateStudentCode(),
                ]);

                GroupEnrollment::create([
                    'student_id' => $student->id,
                    'group_id' => $group->id,
                    'academic_term_id' => $group->academic_term_id,
                    'card_id' => GroupEnrollment::generateCardId(),
                    'qr_token' => GroupEnrollment::generateQrToken(),
                    'status' => 'enrolled',
                ])->recordRegistered(auth()->user()->name);

                $results['success']++;
                $seatsLeft--;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'حدث خطأ أثناء معالجة الملف. لم يتم استيراد أي بيانات.');
        }

        if ($results['success'] > 0) {
            TeacherActivity::log(
                auth()->id(),
                'students_imported',
                'استيراد طلاب',
                "تم استيراد {$results['success']} طالب إلى مجموعة {$group->title} (مرفوض: {$results['rejected']})"
            );
        }

        return redirect()
            ->route('teacher.students.import')
            ->with('import_results', $results)
            ->with('success', "تم استيراد {$results['success']} طالب بنجاح، ورفض {$results['rejected']} صف.");
    }

    /**
     * Validate a single row. Returns null when valid, or the rejection reason.
     */
    private function validateRow(StudyGroup $group, string $name, string $mobile, string $parentMobile, string $password, int $seatsLeft): ?string
    {
        if ($name === '' || mb_strlen($name) > 255) {
            return 'الاسم مطلوب (حتى 255 حرفاً).';
        }

        if ($mobile === '' || ! preg_match('/^[0-9+\s-]{8,20}$/', $mobile)) {
            return 'رقم الجوال غير صالح.';
        }

        if ($parentMobile === '' || ! preg_match('/^[0-9+\s-]{8,20}$/', $parentMobile)) {
            return 'رقم جوال ولي الأمر غير صالح.';
        }

        if ($password === '' || strlen($password) < 8) {
            return 'كلمة المرور مطلوبة (8 أحرف على الأقل).';
        }

        // Duplicate enrollment: same mobile already enrolled in this group.
        $exists = GroupEnrollment::where('group_id', $group->id)
            ->whereHas('student', fn ($q) => $q->where('mobile', $mobile))
            ->exists();

        if ($exists) {
            return 'رقم الجوال مسجل بالفعل في هذه المجموعة.';
        }

        if ($seatsLeft <= 0) {
            return 'المجموعة ممتلئة ولا توجد مقاعد شاغرة.';
        }

        return null;
    }

    /**
     * Map header names to column indexes. Accepts English or Arabic headers.
     */
    private function mapHeader(array $header): array
    {
        $aliases = [
            'name' => ['name', 'الاسم', 'اسم الطالب'],
            'mobile' => ['mobile', 'phone', 'الجوال', 'الموبايل', 'رقم الجوال'],
            'parent_mobile' => ['parent_mobile', 'parent mobile', 'parent phone', 'جوال ولي الأمر', 'هاتف ولي الأمر'],
            'password' => ['password', 'كلمة المرور'],
        ];

        $map = [];
        foreach ($header as $i => $raw) {
            $value = strtolower(trim((string) $raw));
            foreach ($aliases as $key => $names) {
                if (in_array($value, array_map('strtolower', $names), true)) {
                    $map[$key] = $i;
                }
            }
        }

        return $map;
    }

    /**
     * Parse a CSV file into rows of trimmed strings.
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('تعذر قراءة ملف CSV.');
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($v) => trim((string) $v), $data);
        }
        fclose($handle);

        return $rows;
    }
}