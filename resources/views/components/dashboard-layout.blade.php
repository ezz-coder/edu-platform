<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'لوحة التحكم' }} - {{ config('app.name', 'بيان') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="min-h-screen flex">
            <!-- Sidebar Overlay (mobile) -->
            <div id="sidebar-overlay" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity duration-200"></div>

            <!-- Sidebar -->
            <aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-64 bg-bg-sidebar border-l border-border-light transform transition-all duration-200 lg:translate-x-0 translate-x-full lg:static lg:inset-0 flex flex-col">
                <!-- Logo -->
                <div class="flex items-center justify-center h-16 border-b border-border-light flex-shrink-0">
                    <h1 class="text-2xl font-bold text-text-primary">بيان</h1>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto scrollbar-thin">
                    @if($currentUser->role === 'admin')
                        <x-sidebar-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                            <i class="bi bi-speedometer2 text-lg"></i>
                            <span>لوحة الأدمن</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.teacher-requests') }}" :active="request()->routeIs('admin.teacher-requests')">
                            <i class="bi bi-person-check text-lg"></i>
                            <span>طلبات المدرسين</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.teachers') }}" :active="request()->routeIs('admin.teachers') || request()->routeIs('admin.teachers.*')">
                            <i class="bi bi-people text-lg"></i>
                            <span>إدارة المدرسين</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                            <i class="bi bi-person-badge text-lg"></i>
                            <span>إدارة المستخدمين</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.activity-log') }}" :active="request()->routeIs('admin.activity-log')">
                            <i class="bi bi-activity text-lg"></i>
                            <span>سجل النشاط</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')">
                            <i class="bi bi-gear text-lg"></i>
                            <span>الإعدادات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.backup.index') }}" :active="request()->routeIs('admin.backup.*')">
                            <i class="bi bi-cloud-arrow-down text-lg"></i>
                            <span>النسخ الاحتياطي</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.health') }}" :active="request()->routeIs('admin.health')">
                            <i class="bi bi-heart-pulse text-lg"></i>
                            <span>صحة النظام</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('admin.errors') }}" :active="request()->routeIs('admin.errors')">
                            <i class="bi bi-exclamation-triangle text-lg"></i>
                            <span>الأخطاء</span>
                        </x-sidebar-link>
                    @elseif($currentUser->role === 'teacher')
                        <x-sidebar-link href="{{ route('teacher.dashboard') }}" :active="request()->routeIs('teacher.dashboard')">
                            <i class="bi bi-speedometer2 text-lg"></i>
                            <span>لوحة المدرس</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.groups.index') }}" :active="request()->routeIs('teacher.groups.*')">
                            <i class="bi bi-collection text-lg"></i>
                            <span>مجموعاتي</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.students.index') }}" :active="request()->routeIs('teacher.students.*')">
                            <i class="bi bi-people text-lg"></i>
                            <span>طلابي</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.lessons.index') }}" :active="request()->routeIs('teacher.lessons.*')">
                            <i class="bi bi-book text-lg"></i>
                            <span>الدروس</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.resources.index') }}" :active="request()->routeIs('teacher.resources.*')">
                            <i class="bi bi-folder2 text-lg"></i>
                            <span>المصادر</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.exams.index') }}" :active="request()->routeIs('teacher.exams.*')">
                            <i class="bi bi-file-earmark-text text-lg"></i>
                            <span>الامتحانات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.grades.index') }}" :active="request()->routeIs('teacher.grades.*')">
                            <i class="bi bi-bar-chart text-lg"></i>
                            <span>الدرجات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.reports.index') }}" :active="request()->routeIs('teacher.reports.*')">
                            <i class="bi bi-clipboard-data text-lg"></i>
                            <span>التقارير</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.calendar.index') }}" :active="request()->routeIs('teacher.calendar.*')">
                            <i class="bi bi-calendar3 text-lg"></i>
                            <span>التقويم</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.progress.index') }}" :active="request()->routeIs('teacher.progress.*')">
                            <i class="bi bi-graph-up-arrow text-lg"></i>
                            <span>تتبع التقدم</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.reservations.index') }}" :active="request()->routeIs('teacher.reservations.*')">
                            <i class="bi bi-calendar-check text-lg"></i>
                            <span>الحجوزات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.attendance.index') }}" :active="request()->routeIs('teacher.attendance.*')">
                            <i class="bi bi-clipboard-check text-lg"></i>
                            <span>الحضور</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.question-bank.index') }}" :active="request()->routeIs('teacher.question-bank.*')">
                            <i class="bi bi-question-circle text-lg"></i>
                            <span>بنك الأسئلة</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.assistants.index') }}" :active="request()->routeIs('teacher.assistants.*')">
                            <i class="bi bi-person-workspace text-lg"></i>
                            <span>المساعدون</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.lessons.recycle-bin') }}" :active="request()->routeIs('teacher.*.recycle-bin')">
                            <i class="bi bi-trash text-lg"></i>
                            <span>سلة المحذوفات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.messages.index') }}" :active="request()->routeIs('teacher.messages.*')">
                            <i class="bi bi-chat-dots text-lg"></i>
                            <span>الرسائل</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.announcements.index') }}" :active="request()->routeIs('teacher.announcements.*')">
                            <i class="bi bi-megaphone text-lg"></i>
                            <span>الإعلانات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('teacher.settings.index') }}" :active="request()->routeIs('teacher.settings.*')">
                            <i class="bi bi-gear text-lg"></i>
                            <span>الإعدادات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('profile.edit') }}" :active="request()->routeIs('profile.*')">
                            <i class="bi bi-person text-lg"></i>
                            <span>حسابي</span>
                        </x-sidebar-link>
                    @elseif($currentUser->role === 'student')
                        <x-sidebar-link href="{{ route('student.resources.home') }}" :active="request()->routeIs('student.resources.*')">
                            <i class="bi bi-grid-1x2 text-lg"></i>
                            <span>المصادر</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.exams.index') }}" :active="request()->routeIs('student.exams.*')">
                            <i class="bi bi-file-earmark-text text-lg"></i>
                            <span>الامتحانات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.reservations.index') }}" :active="request()->routeIs('student.reservations.*')">
                            <i class="bi bi-calendar-check text-lg"></i>
                            <span>الحجوزات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.messages.index') }}" :active="request()->routeIs('student.messages.*')">
                            <i class="bi bi-chat-dots text-lg"></i>
                            <span>الرسائل</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('notifications.index') }}" :active="request()->routeIs('notifications.*')">
                            <i class="bi bi-bell text-lg"></i>
                            <span>الإشعارات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.grades') }}" :active="request()->routeIs('student.grades')">
                            <i class="bi bi-bar-chart text-lg"></i>
                            <span>درجاتي</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.attendance') }}" :active="request()->routeIs('student.attendance')">
                            <i class="bi bi-clipboard-check text-lg"></i>
                            <span>حضوري</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.settings') }}" :active="request()->routeIs('student.settings')">
                            <i class="bi bi-gear text-lg"></i>
                            <span>الإعدادات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('student.account') }}" :active="request()->routeIs('student.account')">
                            <i class="bi bi-person text-lg"></i>
                            <span>حسابي</span>
                        </x-sidebar-link>
                    @elseif($currentUser->role === 'assistant')
                        <x-sidebar-link href="{{ route('assistant.dashboard') }}" :active="request()->routeIs('assistant.dashboard')">
                            <i class="bi bi-speedometer2 text-lg"></i>
                            <span>لوحة المساعد</span>
                        </x-sidebar-link>
                        @if($currentUser->canPerm('can_view_groups'))
                        <x-sidebar-link href="{{ route('assistant.groups.index') }}" :active="request()->routeIs('assistant.groups.*')">
                            <i class="bi bi-collection text-lg"></i>
                            <span>المجموعات</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_manage_students'))
                        <x-sidebar-link href="{{ route('assistant.students.index') }}" :active="request()->routeIs('assistant.students.*')">
                            <i class="bi bi-people text-lg"></i>
                            <span>طلاب المدرس</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_manage_lessons'))
                        <x-sidebar-link href="{{ route('assistant.lessons.index') }}" :active="request()->routeIs('assistant.lessons.*')">
                            <i class="bi bi-book text-lg"></i>
                            <span>الدروس</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_upload_resources'))
                        <x-sidebar-link href="{{ route('assistant.resources.index') }}" :active="request()->routeIs('assistant.resources.*')">
                            <i class="bi bi-folder2 text-lg"></i>
                            <span>المصادر</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_manage_exams'))
                        <x-sidebar-link href="{{ route('assistant.exams.index') }}" :active="request()->routeIs('assistant.exams.*')">
                            <i class="bi bi-file-earmark-text text-lg"></i>
                            <span>الامتحانات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('assistant.question-bank.index') }}" :active="request()->routeIs('assistant.question-bank.*')">
                            <i class="bi bi-question-circle text-lg"></i>
                            <span>بنك الأسئلة</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_manage_grades'))
                        <x-sidebar-link href="{{ route('assistant.grades.index') }}" :active="request()->routeIs('assistant.grades.*')">
                            <i class="bi bi-bar-chart text-lg"></i>
                            <span>الدرجات</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_manage_reservations'))
                        <x-sidebar-link href="{{ route('assistant.reservations.index') }}" :active="request()->routeIs('assistant.reservations.*')">
                            <i class="bi bi-calendar-check text-lg"></i>
                            <span>الحجوزات</span>
                        </x-sidebar-link>
                        @endif
                        @if($currentUser->canPerm('can_take_attendance'))
                        <x-sidebar-link href="{{ route('assistant.attendance.index') }}" :active="request()->routeIs('assistant.attendance.*')">
                            <i class="bi bi-clipboard-check text-lg"></i>
                            <span>الحضور</span>
                        </x-sidebar-link>
                        @endif
                        <x-sidebar-link href="{{ route('assistant.notifications.index') }}" :active="request()->routeIs('assistant.notifications.*')">
                            <i class="bi bi-bell text-lg"></i>
                            <span>الإشعارات</span>
                        </x-sidebar-link>
                        <x-sidebar-link href="{{ route('assistant.profile.edit') }}" :active="request()->routeIs('assistant.profile.*')">
                            <i class="bi bi-person text-lg"></i>
                            <span>حسابي</span>
                        </x-sidebar-link>
                    @endif
                </nav>

                <!-- User Info -->
                <div class="p-4 border-t border-border-light flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
                            {{ mb_substr($currentUser->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text-primary truncate">{{ $currentUser->name }}</p>
                            <p class="text-xs text-text-muted">{{ match($currentUser->role) {
                                'admin' => 'أدمن',
                                'teacher' => 'مدرس',
                                'student' => 'طالب',
                                'assistant' => 'مساعد',
                                default => '',
                            } }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Top Navigation -->
                <header class="bg-bg-card border-b border-border-light sticky top-0 z-30">
                    <div class="flex items-center justify-between px-4 lg:px-6 py-3">
                        <!-- Mobile Menu Button -->
                        <button id="mobile-menu-button" class="lg:hidden p-2 rounded-button text-text-secondary hover:bg-gray-100 transition-colors duration-150" aria-label="فتح القائمة">
                            <i class="bi bi-list text-xl"></i>
                        </button>

                        <!-- Page Title (Mobile) -->
                        <h2 class="text-lg font-semibold text-text-primary lg:hidden">{{ $header ?? '' }}</h2>

                        <!-- Spacer (Desktop) -->
                        <div class="hidden lg:block"></div>

                        <!-- Notification bell + Logout -->
                        <div class="flex items-center gap-2">
                            @if ($currentUser)
                                @if ($currentUser->role === 'teacher' || $currentUser->role === 'student' || $currentUser->role === 'assistant')
                                    @include('components.notification-bell')
                                @endif
                            @endif
                            @if ($currentUser)
                                @if($currentGuard === 'web')
                                    <form method="POST" action="{{ route('logout') }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-ghost btn-sm">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span class="hidden sm:inline">تسجيل الخروج</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('assistant.logout') }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-ghost btn-sm">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span class="hidden sm:inline">تسجيل الخروج</span>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 lg:p-8 overflow-y-auto pb-28 lg:pb-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Bottom Navigation (Mobile) -->
        @if ($currentUser)
        @if ($currentUser->role === 'student')
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-bg-card border-t border-border-light flex justify-around items-center py-2 shadow-dropdown">
            <a href="{{ route('student.resources.home') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('student.resources.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-grid-1x2 text-xl"></i><span>المصادر</span>
            </a>
            <a href="{{ route('student.exams.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('student.exams.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-file-earmark-text text-xl"></i><span>الامتحانات</span>
            </a>
            <a href="{{ route('student.reservations.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('student.reservations.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-calendar-check text-xl"></i><span>الحجوزات</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('notifications.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-bell text-xl"></i><span>الإشعارات</span>
            </a>
            <a href="{{ route('student.account') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('student.account') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-person text-xl"></i><span>حسابي</span>
            </a>
        </nav>
        @elseif ($currentUser->role === 'teacher')
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-bg-card border-t border-border-light flex justify-around items-center py-2 shadow-dropdown">
            <a href="{{ route('teacher.dashboard') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('teacher.dashboard') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-speedometer2 text-xl"></i><span>الرئيسية</span>
            </a>
            <a href="{{ route('teacher.groups.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('teacher.groups.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-collection text-xl"></i><span>المجموعات</span>
            </a>
            <a href="{{ route('teacher.resources.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('teacher.resources.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-folder2 text-xl"></i><span>المصادر</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('notifications.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-bell text-xl"></i><span>الإشعارات</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('profile.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-person text-xl"></i><span>حسابي</span>
            </a>
        </nav>
        @elseif ($currentUser->role === 'assistant')
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-bg-card border-t border-border-light flex justify-around items-center py-2 shadow-dropdown">
            <a href="{{ route('assistant.dashboard') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('assistant.dashboard') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-speedometer2 text-xl"></i><span>الرئيسية</span>
            </a>
            @if($currentUser->canPerm('can_view_groups'))
            <a href="{{ route('assistant.groups.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('assistant.groups.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-collection text-xl"></i><span>المجموعات</span>
            </a>
            @endif
            <a href="{{ route('assistant.notifications.index') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('assistant.notifications.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-bell text-xl"></i><span>الإشعارات</span>
            </a>
            <a href="{{ route('assistant.profile.edit') }}" class="flex flex-col items-center gap-0.5 text-xs {{ request()->routeIs('assistant.profile.*') ? 'text-primary-600' : 'text-text-muted' }} transition-colors duration-150">
                <i class="bi bi-person text-xl"></i><span>حسابي</span>
            </a>
        </nav>
        @endif
        @endif

        <script>
            function copyTeacherCode() {
                const code = document.getElementById('teacher-code');
                if (code) {
                    navigator.clipboard.writeText(code.textContent.trim()).then(() => {
                        const btn = document.querySelector('[onclick="copyTeacherCode()"]');
                        if (btn) {
                            const original = btn.innerHTML;
                            btn.innerHTML = '<i class="bi bi-check-lg"></i> تم النسخ';
                            setTimeout(() => btn.innerHTML = original, 2000);
                        }
                    });
                }
            }

            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                sidebar.classList.toggle('translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            }

            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', toggleSidebar);
            }
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }
        </script>

        @include('components.toast')
    </body>
</html>