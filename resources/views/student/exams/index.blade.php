<x-dashboard-layout title="الامتحانات">
    <div class="space-y-4">
        <h1 class="text-lg font-bold text-gray-900">الامتحانات</h1>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded-lg text-sm">{{ session('info') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($exams as $exam)
                @php
                    $attempt = $exam->attempts->first();
                    $now = now();
                    $notStarted = $exam->starts_at && $now->lt($exam->starts_at);
                    $ended = $exam->ends_at && $now->gt($exam->ends_at);
                @endphp
                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                    <h3 class="font-semibold text-gray-900">{{ $exam->title }}</h3>
                    <p class="text-xs text-gray-500 mt-1">المدة: {{ $exam->duration_minutes }} دقيقة</p>
                    <p class="text-xs text-gray-500">من: {{ $exam->starts_at?->format('Y-m-d H:i') ?? '—' }}</p>
                    <p class="text-xs text-gray-500">إلى: {{ $exam->ends_at?->format('Y-m-d H:i') ?? '—' }}</p>
                    <div class="mt-3">
                        @if ($notStarted)
                            <span class="inline-block text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded">سيبدأ لاحقاً</span>
                        @elseif ($ended)
                            <span class="inline-block text-xs px-2 py-1 bg-red-100 text-red-700 rounded">منتهي</span>
                        @elseif ($attempt && $attempt->isFinished())
                            <span class="inline-block text-xs px-2 py-1 bg-green-100 text-green-700 rounded">تم الحل</span>
                        @else
                            <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                                @csrf
                                <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">ابدأ الامتحان</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <x-empty-state icon="bi-file-earmark-text" title="لا توجد امتحانات متاحة" message="ستظهر امتحانات مدرّسك هنا عند نشرها وفي وقتها المحدد." />
            @endforelse
        </div>

        {{ $exams->links() }}
    </div>
</x-dashboard-layout>