<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>بيان — منصة إدارة المدرسين</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bg-primary text-text-primary" style="font-family: 'Cairo', sans-serif;">
    <div class="min-h-screen flex flex-col">
        {{-- Hero Section --}}
        <div class="flex-1 flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-4xl mx-auto text-center">
                {{-- Logo / Brand --}}
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 text-primary-600 mb-4 mx-auto shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-text-primary sm:text-5xl">
                        بيان
                    </h1>
                    <p class="mt-3 text-lg text-text-secondary max-w-xl mx-auto leading-relaxed">
                        منصة عربية متكاملة تساعد المدرسين على إدارة مجموعاتهم وطلابهم بكل سهولة وبساطة.
                    </p>
                </div>

                {{-- Role Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mt-10">
                    {{-- Teacher Card --}}
                    <a href="{{ route('login') }}" class="group block bg-bg-card rounded-card p-6 sm:p-7 shadow-card hover:shadow-card-hover border border-border-light hover:border-primary-200 transition-all duration-200">
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center mb-4 group-hover:bg-primary-100 group-hover:scale-110 transition-all duration-200">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-text-primary group-hover:text-primary-600 transition-colors duration-200">
                                الدخول كمدرس
                            </h3>
                            <p class="mt-2 text-sm text-text-muted leading-relaxed">
                                لإدارة المجموعات والطلاب والاختبارات والحضور
                            </p>
                        </div>
                    </a>

                    {{-- Student Card --}}
                    <a href="{{ route('register.student') }}" class="group block bg-bg-card rounded-card p-6 sm:p-7 shadow-card hover:shadow-card-hover border border-border-light hover:border-success-200 transition-all duration-200">
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full bg-success-50 text-success-600 flex items-center justify-center mb-4 group-hover:bg-success-100 group-hover:scale-110 transition-all duration-200">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" transform="rotate(0)"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" transform="rotate(0)"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10v4l9 5 9-5v-4"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-text-primary group-hover:text-success-600 transition-colors duration-200">
                                الدخول كطالب
                            </h3>
                            <p class="mt-2 text-sm text-text-muted leading-relaxed">
                                لمتابعة المجموعات والموارد والاختبارات والنتائج
                            </p>
                        </div>
                    </a>

                    {{-- Assistant Card --}}
                    <a href="{{ route('assistant.login') }}" class="group block bg-bg-card rounded-card p-6 sm:p-7 shadow-card hover:shadow-card-hover border border-border-light hover:border-emerald-200 transition-all duration-200">
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 group-hover:bg-emerald-100 group-hover:scale-110 transition-all duration-200">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-text-primary group-hover:text-emerald-600 transition-colors duration-200">
                                الدخول كمساعد
                            </h3>
                            <p class="mt-2 text-sm text-text-muted leading-relaxed">
                                لمساعدة المدرس في إدارة المجموعات والمهام
                            </p>
                        </div>
                    </a>
                </div>

                {{-- Footer Links --}}
                <div class="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm">
                    <a href="{{ route('register.teacher') }}" class="text-primary-600 hover:text-primary-700 font-medium transition-colors duration-150">
                        ليس لديك حساب؟ سجل كمدرس
                    </a>
                    <span class="text-border-light hidden sm:inline">|</span>
                    <a href="{{ route('register.student') }}" class="text-primary-600 hover:text-primary-700 font-medium transition-colors duration-150">
                        سجل كطالب
                    </a>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="py-6 text-center">
            <p class="text-xs text-text-muted">
                &copy; {{ date('Y') }} بيان — منصة إدارة المدرسين. جميع الحقوق محفوظة.
            </p>
        </footer>
    </div>
</body>
</html>