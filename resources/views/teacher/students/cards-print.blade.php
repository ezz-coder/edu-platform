<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة بطاقات مجموعة - {{ $group->title }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-4 print:hidden">
            <h1 class="text-xl font-bold text-gray-900">بطاقات مجموعة: {{ $group->title }}</h1>
            <button type="button" onclick="window.print()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                <i class="bi bi-printer"></i> طباعة الكل
            </button>
        </div>

        @if ($cards->isEmpty())
            <p class="text-center text-gray-500 py-8">لا يوجد طلاب في هذه المجموعة.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($cards as $item)
                    @include('components.student-card', [
                        'account' => $item['enrollment']->student,
                        'enrollment' => $item['enrollment'],
                        'qr' => $item['qr'],
                    ])
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>