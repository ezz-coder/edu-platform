<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>حساب ولي الأمر جديد - بيان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-4">
        <h1 class="text-2xl font-bold text-center text-gray-900">إنشاء حساب ولي أمر</h1>
        <p class="text-sm text-center text-gray-500">
            استخدم نفس رقم الموبايل المسجل كـ "هاتف ولي الأمر" لأبنائك لربطهم تلقائياً.
        </p>

        <form method="POST" action="{{ route('parent.register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الاسم</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                       class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">رقم الموبايل (هاتف ولي الأمر)</label>
                <input type="text" name="mobile" value="{{ old('mobile') }}" required maxlength="20"
                       class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                @error('mobile')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 font-medium">
                إنشاء الحساب
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            لديك حساب؟
            <a href="{{ route('parent.login') }}" class="text-blue-600 hover:underline">تسجيل الدخول</a>
        </p>
    </div>
</body>
</html>