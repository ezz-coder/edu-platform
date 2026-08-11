<x-dashboard-layout title="تعديل صلاحيات المساعد">
    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('teacher.assistants.update', $assistant) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('الاسم')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $assistant->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Username -->
            <div>
                <x-input-label for="username" :value="__('اسم المستخدم')" />
                <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $assistant->username)" required />
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('كلمة المرور (اتركها فارغة لعدم التغيير)')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Phone -->
            <div>
                <x-input-label for="phone" :value="__('الهاتف (اختياري)')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $assistant->phone)" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <!-- Permissions -->
            <div>
                <p class="font-semibold text-gray-900 mb-3">صلاحيات المساعد</p>
                <div class="space-y-2">
                    @foreach ($permissionLabels as $key => $label)
                        <label class="flex items-center gap-3 bg-gray-50 rounded-lg px-4 py-3 cursor-pointer">
                            <input type="checkbox" name="{{ $key }}" value="1" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" {{ old($key, $permissions?->{$key}) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3">
                <x-primary-button class="bg-emerald-600 hover:bg-emerald-700">
                    {{ __('حفظ التعديلات') }}
                </x-primary-button>
                <a href="{{ route('teacher.assistants.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </div>
</x-dashboard-layout>