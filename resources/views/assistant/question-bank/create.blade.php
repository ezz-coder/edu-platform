<x-dashboard-layout title="إضافة سؤال">
    <x-page-header
        title="إضافة سؤال جديد"
        description="إضافة سؤال إلى بنك الأسئلة"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('assistant.question-bank.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">العنوان (اختياري)</label>
            <input type="text" name="title" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">السؤال</label>
            <textarea name="body" class="w-full border rounded px-3 py-2" required></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">النوع</label>
            <select name="type" class="w-full border rounded px-3 py-2" required>
                <option value="mcq">اختيار من متعدد</option>
                <option value="true_false">صح/خطأ</option>
                <option value="fill_blank">ملء فراغ</option>
                <option value="short_answer">إجابة قصيرة</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الإجابة الصحيحة</label>
            <input type="text" name="correct_answer" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الخيارات (JSON للـ MCQ)</label>
            <textarea name="options" class="w-full border rounded px-3 py-1" placeholder='{"a":"الخيار أ", "b":"الخيار ب"}'></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">التفسير</label>
            <textarea name="explanation" class="w-full border rounded px-3 py-2"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الصعوبة</label>
            <select name="difficulty" class="w-full border rounded px-3 py-2" required>
                <option value="easy">سهل</option>
                <option value="medium">متوسط</option>
                <option value="hard">صعب</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الدرجات</label>
            <input type="number" name="points" class="w-full border rounded px-3 py-2" min="1" value="1" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الدرس (اختياري)</label>
            <select name="lesson_id" class="w-full border rounded px-3 py-2">
                <option value="">بدون درس</option>
                @foreach ($lessons as $lesson)
                    <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">المجموعة (اختياري)</label>
            <select name="group_id" class="w-full border rounded px-3 py-2">
                <option value="">بدون مجموعة</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->title }}</option>
                @endforeach
            </select>
        </div>

        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">إنشاء السؤال</button>
    </form>
</x-dashboard-layout>