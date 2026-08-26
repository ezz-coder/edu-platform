<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التحقق بخطوتين - بيان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-4">
        <h1 class="text-xl font-bold text-center text-gray-900">التحقق بخطوتين</h1>
        <p class="text-sm text-center text-gray-500">
            تم إرسال رمز تحقق مكوّن من 6 أرقام إلى بريدك الإلكتروني. الرمز صالح لمدة 10 دقائق.
        </p>

        <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">رمز التحقق</label>
                <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       required autofocus autocomplete="one-time-code"
                       class="w-full border border-gray-300 rounded-lg p-2.5 text-center text-lg tracking-widest">
                @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 font-medium">
                تأكيد الدخول
            </button>
        </form>

        <form method="GET" action="{{ route('login') }}">
            <button type="submit" class="w-full text-sm text-gray-500 hover:text-gray-700">← العودة لتسجيل الدخول</button>
        </form>
    </div>
</body>
</html>