<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة ولي الأمر - بيان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="font-bold text-gray-900">بوابة ولي الأمر</h1>
            <form method="POST" action="{{ route('parent.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-red-600">تسجيل الخروج</button>
            </form>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 space-y-4">
        @if(session('success'))
            <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('success') }}</div>
        @endif

        <h2 class="text-lg font-semibold text-gray-800">أبنائي</h2>

        @if($students->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                لا يوجد طلاب مرتبطون برقمك.
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($students as $student)
                    @php $enrollment = $student->enrollments->first(); @endphp
                    <a href="{{ route('parent.student.show', $student) }}"
                       class="bg-white border border-gray-200 rounded-lg p-4 hover:border-blue-400 transition-colors block">
                        <p class="font-semibold text-gray-900">{{ $student->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">كود الطالب: {{ $student->student_code ?? '—' }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $enrollment ? ($enrollment->group->title ?? '') : 'غير مسجل حالياً' }}
                        </p>
                        <span class="inline-block mt-2 text-xs text-blue-600">عرض البيانات ←</span>
                    </a>
                @endforeach
            </div>
        @endif

        <p class="text-xs text-gray-400 text-center pt-4">
            هذه البوابة للقراءة فقط وتعرض الحضور والدرجات والتقرير فقط.
        </p>
    </main>
</body>
</html>