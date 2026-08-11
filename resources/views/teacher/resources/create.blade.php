<x-dashboard-layout title="إنشاء مصدر">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">إنشاء مصدر</h1>

        <form method="POST" action="{{ route('teacher.resources.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <select name="type" id="type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div id="file-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">الملف (PDF / صورة / ZIP / ملف)</label>
                <input type="file" name="file" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div id="url-field" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">الرابط (Google Drive / رابط خارجي / رابط فيديو)</label>
                <input type="url" name="url" value="{{ old('url') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ النشر</label>
                    <input type="date" name="publish_date" value="{{ old('publish_date') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الانتهاء (اختياري)</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="pinned" value="1" {{ old('pinned') ? 'checked' : '' }}> مثبّت
                </label>
            </div>

            <hr>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الدرس (اختياري)</label>
                <select name="lesson_id" id="lesson_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">— مورد عام —</option>
                    @foreach ($lessons as $lesson)
                        <option value="{{ $lesson->id }}">{{ $lesson->title }} ({{ $lesson->group->title }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">اتركه فارغاً ليصبح المورد عاماً</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الهدف</label>
                <select name="target_scope" id="target_scope" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <option value="group" {{ old('target_scope', 'group') === 'group' ? 'selected' : '' }}>مجموعة محددة</option>
                    <option value="stage" {{ old('target_scope') === 'stage' ? 'selected' : '' }}>مرحلة دراسية</option>
                    <option value="gender" {{ old('target_scope') === 'gender' ? 'selected' : '' }}>نوع المجموعة</option>
                    <option value="all" {{ old('target_scope') === 'all' ? 'selected' : '' }}>كل المجموعات</option>
                </select>
            </div>

            <div id="group-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">اختر المجموعات</label>
                <select name="group_ids[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2" size="4">
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->title }} ({{ $g->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div id="stage-field" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">المرحلة</label>
                <select name="stage" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    @foreach ($stages as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div id="gender-field" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <select name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="boys">بنين</option>
                    <option value="girls">بنات</option>
                    <option value="mixed">مختلط</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">حفظ</button>
                <a href="{{ route('teacher.resources.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>

    <script>
        function toggleFields() {
            const type = document.getElementById('type').value;
            const isFile = ['pdf','image','zip','other'].includes(type);
            document.getElementById('file-field').style.display = isFile ? 'block' : 'none';
            document.getElementById('url-field').style.display = isFile ? 'none' : 'block';

            const scope = document.getElementById('target_scope').value;
            document.getElementById('group-field').style.display = scope === 'group' ? 'block' : 'none';
            document.getElementById('stage-field').style.display = scope === 'stage' ? 'block' : 'none';
            document.getElementById('gender-field').style.display = scope === 'gender' ? 'block' : 'none';
        }
        document.getElementById('type').addEventListener('change', toggleFields);
        document.getElementById('target_scope').addEventListener('change', toggleFields);
        toggleFields();
    </script>
</x-dashboard-layout>