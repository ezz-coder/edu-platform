<x-dashboard-layout title="بنك الأسئلة">
    <x-page-header
        title="بنك الأسئلة"
        description="إدارة أسئلتك المحفوظة"
        :action="'<a href=\''.route('assistant.question-bank.create').'\' class=\'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> سؤال</a>'"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs mb-1">النوع</label>
                <select name="type" class="border rounded px-2 py-1">
                    <option value="">الكل</option>
                    <option value="mcq" {{ ($filters['type'] ?? '') === 'mcq' ? 'selected' : '' }}>اختيار من متعدد</option>
                    <option value="true_false" {{ ($filters['type'] ?? '') === 'true_false' ? 'selected' : '' }}>صح/خطأ</option>
                    <option value="fill_blank" {{ ($filters['type'] ?? '') === 'fill_blank' ? 'selected' : '' }}>ملء فراغ</option>
                    <option value="short_answer" {{ ($filters['type'] ?? '') === 'short_answer' ? 'selected' : '' }}>إجابة قصيرة</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1">الصعوبة</label>
                <select name="difficulty" class="border rounded px-2 py-1">
                    <option value="">الكل</option>
                    <option value="easy" {{ ($filters['difficulty'] ?? '') === 'easy' ? 'selected' : '' }}>سهل</option>
                    <option value="medium" {{ ($filters['difficulty'] ?? '') === 'medium' ? 'selected' : '' }}>متوسط</option>
                    <option value="hard" {{ ($filters['difficulty'] ?? '') === 'hard' ? 'selected' : '' }}>صعب</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1">الدرس</label>
                <select name="lesson_id" class="border rounded px-2 py-1">
                    <option value="">الكل</option>
                    @foreach ($lessons as $lesson)
                        <option value="{{ $lesson->id }}" {{ ($filters['lesson_id'] ?? '') == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1">المجموعة</label>
                <select name="group_id" class="border rounded px-2 py-1">
                    <option value="">الكل</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" {{ ($filters['group_id'] ?? '') == $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="بحث..." class="border rounded px-2 py-1">
            </div>
            <button class="px-3 py-1 bg-blue-600 text-white rounded">بحث</button>
            <a href="{{ route('assistant.question-bank.index') }}" class="px-3 py-1 bg-gray-300 rounded">مسح</a>
        </form>
    </div>

    <a href="{{ route('assistant.question-bank.recycle-bin') }}" class="text-gray-600 hover:underline mb-2 inline-block">سلة المحذوفات</a>

    @if ($questions->isEmpty())
        <x-empty-state icon="bi-question-circle" title="لا توجد أسئلة بعد" message="أضف أسئلة لبنكك." :action="'<a href=\''.route('assistant.question-bank.create').'\' class=\'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> سؤال</a>'" />
    @else
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">السؤال</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">الصعوبة</th>
                        <th class="px-4 py-3">الدرجات</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($questions as $question)
                        <tr>
                            <td class="px-4 py-3">{{ Str::limit($question->body ?? $question->title, 50) }}</td>
                            <td class="px-4 py-3">{{ ['mcq' => 'اختيار من متعدد', 'true_false' => 'صح/خطأ', 'fill_blank' => 'ملء فراغ', 'short_answer' => 'إجابة قصيرة'][$question->type] ?? '' }}</td>
                            <td class="px-4 py-3">{{ ['easy' => 'سهل', 'medium' => 'متوسط', 'hard' => 'صعب'][$question->difficulty] ?? '' }}</td>
                            <td class="px-4 py-3">{{ $question->points }}</td>
                            <td class="px-4 py-3">{{ $question->status === 'active' ? 'نشط' : 'غير نشط' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('assistant.question-bank.edit', $question) }}" class="text-blue-600 hover:underline">تعديل</a>
                                <form method="POST" action="{{ route('assistant.question-bank.destroy', $question) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $questions->links() }}
    @endif
</x-dashboard-layout>