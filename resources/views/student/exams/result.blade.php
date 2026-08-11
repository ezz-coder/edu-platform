<x-dashboard-layout title="نتيجة الامتحان">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
            <h1 class="text-xl font-bold text-gray-900 mb-2">تم تسليم الامتحان</h1>
            <p class="text-gray-600">{{ $exam->title }}</p>

            <div class="mt-6">
                <p class="text-sm text-gray-500">درجتك</p>
                <p class="text-4xl font-bold text-blue-700 mt-1">{{ $attempt->score }} / {{ $attempt->total_marks }}</p>
            </div>

            <div class="mt-6 text-sm text-gray-500">
                وقت التسليم: {{ $attempt->finished_at?->format('Y-m-d H:i') }}
            </div>

            <div class="mt-6">
                <a href="{{ route('student.exams.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">العودة للامتحانات</a>
            </div>
        </div>
    </div>
</x-dashboard-layout>