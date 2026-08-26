<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $student->name }} — بوابة ولي الأمر</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('parent.dashboard') }}" class="text-sm text-blue-600 hover:underline">← أبنائي</a>
            <form method="POST" action="{{ route('parent.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-red-600">تسجيل الخروج</button>
            </form>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 space-y-6">
        <!-- Student info -->
        <section class="bg-white border border-gray-200 rounded-lg p-4">
            <h1 class="text-lg font-bold text-gray-900">{{ $student->name }}</h1>
            <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-600">
                <dt>كود الطالب:</dt><dd>{{ $student->student_code ?? '—' }}</dd>
                <dt>المجموعة:</dt><dd>{{ $enrollment?->group?->title ?? '—' }}</dd>
                <dt>المدرس:</dt><dd>{{ $enrollment?->group?->teacher?->name ?? '—' }}</dd>
                <dt>حالة التسجيل:</dt><dd>{{ $enrollment?->status ?? 'غير مسجل' }}</dd>
            </dl>
        </section>

        <!-- Attendance summary -->
        <section class="bg-white border border-gray-200 rounded-lg p-4">
            <h2 class="font-semibold text-gray-800 mb-2">ملخص الحضور</h2>
            <div class="flex gap-6 text-sm">
                <span class="text-green-700">حضور: {{ $attendance['present'] ?? 0 }}</span>
                <span class="text-yellow-700">متأخر: {{ $attendance['late'] ?? 0 }}</span>
                <span class="text-red-700">غياب: {{ $attendance['absent'] ?? 0 }}</span>
            </div>
        </section>

        <!-- Grades -->
        <section class="bg-white border border-gray-200 rounded-lg p-4">
            <h2 class="font-semibold text-gray-800 mb-2">الدرجات</h2>
            @if($attempts->isEmpty())
                <p class="text-sm text-gray-500">لا توجد امتحانات مؤداة.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-right text-gray-500 border-b border-gray-100">
                            <th class="py-1">الامتحان</th>
                            <th class="py-1">الدرجة</th>
                            <th class="py-1">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr class="border-b border-gray-50">
                                <td class="py-1.5">{{ $attempt->title }}</td>
                                <td class="py-1.5">{{ $attempt->score }} / {{ $attempt->total_marks }}</td>
                                <td class="py-1.5 text-gray-500">{{ \Carbon\Carbon::parse($attempt->finished_at)->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
</body>
</html>