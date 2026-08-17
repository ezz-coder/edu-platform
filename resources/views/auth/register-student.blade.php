<x-guest-layout>
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    @if ($invalidCode)
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ __('كود المدرس غير صحيح') }}
        </div>
    @endif

    @if ($teacher && $groups->isNotEmpty())
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('تم العثور على المدرس') }}: {{ $teacher->name }}
        </div>
    @endif

    <!-- Step 1: Teacher Code Lookup Form -->
    <form method="GET" action="{{ route('register.student') }}" class="mb-6">
        <div>
            <x-input-label for="code" :value="__('كود المدرس')" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code', $code ?? '')" placeholder="TC-XXXXXX" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
            @if ($invalidCode)
                <p class="text-sm text-red-600 mt-2">{{ __('كود المدرس غير صحيح. يرجى المحاولة مرة أخرى.') }}</p>
            @endif
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('بحث عن المجموعات') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Step 2: Show groups + student registration form (only when teacher code is valid) -->
    @if ($teacher)
        <form method="POST" action="{{ route('register.student.store') }}">
            @csrf

            <!-- Hidden teacher code -->
            <input type="hidden" name="teacher_code" value="{{ $code }}" />

            <!-- Group selection -->
            <div class="mb-4">
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

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">
                    {{ __('لديك حساب بالفعض؟') }}
                </a>

                <x-primary-button class="ms-4">
                    {{ __('إنشاء الحساب') }}
                </x-primary-button>
            </div>
        </form>
    @else
        <!-- Show message when no valid code entered yet -->
        @if ($code && !$invalidCode)
            <!-- Code was entered but no teacher found — already show error above -->
        @elseif (!$code)
            <p class="text-sm text-gray-500 mt-4">أدخل كود المدرس واضغط "بحث عن المجموعات" لعرض المجموعات المتاحة.</p>
        @endif
    @endif
</x-guest-layout>
