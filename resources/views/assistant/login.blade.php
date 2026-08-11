<x-guest-layout>
    <form method="POST" action="{{ route('assistant.login') }}">
        @csrf

        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">دخول المساعد</h1>
            <p class="text-sm text-gray-500 mt-1">منصة بيان — حساب مساعد المدرس</p>
        </div>

        <!-- Username -->
        <div>
            <x-input-label for="username" :value="__('اسم المستخدم')" />
            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
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

        <div class="flex flex-col items-center justify-center mt-6 gap-3">
            <x-primary-button>
                {{ __('تسجيل الدخول') }}
            </x-primary-button>

            <a class="text-sm text-blue-600 hover:text-blue-900" href="{{ route('login') }}">
                {{ __('دخول كمدرس أو طالب') }}
            </a>
        </div>
    </form>
</x-guest-layout>