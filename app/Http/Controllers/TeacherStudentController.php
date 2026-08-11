<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Models\User;
use App\Utilities\NotificationHelper;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeacherStudentController extends Controller
{
    /**
     * List the teacher's students with search and filter support,
     * plus pending transfer requests.
     */
    public function index(Request $request)
    {
        $teacherId = auth()->id();

        $groups = StudyGroup::where('teacher_id', $teacherId)
            ->with(['students.student'])
            ->latest()
            ->get();

        $transferRequests = GroupEnrollment::where('status', 'pending_transfer')
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->with(['student', 'group', 'requestedGroup'])
            ->get();

        // Build a flat, searchable/filterable list of enrollments.
        $enrollments = GroupEnrollment::query()
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->with(['student', 'group'])
            ->when($request->group_id, fn ($q) => $q->where('group_id', $request->group_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('student_code', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('teacher.students.index', [
            'header' => 'طلابي',
            'groups' => $groups,
            'transferRequests' => $transferRequests,
            'enrollments' => $enrollments,
            'filters' => $request->only(['search', 'group_id', 'status']),
        ]);
    }

    /**
     * Show the immutable enrollment history for a single enrollment.
     * History can never be edited or deleted.
     */
    public function history(GroupEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($enrollment);

        $enrollment->load(['student', 'group', 'histories' => fn ($q) => $q->with(['fromGroup', 'toGroup', 'student'])->latest('changed_at')]);

        return view('teacher.students.history', [
            'header' => 'سجل التحويلات - ' . $enrollment->student->name,
            'enrollment' => $enrollment,
        ]);
    }

    /**
     * Move a student manually to another of the teacher's groups.
     */
    public function move(Request $request, GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
        ]);

        $target = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        if ($target->status !== 'active') {
            return back()->withErrors(['group_id' => 'لا يمكن نقل الطالب إلى مجموعة غير نشطة']);
        }

        if ($target->isFull()) {
            return back()->withErrors(['group_id' => 'المجموعة الهدف ممتلئة']);
        }

        $fromTitle = $enrollment->group->title;
        $enrollment->transferTo($target->id, auth()->user()->name);

        TeacherActivity::log(
            auth()->id(),
            'student_transferred',
            'نقل طالب',
            "تم نقل الطالب {$enrollment->student->name} من {$fromTitle} إلى {$target->title}"
        );

        return back()->with('success', 'تم نقل الطالب بنجاح');
    }

    /**
     * Bulk move selected enrollments to a destination group.
     * Every movement is recorded individually in Enrollment History.
     */
    public function bulkMove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'exists:group_enrollments,id'],
            'group_id' => ['required', 'exists:study_groups,id'],
        ]);

        $target = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        if ($target->status !== 'active') {
            return back()->withErrors(['group_id' => 'لا يمكن نقل الطالب إلى مجموعة غير نشطة']);
        }

        if ($target->isFull()) {
            return back()->withErrors(['group_id' => 'المجموعة الهدف ممتلئة']);
        }

        $enrollments = GroupEnrollment::whereIn('id', $validated['enrollment_ids'])
            ->whereHas('group', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->get();

        $moved = 0;
        foreach ($enrollments as $enrollment) {
            if ($enrollment->group_id === $target->id) {
                continue;
            }
            $enrollment->transferTo($target->id, auth()->user()->name);
            $moved++;
        }

        if ($moved > 0) {
            TeacherActivity::log(
                auth()->id(),
                'student_bulk_transferred',
                'نقل جماعي',
                "تم نقل {$moved} طالب إلى مجموعة {$target->title}"
            );
        }

        return back()->with('success', "تم نقل {$moved} طالب بنجاح");
    }

    /**
     * Remove a student from the group (soft removal via status).
     */
    public function remove(GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        $name = $enrollment->student->name;
        $groupTitle = $enrollment->group->title;
        $enrollment->markRemovedByTeacher(auth()->user()->name);

        TeacherActivity::log(
            auth()->id(),
            'student_removed',
            'إزالة طالب',
            "تم إزالة الطالب {$name} من مجموعة {$groupTitle}"
        );

        // Student Removed notification uses the RESERVED BLACK color.
        Notification::notify(
            auth()->id(),
            'تم إزالة الطالب',
            "تم إزالة الطالب {$name} من مجموعة {$groupTitle}. لا يزال بإمكانك الوصول لموادك السابقة، والمواد الجديدة مخفية حتى يسترجع مدرسك تسجيلك.",
            $enrollment->student_id,
            $enrollment->group_id,
            'student_removed',
            route('student.dashboard')
        );

        return back()->with('success', 'تم إزالة الطالب من المجموعة');
    }

    /**
     * Bulk remove selected enrollments (manual removal).
     */
    public function bulkRemove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'exists:group_enrollments,id'],
        ]);

        $enrollments = GroupEnrollment::whereIn('id', $validated['enrollment_ids'])
            ->whereHas('group', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->get();

        $count = 0;
        foreach ($enrollments as $enrollment) {
            if (! $enrollment->isInactive()) {
                $enrollment->markRemovedByTeacher(auth()->user()->name);
                $count++;
            }
        }

        if ($count > 0) {
            TeacherActivity::log(
                auth()->id(),
                'student_bulk_removed',
                'إزالة جماعية',
                "تم إزالة {$count} طالب من مجموعات المدرس"
            );
        }

        return back()->with('success', "تم إزالة {$count} طالب بنجاح");
    }

    /**
     * Bulk restore selected enrollments (only inactive ones).
     */
    public function bulkRestore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'exists:group_enrollments,id'],
        ]);

        $enrollments = GroupEnrollment::whereIn('id', $validated['enrollment_ids'])
            ->whereHas('group', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->get();

        $count = 0;
        foreach ($enrollments as $enrollment) {
            if ($enrollment->isInactive()) {
                $enrollment->restoreActive(auth()->user()->name);

                Notification::notify(
                    auth()->id(),
                    'تم استرجاع تسجيلك',
                    "تم استرجاعك إلى مجموعة {$enrollment->group->title}. يمكنك الآن مشاهدة جميع المواد الجديدة.",
                    $enrollment->student_id,
                    $enrollment->group_id,
                    'group',
                    route('student.dashboard')
                );

                $count++;
            }
        }

        if ($count > 0) {
            TeacherActivity::log(
                auth()->id(),
                'student_bulk_restored',
                'استرجاع جماعي',
                "تم استرجاع {$count} طالب إلى مجموعات المدرس"
            );
        }

        return back()->with('success', "تم استرجاع {$count} طالب بنجاح");
    }

    /**
     * Approve a pending transfer request.
     */
    public function approveTransfer(GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        if ($enrollment->status !== 'pending_transfer' || ! $enrollment->requested_group_id) {
            abort(404);
        }

        $target = $enrollment->requestedGroup;

        if ($target->status !== 'active') {
            return back()->withErrors(['group_id' => 'المجموعة الهدف غير نشطة']);
        }

        if ($target->isFull()) {
            return back()->withErrors(['group_id' => 'المجموعة الهدف ممتلئة']);
        }

        $name = $enrollment->student->name;
        $fromTitle = $enrollment->group->title;
        $enrollment->transferTo($enrollment->requested_group_id, auth()->user()->name);

        TeacherActivity::log(
            auth()->id(),
            'student_transferred',
            'قبول طلب نقل',
            "تم قبول نقل الطالب {$name} من {$fromTitle} إلى {$target->title}"
        );

        return back()->with('success', 'تم قبول طلب النقل');
    }

    /**
     * Reject a pending transfer request.
     */
    public function rejectTransfer(GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        if ($enrollment->status !== 'pending_transfer') {
            abort(404);
        }

        $name = $enrollment->student->name;
        $enrollment->update([
            'requested_group_id' => null,
            'status' => 'enrolled',
        ]);

        TeacherActivity::log(
            auth()->id(),
            'transfer_rejected',
            'رفض طلب نقل',
            "تم رفض طلب نقل الطالب {$name}"
        );

        return back()->with('success', 'تم رفض طلب النقل');
    }

    private function authorizeEnrollment(GroupEnrollment $enrollment): void
    {
        if ($enrollment->group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الطالب');
        }
    }

    private function authorizeGroup(StudyGroup $group): void
    {
        if ($group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المجموعة');
        }
    }

    /**
     * Add a brand-new student directly to one of the teacher's groups.
     * Reuses the same independent account + enrollment model as self-registration.
     */
    public function store(Request $request, StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        if ($group->status !== 'active') {
            return back()->with('error', 'لا يمكن إضافة طالب إلى مجموعة غير نشطة')->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'parent_mobile' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($group->isFull()) {
            return back()->with('error', 'المجموعة ممتلئة ولا توجد بها مقاعد شاغرة')->withInput();
        }

        $student = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'parent_mobile' => $validated['parent_mobile'],
            'password' => $validated['password'],
            'role' => 'student',
            'status' => 'active',
            'student_code' => User::generateStudentCode(),
        ]);

        $enrollment = GroupEnrollment::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'academic_term_id' => $group->academic_term_id,
            'card_id' => GroupEnrollment::generateCardId(),
            'qr_token' => GroupEnrollment::generateQrToken(),
            'status' => 'enrolled',
        ]);
        $enrollment->recordRegistered(auth()->user()->name);

        TeacherActivity::log(
            auth()->id(),
            'student_account_created',
            'إنشاء حساب طالب',
            "تمت إضافة الطالب {$student->name} إلى مجموعة {$group->title}"
        );

        return redirect()
            ->route('teacher.groups.show', $group)
            ->with('success', 'تم إضافة الطالب إلى المجموعة بنجاح');
    }

    /**
     * Restore a student previously removed (manually or due to absence).
     */
    public function restore(GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        if (! $enrollment->isInactive()) {
            return back()->with('info', 'الطالب ليس مزالاً من المجموعة.');
        }

        $enrollment->restoreActive(auth()->user()->name);

        TeacherActivity::log(
            auth()->id(),
            'student_restored',
            'استرجاع طالب',
            "تم استرجاع الطالب {$enrollment->student->name} إلى مجموعة {$enrollment->group->title}"
        );

        // Notify the restored student (uses the green color via group type).
        Notification::notify(
            auth()->id(),
            'تم استرجاع تسجيلك',
            "تم استرجاعك إلى مجموعة {$enrollment->group->title}. يمكنك الآن مشاهدة جميع المواد الجديدة.",
            $enrollment->student_id,
            $enrollment->group_id,
            'group',
            route('student.dashboard')
        );

        return back()->with('success', 'تم استرجاع الطالب إلى المجموعة');
    }
}