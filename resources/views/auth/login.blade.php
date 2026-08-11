<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <!-- Mobile -->
        <div>
            <x-input-label for="mobile" :value="__('رقم الموبايل')" />
            <x-text-input id="mobile" class="block mt-1 w-full" type="text" name="mobile" :value="old('mobile')" required autofocus />
            <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('كلمة المرور')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('تذكرني') }}</span>
            </label>
        </div>

        <div class="flex flex-col items-center justify-center mt-4 gap-3">
            <x-primary-button>
                {{ __('تسجيل الدخول') }}
            </x-primary-button>

            <a class="text-sm text-blue-600 hover:text-blue-900" href="{{ route('register.teacher') }}">
                {{ __('تسجيل مدرس جديد') }}
            </a>
            <a class="text-sm text-blue-600 hover:text-blue-900" href="{{ route('register.student') }}">
                {{ __('تسجيل طالب جديد') }}
            </a>
        </div>
    </form>
</x-guest-layout>
