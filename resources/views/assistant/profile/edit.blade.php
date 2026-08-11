<x-dashboard-layout title="الملف الشخصي">
    <div class="max-w-4xl mx-auto space-y-6">
        <x-page-header
            title="الملف الشخصي"
            description="تعديل بياناتك الشخصية وكلمة المرور"
        />

        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="max-w-xl">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">البيانات الشخصية</h3>

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="post" action="{{ route('assistant.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('patch')

                    <div>
                        <x-input-label for="name" value="الاسم" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $assistant->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="رقم الهاتف" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $assistant->phone)" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>حفظ التعديلات</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Change -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="max-w-xl">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تغيير كلمة المرور</h3>

                <form method="post" action="{{ route('assistant.password.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="current_password" value="كلمة المرور الحالية" />
                        <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="كلمة المرور الجديدة" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="تأكيد كلمة المرور" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>تغيير كلمة المرور</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>