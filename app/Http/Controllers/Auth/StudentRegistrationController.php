<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class StudentRegistrationController extends Controller
{
    /**
     * Step 1: the student enters a teacher code.
     * If valid, the page shows that teacher's active groups.
     */
    public function create(Request $request): View
    {
        $teacher = null;
        $groups = collect();
        $invalidCode = false;

        $code = $request->query('code');

        if ($code) {
            $teacher = User::where('role', 'teacher')
                ->where('status', 'active')
                ->where('teacher_code', strtoupper($code))
                ->first();

            if ($teacher) {
                $groups = $teacher->studyGroups()
                    ->where('status', 'active')
                    ->latest()
                    ->get();
            } else {
                // Teacher code was submitted but not found — show error feedback
                $invalidCode = true;
            }
        }

        return view('auth.register-student', [
            'code' => $code,
            'teacher' => $teacher,
            'groups' => $groups,
            'invalidCode' => $invalidCode,
        ]);
    }

    /**
     * Step 2: validate the teacher code, pick exactly one active group,
     * then create a NEW independent student account + enrollment.
     *
     * A student may register with multiple teachers. Each registration
     * creates a completely independent account. The same mobile may be
     * reused; we never reject it.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_code' => ['required', 'string'],
            'group_id' => ['required', 'exists:study_groups,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'parent_mobile' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $teacher = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('teacher_code', strtoupper($validated['teacher_code']))
            ->first();

        if (! $teacher) {
            return back()->withErrors(['teacher_code' => 'كود المدرس غير صحيح'])->withInput();
        }

        $group = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->first();

        if (! $group) {
            return back()->withErrors(['group_id' => 'المجموعة غير متاحة'])->withInput();
        }

        // Prevent joining a full group.
        if ($group->isFull()) {
            return back()->withErrors(['group_id' => 'المجموعة ممتلئة ولا توجد بها مقاعد شاغرة'])->withInput();
        }

        // Prevent duplicate enrollment in the SAME group (same mobile).
        $exists = GroupEnrollment::where('group_id', $group->id)
            ->whereHas('student', fn ($q) => $q->where('mobile', $validated['mobile']))
            ->exists();

        if ($exists) {
            return back()->withErrors(['mobile' => 'رقم الجوال مسجل بالفعل في هذه المجموعة'])->withInput();
        }

        // Create a brand new independent student account.
        $student = User::create([
            'name' => $validated['name'],
            'email' => 'student_' . $validated['mobile'] . '@placeholder.local',
            'mobile' => $validated['mobile'],
            'parent_mobile' => $validated['parent_mobile'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'status' => 'active',
            'student_code' => User::generateStudentCode(),
        ]);

        $activeTerm = $teacher->studyGroups()
            ->where('id', $group->id)
            ->first()
            ?->term;

        $enrollment = GroupEnrollment::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'academic_term_id' => $activeTerm?->id,
            'card_id' => GroupEnrollment::generateCardId(),
            'qr_token' => GroupEnrollment::generateQrToken(),
            'status' => 'enrolled',
        ]);

        $enrollment->recordRegistered($teacher->name);

        TeacherActivity::log(
            $teacher->id,
            'student_account_created',
            'إنشاء حساب طالب',
            "تم إنشاء حساب للطالب {$student->name} (كود {$student->student_code})"
        );

        TeacherActivity::log(
            $teacher->id,
            'student_joined',
            'انضمام طالب جديد',
            "انضم الطالب {$student->name} إلى مجموعة {$group->title}"
        );

        // Log the new account in and set it as the active account.
        auth()->login($student);
        session(['active_student_id' => $student->id]);

        return redirect()->route('student.dashboard');
    }
}
