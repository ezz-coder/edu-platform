<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بطاقة الطالب - {{ $enrollment->student->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto">
        @include('components.student-card', [
            'account' => $enrollment->student,
            'enrollment' => $enrollment,
            'qr' => $qr,
        ])
        <div class="mt-4 text-center print:hidden">
            <button type="button" onclick="window.print()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                <i class="bi bi-printer"></i> طباعة البطاقة
            </button>
        </div>
    </div>
</body>
</html>