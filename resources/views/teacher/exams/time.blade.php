<x-dashboard-layout title="تعديل وقت الامتحان">
    <div class="max-w-xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">تعديل وقت الامتحان: {{ $exam->title }}</h1>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-2 rounded-lg text-sm mb-4">
            الطلاب الذين بدأوا الامتحان يستمرون بمؤقتهم الحالي (حسب وقت بدئهم). فقط الطلاب الذين لم يبدأوا يتأثرون بالوقت الجديد.
        </div>

        <form method="POST" action="{{ route('teacher.exams.time.update', $exam) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">وقت البداية</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">وقت النهاية</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">حفظ</button>
                <a href="{{ route('teacher.exams.attempts', $exam) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>
</x-dashboard-layout>