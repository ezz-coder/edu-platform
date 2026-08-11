<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ActivityLog;
use App\Models\Assistant;
use App\Models\AttendanceSession;
use App\Models\Backup;
use App\Models\Exam;
use App\Models\GroupEnrollment;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyGroup;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminController extends Controller
{
    // ============================================================
    // MODULE 3 & 6: Dashboard + Monitoring
    // ============================================================
    public function dashboard(): View
    {
        $stats = [
            'teachers' => User::where('role', 'teacher')->count(),
            'active_teachers' => User::where('role', 'teacher')->where('status', 'active')->count(),
            'pending_teachers' => User::where('role', 'teacher')->where('status', 'pending')->count(),
            'suspended_teachers' => User::where('role', 'teacher')->where('status', 'suspended')->count(),
            'rejected_teachers' => User::where('role', 'teacher')->where('status', 'rejected')->count(),
            'assistants' => Assistant::count(),
            'students' => User::where('role', 'student')->count(),
            'inactive_students' => User::where('role', 'student')->where('status', 'inactive')->count(),
            'groups' => StudyGroup::count(),
            'active_groups' => StudyGroup::where('status', 'active')->count(),
            'resources' => Resource::count(),
            'reservations' => Reservation::count(),
            'exams' => Exam::count(),
            'published_exams' => Exam::where('status', 'published')->count(),
            'attendance_sessions' => AttendanceSession::count(),
            'enrollments' => GroupEnrollment::where('status', 'enrolled')->count(),
        ];

        $latestTerm = AcademicTerm::latest()->first();

        return view('admin.dashboard', [
            'header' => 'لوحة التحكم',
            'stats' => $stats,
            'latestTerm' => $latestTerm,
        ]);
    }

    // ============================================================
    // MODULE 1: Teacher Registration Approval
    // ============================================================
    public function teacherRequests(): View
    {
        $requests = User::where('role', 'teacher')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.teachers.requests', [
            'header' => 'طلبات تسجيل المدرسين',
            'requests' => $requests,
        ]);
    }

    public function approveTeacher(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'teacher' || $user->status !== 'pending') {
            return back()->with('error', 'لا يمكن الموافقة على هذا الطلب');
        }

        $user->update(['status' => 'active']);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_approved',
            'category' => 'Teacher',
            'description' => 'تم الموافقة على المدرس: ' . $user->name,
        ]);

        return back()->with('success', 'تم الموافقة على المدرس بنجاح، يمكنه تسجيل الدخول الآن');
    }

    public function rejectTeacher(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'teacher' || $user->status !== 'pending') {
            return back()->with('error', 'لا يمكن رفض هذا الطلب');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_rejected',
            'category' => 'Teacher',
            'description' => 'تم رفض المدرس: ' . $user->name . ($validated['rejection_reason'] ? ' - السبب: ' . $validated['rejection_reason'] : ''),
        ]);

        return back()->with('success', 'تم رفض طلب المدرس');
    }

    // ============================================================
    // MODULE 2: Teacher Management
    // ============================================================
    public function teachers(): View
    {
        $teachers = User::where('role', 'teacher')
            ->withCount('studyGroups')
            ->latest()
            ->paginate(20);

        return view('admin.teachers.index', [
            'header' => 'إدارة المدرسين',
            'teachers' => $teachers,
        ]);
    }

    public function activateTeacher(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->with('error', 'المستخدم ليس مدرساً');
        }

        $user->update(['status' => 'active']);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_activated',
            'category' => 'Teacher',
            'description' => 'تم تفعيل المدرس: ' . $user->name,
        ]);

        return back()->with('success', 'تم تفعيل المدرس بنجاح');
    }

    public function deactivateTeacher(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->with('error', 'المستخدم ليس مدرساً');
        }

        $user->update(['status' => 'inactive']);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_deactivated',
            'category' => 'Teacher',
            'description' => 'تم إلغاء تفعيل المدرس: ' . $user->name,
        ]);

        return back()->with('success', 'تم إلغاء تفعيل المدرس');
    }

    public function suspendTeacher(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->with('error', 'المستخدم ليس مدرساً');
        }

        $user->update(['status' => 'suspended']);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_suspended',
            'category' => 'Teacher',
            'description' => 'تم تعليق المدرس: ' . $user->name,
        ]);

        return back()->with('success', 'تم تعليق المدرس');
    }

    public function restoreTeacher(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->with('error', 'المستخدم ليس مدرساً');
        }

        $user->update(['status' => 'active']);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_restored',
            'category' => 'Teacher',
            'description' => 'تم استعادة المدرس: ' . $user->name,
        ]);

        return back()->with('success', 'تم استعادة المدرس بنجاح');
    }

    public function softDeleteTeacher(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->with('error', 'المستخدم ليس مدرساً');
        }

        $user->update(['status' => 'deleted']);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'teacher_deleted',
            'category' => 'Teacher',
            'description' => 'تم حذف المدرس: ' . $user->name,
        ]);

        return back()->with('success', 'تم حذف المدرس');
    }

    // ============================================================
    // MODULE 4: Activity Log
    // ============================================================
    public function activityLog(Request $request): View
    {
        $query = ActivityLog::latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('actor_type')) {
            $query->where('actor_type_label', $request->actor_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(30);

        $categories = [
            'Authentication', 'Teacher', 'Assistant', 'Student', 'Groups',
            'Attendance', 'Resources', 'Reservations', 'Exams', 'Academic Terms', 'Administration',
        ];

        $actorTypes = ['أدمن', 'مدرس', 'طالب', 'مساعد'];

        return view('admin.activities.index', [
            'header' => 'سجل نشاط المنصة',
            'logs' => $logs,
            'categories' => $categories,
            'actorTypes' => $actorTypes,
        ]);
    }

    // ============================================================
    // MODULE 5: System Settings
    // ============================================================
    public function settings(): View
    {
        $settings = [
            'platform_name' => SystemSetting::getValue('platform_name', config('app.name')),
            'platform_logo' => SystemSetting::getValue('platform_logo'),
            'primary_color' => SystemSetting::getValue('primary_color', '#2563eb'),
            'secondary_color' => SystemSetting::getValue('secondary_color', '#6b7280'),
            'support_phone' => SystemSetting::getValue('support_phone'),
            'support_email' => SystemSetting::getValue('support_email'),
            'platform_description' => SystemSetting::getValue('platform_description'),
            'welcome_message' => SystemSetting::getValue('welcome_message'),
            'footer_text' => SystemSetting::getValue('footer_text'),
        ];

        return view('admin.settings.index', [
            'header' => 'إعدادات المنصة',
            'settings' => $settings,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:255'],
            'platform_logo' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'platform_description' => ['nullable', 'string', 'max:1000'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::setValue($key, $value);
        }

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'settings_updated',
            'category' => 'Administration',
            'description' => 'تم تحديث إعدادات المنصة',
        ]);

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }

    // ============================================================
    // MODULE 8: Manual Backup
    // ============================================================
    public function backupIndex(): View
    {
        $backups = Backup::latest()->paginate(20);

        return view('admin.backup.index', [
            'header' => 'النسخ الاحتياطي',
            'backups' => $backups,
        ]);
    }

    public function createBackup(): RedirectResponse
    {
        // Fail gracefully if mysqldump is not available
        $mysqldumpAvailable = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            ? $this->commandExists('mysqldump')
            : $this->commandExists('mysqldump');

        if (!$mysqldumpAvailable) {
            return back()->with('error', 'mysqldump غير متوفر على الخادم. الرجاء تثبيته أولاً.');
        }

        $filename = 'backup-' . now()->format('Y-m-d-H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }

        $db = config('database.connections.mysql');

        $dumpCommand = sprintf(
            'mysqldump --host=%s --user=%s %s %s > "%s" 2>&1',
            escapeshellarg($db['host']),
            escapeshellarg($db['username']),
            $db['password'] ? '--password=' . escapeshellarg($db['password']) : '',
            escapeshellarg($db['database']),
            $path
        );

        $output = null;
        $resultCode = null;
        exec($dumpCommand, $output, $resultCode);

        if ($resultCode !== 0) {
            return back()->with('error', 'فشل إنشاء النسخة الاحتياطية. تأكد من توفر mysqldump');
        }

        $size = File::exists($path) ? File::size($path) : 0;

        Backup::create([
            'filename' => $filename,
            'path' => 'backups/' . $filename,
            'size' => $size,
            'type' => 'manual',
        ]);

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'backup_created',
            'category' => 'Administration',
            'description' => 'تم إنشاء نسخة احتياطية: ' . $filename,
        ]);

        return back()->with('success', 'تم إنشاء النسخة الاحتياطية بنجاح');
    }

    /**
     * Check if a shell command exists.
     */
    private function commandExists(string $command): bool
    {
        $output = [];
        $returnCode = 0;
        $checkCommand = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            ? "where {$command}"
            : "which {$command}";

        exec($checkCommand . ' 2>/dev/null', $output, $returnCode);

        return $returnCode === 0;
    }

    public function downloadBackup(Backup $backup)
    {
        $path = storage_path('app/' . $backup->path);

        if (!File::exists($path)) {
            return back()->with('error', 'الملف غير موجود');
        }

        return response()->download($path, $backup->filename);
    }

    public function deleteBackup(Backup $backup): RedirectResponse
    {
        $path = storage_path('app/' . $backup->path);

        if (File::exists($path)) {
            File::delete($path);
        }

        $backup->delete();

        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => User::class,
            'actor_type_label' => 'أدمن',
            'action' => 'backup_deleted',
            'category' => 'Administration',
            'description' => 'تم حذف النسخة الاحتياطية: ' . $backup->filename,
        ]);

        return back()->with('success', 'تم حذف النسخة الاحتياطية بنجاح');
    }

    // ============================================================
    // MODULE 9: System Health
    // ============================================================
    public function health(): View
    {
        $health = [
            'app_version' => config('app.version', '1.0.0'),
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
            'database_status' => $this->checkDatabase(),
            'storage_writable' => is_writable(storage_path()),
            'cache_status' => $this->checkCache(),
            'last_backup' => Backup::latest()->first()?->created_at,
            'environment' => app()->environment(),
        ];

        return view('admin.health.index', [
            'header' => 'صحة النظام',
            'health' => $health,
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'متصل';
        } catch (\Exception $e) {
            return 'غير متصل';
        }
    }

    private function checkCache(): string
    {
        try {
            $testValue = 'health_check_' . time();
            Cache::store()->put('__health_check_key__', $testValue, 60);
            $retrieved = Cache::store()->get('__health_check_key__');
            Cache::store()->forget('__health_check_key__');
            return ($retrieved === $testValue) ? 'يعمل' : 'غير متاح';
        } catch (\Exception $e) {
            return 'غير متاح';
        }
    }

    // ============================================================
    // MODULE 10: Recent Errors
    // ============================================================
    public function errors(): View
    {
        $logFile = storage_path('logs/laravel.log');
        $errors = [];

        if (File::exists($logFile)) {
            $lines = File::lines($logFile)->reverse()->take(500);

            foreach ($lines as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\.(\w+):\s*(.+?)\s+in\s+(.+?):(\d+)/', $line, $matches)) {
                    $errors[] = [
                        'date' => $matches[1],
                        'severity' => $this->parseSeverity($matches[2]),
                        'message' => $matches[3],
                        'file' => $matches[4],
                        'line' => $matches[5],
                    ];
                } elseif (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?(\w+):\s*(.+)/', $line, $matches)) {
                    $errors[] = [
                        'date' => $matches[1],
                        'severity' => $this->parseSeverity($matches[2]),
                        'message' => $matches[3],
                        'file' => '-',
                        'line' => '-',
                    ];
                }

                if (count($errors) >= 100) {
                    break;
                }
            }
        }

        return view('admin.errors.index', [
            'header' => 'الأخطاء الأخيرة',
            'errors' => $errors,
        ]);
    }

    private function parseSeverity(string $level): string
    {
        return match (strtoupper($level)) {
            'ERROR' => 'خطأ',
            'CRITICAL' => 'حرج',
            'WARNING' => 'تحذير',
            'NOTICE' => 'ملاحظة',
            'INFO' => 'معلومات',
            default => $level,
        };
    }
}