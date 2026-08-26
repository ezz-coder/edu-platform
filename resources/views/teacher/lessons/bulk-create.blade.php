<x-dashboard-layout title="إنشاء دروس متعددة">
    <x-page-header
        title="إنشاء دروس متعددة"
        description="أدخل عنوان درس واحد في كل سطر لإنشاء عدة دروس دفعة واحدة"
    />

    @if ($errors->any())
        <div class="alert-danger mb-6">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.lessons.bulk.store') }}" class="card p-6 space-y-5 max-w-3xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label for="group_id" class="form-label">المجموعة *</label>
                <select id="group_id" name="group_id" class="form-select" required>
                    <option value="">اختر المجموعة</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>{{ $g->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="is_active" class="form-label">الحالة</label>
                <label class="flex items-center gap-2 mt-2 text-sm text-text-primary">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                    تفعيل الدروس (تظهر للطلاب)
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">الوصف المشترك (اختياري)</label>
            <textarea id="description" name="description" rows="2" class="form-textarea">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label for="titles" class="form-label">عناوين الدروس * (عنوان واحد في كل سطر، بحد أقصى 100 درس)</label>
            <textarea id="titles" name="titles" rows="10" class="form-textarea font-mono" required placeholder="الدرس الأول&#10;الدرس الثاني&#10;الدرس الثالث">{{ old('titles') }}</textarea>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">
                <i class="bi bi-plus-circle"></i> إنشاء الدروس
            </button>
            <a href="{{ route('teacher.lessons.index') }}" class="btn-ghost">إلغاء</a>
        </div>
    </form>
</x-dashboard-layout>