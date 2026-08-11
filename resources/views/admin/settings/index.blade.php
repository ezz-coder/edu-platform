<x-dashboard-layout title="إعدادات المنصة">
    <div class="space-y-6">
        <x-page-header title="إعدادات المنصة" description="تعديل إعدادات المنصة العامة" />

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اسم المنصة</label>
                        <input type="text" name="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شعار المنصة (رابط)</label>
                        <input type="text" name="platform_logo" value="{{ old('platform_logo', $settings['platform_logo']) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اللون الأساسي</label>
                        <input type="color" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}" class="w-full h-10 border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اللون الثانوي</label>
                        <input type="color" name="secondary_color" value="{{ old('secondary_color', $settings['secondary_color']) }}" class="w-full h-10 border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رقم الدعم</label>
                        <input type="text" name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">بريد الدعم</label>
                        <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">وصف المنصة</label>
                    <textarea name="platform_description" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('platform_description', $settings['platform_description']) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رسالة الترحيب</label>
                    <textarea name="welcome_message" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('welcome_message', $settings['welcome_message']) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نص التذييل</label>
                    <textarea name="footer_text" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('footer_text', $settings['footer_text']) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">حفظ الإعدادات</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>