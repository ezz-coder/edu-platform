<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\AssistantPermission;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 18 — Teacher management of his assistants.
 *
 * Security: every assistant is scoped to the authenticated teacher and an
 * inactive (removed) assistant cannot act. The teacher may only edit/remove
 * his own assistants.
 */
class TeacherAssistantController extends Controller
{
    private const PERMISSION_KEYS = [
        'can_take_attendance' => 'تسجيل الحضور',
        'can_manage_students' => 'إدارة الطلاب',
        'can_upload_resources' => 'رفع الموارد',
        'can_manage_reservations' => 'إدارة الحجوزات',
        'can_restore_students' => 'استرجاع الطلاب',
        'can_view_groups' => 'مشاهدة المجموعات',
        'can_manage_lessons' => 'إدارة الدروس',
        'can_manage_exams' => 'إدارة الامتحانات',
    ];

    public function index(): View
    {
        $assistants = Assistant::where('teacher_id', auth()->id())
            ->with('permissions')
            ->latest()
            ->get();

        return view('teacher.assistants.index', [
            'header' => 'المساعدون',
            'assistants' => $assistants,
            'permissionLabels' => self::PERMISSION_KEYS,
        ]);
    }

    public function create(): View
    {
        return view('teacher.assistants.create', [
            'header' => 'إضافة مساعد',
            'permissionLabels' => self::PERMISSION_KEYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:assistants,username'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $assistant = Assistant::create([
            'teacher_id' => auth()->id(),
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'status' => 'active',
        ]);

        // Only the explicitly selected permissions are stored.
        $permissionData = ['assistant_id' => $assistant->id];
        foreach (array_keys(self::PERMISSION_KEYS) as $key) {
            $permissionData[$key] = $request->boolean($key);
        }
        AssistantPermission::create($permissionData);

        TeacherActivity::log(
            auth()->id(),
            'assistant_created',
            'إضافة مساعد',
            "تم إضافة المساعد {$assistant->name}"
        );

        // Teacher gets one "assistant added" notification.
        NotificationHelper::notifyTeacher(
            auth()->id(),
            'assistant',
            'تمت إضافة مساعد',
            "تمت إضافة المساعد {$assistant->name}.",
            route('teacher.assistants.index')
        );

        // Assistant gets his own scoped notification.
        NotificationHelper::notifyAssistant(
            $assistant,
            'assistant',
            'تم تفعيل حسابك',
            "تم إنشاء حسابك كمساعد لدى المدرس. صلاحياتك محددة حسب ما يسمح به.",
            route('assistant.dashboard')
        );

        return redirect()->route('teacher.assistants.index')
            ->with('success', 'تم إضافة المساعد بنجاح');
    }

    public function edit(Assistant $assistant): View
    {
        $this->authorizeAssistant($assistant);

        return view('teacher.assistants.edit', [
            'header' => 'تعديل صلاحيات المساعد',
            'assistant' => $assistant,
            'permissions' => $assistant->permissions,
            'permissionLabels' => self::PERMISSION_KEYS,
        ]);
    }

    public function update(Request $request, Assistant $assistant): RedirectResponse
    {
        $this->authorizeAssistant($assistant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:assistants,username,' . $assistant->id],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $update = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'phone' => $validated['phone'],
        ];

        if (!empty($validated['password'])) {
            $update['password'] = $validated['password'];
        }

        $assistant->update($update);

        $permissionData = [];
        foreach (array_keys(self::PERMISSION_KEYS) as $key) {
            $permissionData[$key] = $request->boolean($key);
        }
        $assistant->permissions()->updateOrCreate(
            ['assistant_id' => $assistant->id],
            $permissionData
        );

        TeacherActivity::log(
            auth()->id(),
            'assistant_updated',
            'تعديل مساعد',
            "تم تعديل صلاحيات المساعد {$assistant->name}"
        );

        // Assistant gets a scoped "permissions changed" notification.
        NotificationHelper::notifyAssistant(
            $assistant,
            'assistant',
            'تم تغيير صلاحياتك',
            "قام المدرس بتحديث صلاحياتك كمساعد. راجع حسابك لمعرفة ما يمكنك الوصول إليه.",
            route('assistant.dashboard')
        );

        return redirect()->route('teacher.assistants.index')
            ->with('success', 'تم تحديث بيانات المساعد بنجاح');
    }

    /**
     * Disable (deactivate) an assistant — keeps the record for history.
     */
    public function disable(Assistant $assistant): RedirectResponse
    {
        $this->authorizeAssistant($assistant);

        $assistant->update(['status' => 'inactive']);

        TeacherActivity::log(
            auth()->id(),
            'assistant_disabled',
            'تعطيل مساعد',
            "تم تعطيل المساعد {$assistant->name}"
        );

        NotificationHelper::notifyTeacher(
            auth()->id(),
            'assistant',
            'تم تعطيل مساعد',
            "تم تعطيل المساعد {$assistant->name}.",
            route('teacher.assistants.index')
        );
        NotificationHelper::notifyAssistant(
            $assistant,
            'assistant',
            'تم تعطيل حسابك',
            "قام المدرس بتعطيل حسابك كمساعد. لن تتمكن من الدخول.",
            route('assistant.dashboard')
        );

        return redirect()->route('teacher.assistants.index')
            ->with('success', 'تم تعطيل المساعد. لن يتمكن من الدخول.');
    }

    /**
     * Re-enable a previously disabled assistant.
     */
    public function enable(Assistant $assistant): RedirectResponse
    {
        $this->authorizeAssistant($assistant);

        $assistant->update(['status' => 'active']);

        NotificationHelper::notifyTeacher(
            auth()->id(),
            'assistant',
            'تم تفعيل مساعد',
            "تم تفعيل المساعد {$assistant->name} مرة أخرى.",
            route('teacher.assistants.index')
        );
        NotificationHelper::notifyAssistant(
            $assistant,
            'assistant',
            'تم تفعيل حسابك',
            "قام المدرس بتفعيل حسابك كمساعد مرة أخرى.",
            route('assistant.dashboard')
        );

        return redirect()->route('teacher.assistants.index')
            ->with('success', 'تم تفعيل المساعد مرة أخرى.');
    }

    /**
     * "Remove" an assistant — soft removal: status = inactive (keep history).
     */
    public function destroy(Assistant $assistant): RedirectResponse
    {
        $this->authorizeAssistant($assistant);

        $assistant->update(['status' => 'inactive']);

        TeacherActivity::log(
            auth()->id(),
            'assistant_removed',
            'حذف مساعد',
            "تم حذف المساعد {$assistant->name}"
        );

        NotificationHelper::notifyTeacher(
            auth()->id(),
            'assistant',
            'تم حذف مساعد',
            "تم حذف المساعد {$assistant->name}.",
            route('teacher.assistants.index')
        );
        NotificationHelper::notifyAssistant(
            $assistant,
            'assistant',
            'تم إنهاء صلاحيتك',
            "قام المدرس بإنهاء صلاحيتك كمساعد.",
            route('assistant.dashboard')
        );

        return redirect()->route('teacher.assistants.index')
            ->with('success', 'تم حذف المساعد بنجاح.');
    }

    private function authorizeAssistant(Assistant $assistant): void
    {
        if ($assistant->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المساعد');
        }
    }
}