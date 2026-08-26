<x-dashboard-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <h2 class="text-lg font-bold text-text-primary">الإعدادات</h2>

        <!-- Change mobile -->
        <div class="bg-bg-card rounded-card border border-border-light p-5">
            <h3 class="font-semibold text-text-primary mb-1">تغيير رقم الهاتف</h3>
            <p class="text-xs text-text-muted mb-4">الرقم الحالي: <span dir="ltr">{{ $account->mobile }}</span></p>
            <form method="POST" action="{{ route('student.settings.mobile') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                @csrf
                @method('PATCH')
                <div class="flex-1 w-full">
                    <label for="mobile" class="block text-sm font-medium text-text-secondary mb-1">رقم الهاتف الجديد</label>
                    <input id="mobile" name="mobile" type="text" dir="ltr" value="{{ old('mobile') }}"
                           class="w-full rounded-input border-border-light focus:border-primary-500 focus:ring-primary-500"
                           required maxlength="20" autocomplete="tel">
                    @error('mobile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary btn-sm whitespace-nowrap">حفظ الرقم</button>
            </form>
            <p class="text-xs text-text-muted mt-3">ملاحظة: يتم تحديث الرقم لجميع حساباتك المرتبطة بنفس الرقم للحفاظ على تبديل الحسابات.</p>
        </div>

        <!-- Links to existing settings -->
        <div class="bg-bg-card rounded-card border border-border-light divide-y divide-border-light">
            <a href="{{ route('profile.edit') }}" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="bi bi-person-gear text-lg text-primary-600"></i>
                    <span class="font-medium text-text-primary">تعديل البيانات الأساسية (الاسم، البريد)</span>
                </div>
                <i class="bi bi-chevron-left text-text-muted"></i>
            </a>
            <a href="{{ route('profile.edit') }}#change-password" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="bi bi-shield-lock text-lg text-primary-600"></i>
                    <span class="font-medium text-text-primary">تغيير كلمة المرور</span>
                </div>
                <i class="bi bi-chevron-left text-text-muted"></i>
            </a>
            <a href="{{ route('student.accounts') }}" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="bi bi-people text-lg text-primary-600"></i>
                    <span class="font-medium text-text-primary">تبديل الحسابات</span>
                </div>
                <i class="bi bi-chevron-left text-text-muted"></i>
            </a>
        </div>
    </div>
</x-dashboard-layout>