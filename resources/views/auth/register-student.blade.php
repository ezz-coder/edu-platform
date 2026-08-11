<x-guest-layout>
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.student.store') }}">
        @csrf

        <!-- Teacher Code -->
        <div>
            <x-input-label for="teacher_code" :value="__('كود المدرس')" />
            <x-text-input id="teacher_code" class="block mt-1 w-full" type="text" name="teacher_code" :value="old('teacher_code', $code ?? '')" required />
            <x-input-error :messages="$errors->get('teacher_code')" class="mt-2" />
            @if (!$teacher)
                <p class="text-sm text-gray-500 mt-2">أدخل كود المدرس لعرض المجموعات المتاحة</p>
            @endif
        </div>

        @if ($teacher)
            <!-- Group selection -->
            <div class="mt-4">
                <x-input-label for="group_id" :value="__('اختر المجموعة')" />
                <select id="group_id" name="group_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    <option value="">-- اختر مجموعة --</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                            {{ $group->title }} ({{ $group->grade_level }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('group_id')" class="mt-2" />
            </div>

            <!-- Name -->
            <div class="mt-4">
                <x-input-label for="name" :value="__('الاسم الكامل')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Mobile -->
            <div class="mt-4">
                <x-input-label for="mobile" :value="__('رقم الموبايل')" />
                <x-text-input id="mobile" class="block mt-1 w-full" type="text" name="mobile" :value="old('mobile')" required />
                <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
            </div>

            <!-- Parent Mobile -->
            <div class="mt-4">
                <x-input-label for="parent_mobile" :value="__('رقم موبايل ولي الأمر')" />
                <x-text-input id="parent_mobile" class="block mt-1 w-full" type="text" name="parent_mobile" :value="old('parent_mobile')" required />
                <x-input-error :messages="$errors->get('parent_mobile')" class="mt-2" />
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
        @endif

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">
                {{ __('لديك حساب بالفعل؟') }}
            </a>

            @if ($teacher)
                <x-primary-button class="ms-4">
                    {{ __('إنشاء الحساب') }}
                </x-primary-button>
            @endif
        </div>
    </form>
</x-guest-layout>