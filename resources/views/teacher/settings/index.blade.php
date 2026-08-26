<x-dashboard-layout title="الإعدادات">
    <x-page-header
        title="الإعدادات"
        description="بيانات الحساب وتغيير كلمة المرور"
    />

    @if (session('status') === 'password-updated')
        <div class="alert-success mb-6">تم تحديث كلمة المرور بنجاح.</div>
    @endif

    <!-- Account info -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-bold text-text-primary mb-4"><i class="bi bi-person-gear ml-2"></i> بيانات الحساب</h2>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-text-secondary">الاسم</dt>
                <dd class="font-medium text-text-primary">{{ $teacher->name }}</dd>
            </div>
            <div>
                <dt class="text-text-secondary">كود المدرس (غير قابل للتعديل)</dt>
                <dd class="font-medium text-text-primary" id="teacher-code">{{ $teacher->teacher_code }}</dd>
            </div>
            <div>
                <dt class="text-text-secondary">رقم الموبايل (غير قابل للتعديل)</dt>
                <dd class="font-medium text-text-primary">{{ $teacher->mobile }}</dd>
            </div>
            <div>
                <dt class="text-text-secondary">البريد الإلكتروني</dt>
                <dd class="font-medium text-text-primary">{{ $teacher->email }}</dd>
            </div>
            @if ($activeTerm)
                <div>
                    <dt class="text-text-secondary">الترم الأكاديمي النشط</dt>
                    <dd class="font-medium text-text-primary">{{ $activeTerm->name }}</dd>
                </div>
            @endif
        </dl>

        <a href="{{ route('profile.edit') }}" class="btn-primary btn-sm mt-4 inline-flex">
            <i class="bi bi-pencil"></i> تعديل الاسم والبريد
        </a>
    </div>

    <!-- Sprint 68: Two-Factor Authentication (email OTP) -->
    <div class="card p-6 max-w-xl mb-6">
        <h2 class="text-lg font-bold text-text-primary mb-2"><i class="bi bi-shield-check ml-2"></i> التحقق بخطوتين</h2>
        <p class="text-sm text-text-secondary mb-4">
            عند التفعيل، سيُرسل رمز تحقق إلى بريدك الإلكتروني عند كل تسجيل دخول.
            الحالة الحالية:
            <strong>{{ $teacher->two_factor_enabled ? 'مفعّل' : 'معطّل' }}</strong>
        </p>

        @if(session('success'))
            <div class="alert-success mb-3">{{ session('success') }}</div>
        @endif

        @if (! $teacher->two_factor_enabled)
            <form method="POST" action="{{ route('teacher.two-factor.enable') }}" class="flex items-end gap-2">
                @csrf
                <div class="form-group flex-1">
                    <label for="tf_password" class="form-label">كلمة المرور الحالية *</label>
                    <input id="tf_password" name="password" type="password" class="form-input" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-primary">تفعيل</button>
            </form>
        @else
            <form method="POST" action="{{ route('teacher.two-factor.disable') }}" class="flex items-end gap-2">
                @csrf
                <div class="form-group flex-1">
                    <label for="tf_password_off" class="form-label">كلمة المرور الحالية *</label>
                    <input id="tf_password_off" name="password" type="password" class="form-input" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-secondary">تعطيل</button>
            </form>
        @endif
        @error('password')
            <p class="text-danger-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password change (Workflow 20) -->
    <div class="card p-6 max-w-xl">
        <h2 class="text-lg font-bold text-text-primary mb-4"><i class="bi bi-shield-lock ml-2"></i> تغيير كلمة المرور</h2>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password" class="form-label">كلمة المرور الحالية *</label>
                <input id="current_password" name="current_password" type="password" class="form-input" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">كلمة المرور الجديدة *</label>
                <input id="password" name="password" type="password" class="form-input" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">تأكيد كلمة المرور الجديدة *</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" required autocomplete="new-password">
            </div>

            @error('current_password')
                <p class="text-danger-600 text-sm">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn-primary">
                <i class="bi bi-check-lg"></i> حفظ كلمة المرور
            </button>
        </form>
    </div>
</x-dashboard-layout>