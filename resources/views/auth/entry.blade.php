<x-guest-layout>
    @if (session('auth_entry_redirect'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('auth_entry_redirect') }}
        </div>
    @endif

    <!-- Check if user is already authenticated -->
    @if (auth()->check())
        <div class="bg-bg-card p-6 rounded-lg mb-8">
            <h3 class="text-lg font-bold text-text-primary mb-4">{{ __('أنت بالفعل مسجل دخول') }}</h3>
            
            <div class="grid grid-cols-2 gap-4">
                @if (auth()->user()->role === 'teacher')
                    <a href="{{ route('teacher.dashboard') }}" class="btn-primary w-full">
                        {{ __('الذهاب إلى لوحة المدرس') }}
                    </a>
                @elseif (auth()->user()->role === 'student')
                    <a href="{{ route('student.dashboard') }}" class="btn-primary w-full">
                        {{ __('الذهاب إلى لوحة الطالب') }}
                    </a>
                @elseif (auth()->user()->role === 'assistant')
                    <a href="{{ route('assistant.dashboard') }}" class="btn-primary w-full">
                        {{ __('الذهاب إلى لوحة المساعد') }}
                    </a>
                @endif
            </div>
            
            <a href="{{ route('logout') }}" class="mt-4 block text-sm text-gray-600 hover:text-gray-900">
                {{ __('تسجيل الخروج') }}
            </a>
        </div>
    @endif

    <div class="bg-bg-card p-6 rounded-lg mb-8 max-w-md">
        <h2 class="text-2xl font-bold text-text-primary mb-6">{{ __('مرحباً بك في بيان') }}</h2>
        
        <p class="text-text-muted mb-8">{{ __('الرجاء اختيار دورك للمتابعة') }}</p>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Teacher Card -->
            <a href="{{ route('login') }}" class="group rounded-lg border-2 border-transparent hover:border-primary transition-colors duration-300 bg-primary-50">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 mx-auto mb-4 bg-primary-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-person-badge text-3xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary mb-2">{{ __('مدرس') }}</h3>
                    <p class="text-sm text-text-muted">{{ __('دخول كمدرس') }}</p>
                </div>
            </a>

            <!-- Student Registration Card -->
            <a href="{{ route('register.student') }}" class="group rounded-lg border-2 border-transparent hover:border-primary transition-colors duration-300 bg-primary-50">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 mx-auto mb-4 bg-primary-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-person-plus text-3xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary mb-2">{{ __('طالب') }}</h3>
                    <p class="text-sm text-text-muted">{{ __('تسجيل كطالب جديد') }}</p>
                </div>
            </a>

            <!-- Student Login Card -->
            <a href="{{ route('login') }}" class="group rounded-lg border-2 border-transparent hover:border-secondary transition-colors duration-300 bg-secondary-50">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 mx-auto mb-4 bg-secondary-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-person-check text-3xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary mb-2">{{ __('دخول طالب') }}</h3>
                    <p class="text-sm text-text-muted">{{ __('تسجيل الدخول كطالب موجود') }}</p>
                </div>
            </a>

            <!-- Assistant Card -->
            <a href="{{ route('assistant.login') }}" class="group rounded-lg border-2 border-transparent hover:border-primary transition-colors duration-300 bg-primary-50">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 mx-auto mb-4 bg-primary-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-person-workspace text-3xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary mb-2">{{ __('مساعد') }}</h3>
                    <p class="text-sm text-text-muted">{{ __('دخول كمساعد') }}</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Admin section separated -->
    <div class="mt-8 pt-8 border-t border-border-light">
        <h3 class="text-sm font-semibold text-text-muted mb-4">{{ __('الأدمن لديه دخول منفصل') }}</h3>
        <p class="text-sm text-text-muted">
            {{ __('ينبغي على الأدمن استخدام صفحة تسجيل الدخول الافتراضية') }}
        </p>
        <a href="{{ route('login') }}" class="text-sm text-primary hover:text-primary-700 underline">
            {{ __('تسجيل دخول الأدمن') }}
        </a>
    </div>
</x-guest-layout>
