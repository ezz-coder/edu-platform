<x-dashboard-layout title="صحة النظام">
    <div class="space-y-6">
        <x-page-header title="صحة النظام" description="معلومات عامة عن حالة المنصة" />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">إصدار التطبيق</p>
                <p class="text-lg font-bold text-gray-900">{{ $health['app_version'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">إصدار Laravel</p>
                <p class="text-lg font-bold text-gray-900">{{ $health['laravel_version'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">إصدار PHP</p>
                <p class="text-lg font-bold text-gray-900">{{ $health['php_version'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">حالة قاعدة البيانات</p>
                <p class="text-lg font-bold {{ $health['database_status'] === 'متصل' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $health['database_status'] }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">التخزين</p>
                <p class="text-lg font-bold {{ $health['storage_writable'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $health['storage_writable'] ? 'قابل للكتابة' : 'غير قابل للكتابة' }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">حالة الكاش</p>
                <p class="text-lg font-bold {{ $health['cache_status'] === 'يعمل' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $health['cache_status'] }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">آخر نسخة احتياطية</p>
                <p class="text-lg font-bold text-gray-900">{{ $health['last_backup'] ? $health['last_backup']->format('Y-m-d H:i') : 'لا توجد' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-500">البيئة</p>
                <p class="text-lg font-bold text-gray-900">{{ $health['environment'] }}</p>
            </div>
        </div>
    </div>
</x-dashboard-layout>