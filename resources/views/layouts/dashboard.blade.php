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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50" style="font-family: 'Cairo', sans-serif;">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-64 bg-white border-l border-gray-200 transform transition-transform duration-300 lg:translate-x-0 -translate-x-full lg:static lg:inset-0">
                <div class="flex flex-col h-full">
                    <!-- Logo -->
                    <div class="flex items-center justify-center h-16 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-800">بيان</h1>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                        @if(auth()->user()->role === 'admin')
                            <x-sidebar-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                                لوحة الأدمن
                            </x-sidebar-link>
                            <x-sidebar-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                                إدارة المستخدمين
                            </x-sidebar-link>
                            <x-sidebar-link href="{{ route('admin.groups.index') }}" :active="request()->routeIs('admin.groups.*')">
                                إدارة المجموعات
                            </x-sidebar-link>
                        @elseif(auth()->user()->role === 'teacher')
                            <x-sidebar-link href="{{ route('teacher.dashboard') }}" :active="request()->routeIs('teacher.dashboard')">
                                لوحة المدرس
                            </x-sidebar-link>
                            <x-sidebar-link href="{{ route('teacher.groups.index') }}" :active="request()->routeIs('teacher.groups.*')">
                                مجموعاتي
                            </x-sidebar-link>
                        @elseif(auth()->user()->role === 'student')
                            <x-sidebar-link href="{{ route('student.dashboard') }}" :active="request()->routeIs('student.dashboard')">
                                لوحة الطالب
                            </x-sidebar-link>
                            <x-sidebar-link href="{{ route('student.groups.index') }}" :active="request()->routeIs('student.groups.*')">
                                المجموعات المتاحة
                            </x-sidebar-link>
                        @endif
                    </nav>

                    <!-- User Info -->
                    <div class="p-4 border-t border-gray-200">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ match(auth()->user()->role) {
                                    'admin' => 'أدمن',
                                    'teacher' => 'مدرس',
                                    'student' => 'طالب',
                                    default => '',
                                } }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Top Navigation -->
                <header class="bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between px-4 py-4">
                        <!-- Mobile Menu Button -->
                        <button id="mobile-menu-button" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <!-- Page Title -->
                        <h2 class="text-xl font-semibold text-gray-800">{{ $header ?? '' }}</h2>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                                تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-6 overflow-y-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

        <script>
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            }

            mobileMenuButton.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        </script>
    </body>
</html>
