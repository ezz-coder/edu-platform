<x-dashboard-layout title="تعديل امتحان">
    <x-page-header
        title="تعديل امتحان"
        description="تعديل بيانات الامتحان"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('assistant.exams.update', $exam) }}" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">العنوان</label>
            <input type="text" name="title" value="{{ old('title', $exam->title) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الوصف</label>
            <textarea name="description" class="w-full border rounded px-3 py-2">{{ old('description', $exam->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">المدة (بالدقائق)</label>
            <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="w-full border rounded px-3 py-2" min="1" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">تاريخ البدء</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">تاريخ الانتهاء</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الحالة</label>
            <select name="status" class="w-full border rounded px-3 py-2" required>
                <option value="visible" {{ $exam->status === 'visible' ? 'selected' : '' }}>منشور</option>
                <option value="hidden" {{ $exam->status === 'hidden' ? 'selected' : '' }}>مخفي</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">المجموعات المستهدفة</label>
            <div class="border rounded p-3 max-h-48 overflow-y-auto">
                @foreach ($groups as $group)
                    <label class="flex items-center mb-2">
                        <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                            {{ in_array($group->id, $exam->targetGroups->pluck('id')->toArray()) ? 'checked' : '' }}
                            class="ml-2">
                        {{ $group->title }}
                    </label>
                @endforeach
            </div>
        </div>

        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">حفظ التغييرات</button>
    </form>
</x-dashboard-layout>