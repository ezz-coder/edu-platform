<x-dashboard-layout title="تعديل مصدر">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">تعديل مصدر</h1>

        <form method="POST" action="{{ route('assistant.resources.update', $resource) }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                <input type="text" name="title" value="{{ old('title', $resource->title) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $resource->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <select name="type" id="type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" {{ old('type', $resource->type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div id="file-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">الملف (PDF / صورة / ZIP / ملف) — اتركه فارغاً للإبقاء على الحالي</label>
                <input type="file" name="file" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @if ($resource->file_path)
                    <p class="text-xs text-gray-500 mt-1">الملف الحالي: {{ basename($resource->file_path) }}</p>
                @endif
            </div>

            <div id="url-field" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">الرابط</label>
                <input type="url" name="url" value="{{ old('url', $resource->url) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ النشر</label>
                    <input type="date" name="publish_date" value="{{ old('publish_date', $resource->publish_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الانتهاء (اختياري)</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', $resource->expires_at?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="pinned" value="1" {{ old('pinned', $resource->pinned) ? 'checked' : '' }}> مثبّت
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <option value="visible" {{ old('status', $resource->status) === 'visible' ? 'selected' : '' }}>ظاهر</option>
                    <option value="hidden" {{ old('status', $resource->status) === 'hidden' ? 'selected' : '' }}>مخفي</option>
                    <option value="archived" {{ old('status', $resource->status) === 'archived' ? 'selected' : '' }}>مؤرشف</option>
                </select>
            </div>

            <hr>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الدرس (اختياري)</label>
                <select name="lesson_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">— مورد عام —</option>
                    @foreach ($lessons as $lesson)
                        <option value="{{ $lesson->id }}" {{ old('lesson_id', $resource->lesson_id) == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }} ({{ $lesson->group->title }})</option>
                    @endforeach
                </select>
            </div>

            @php
                $firstTarget = $resource->targets->first();
                $scope = old('target_scope', $firstTarget?->scope ?? 'group');
                $selGroups = old('group_ids', $resource->targets->where('scope','group')->pluck('group_id')->toArray());
                $selStage = old('stage', $resource->targets->where('scope','stage')->first()?->stage);
                $selGender = old('gender', $resource->targets->where('scope','gender')->first()?->gender);
            @endphp

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الهدف</label>
                <select name="target_scope" id="target_scope" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <option value="group" {{ $scope === 'group' ? 'selected' : '' }}>مجموعة محددة</option>
                    <option value="stage" {{ $scope === 'stage' ? 'selected' : '' }}>مرحلة دراسية</option>
                    <option value="gender" {{ $scope === 'gender' ? 'selected' : '' }}>نوع المجموعة</option>
                    <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>كل المجموعات</option>
                </select>
            </div>

            <div id="group-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">اختر المجموعات</label>
                <select name="group_ids[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2" size="4">
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ in_array($g->id, $selGroups) ? 'selected' : '' }}>{{ $g->title }} ({{ $g->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div id="stage-field" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">المرحلة</label>
                <select name="stage" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    @foreach ($stages as $s)
                        <option value="{{ $s }}" {{ $selStage === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div id="gender-field" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <select name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="boys" {{ $selGender === 'boys' ? 'selected' : '' }}>بنين</option>
                    <option value="girls" {{ $selGender === 'girls' ? 'selected' : '' }}>بنات</option>
                    <option value="mixed" {{ $selGender === 'mixed' ? 'selected' : '' }}>مختلط</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">حفظ</button>
                <a href="{{ route('assistant.resources.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
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