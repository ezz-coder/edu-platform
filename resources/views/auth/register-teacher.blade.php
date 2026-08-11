<x-guest-layout>
    <form method="POST" action="{{ route('register.teacher.store') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('الاسم الكامل')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Mobile -->
        <div class="mt-4">
            <x-input-label for="mobile" :value="__('رقم الموبايل')" />
            <x-text-input id="mobile" class="block mt-1 w-full" type="text" name="mobile" :value="old('mobile')" required />
            <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('كلمة المرور')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('تأكيد كلمة المرور')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">
                {{ __('لديك حساب بالفعل؟') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('إرسال طلب التسجيل') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>