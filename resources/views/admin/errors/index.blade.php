<x-dashboard-layout title="الأخطاء الأخيرة">
    <div class="space-y-6">
        <x-page-header title="الأخطاء الأخيرة" description="عرض آخر الأخطاء المسجلة في المنصة" />

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            @if (count($errors) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرسالة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الملف</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السطر</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستوى</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($errors as $error)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $error['date'] }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-700 max-w-md truncate">{{ $error['message'] }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $error['file'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $error['line'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 text-xs rounded-full
                                            @if ($error['severity'] === 'خطأ') bg-red-100 text-red-800
                                            @elseif ($error['severity'] === 'حرج') bg-red-200 text-red-900
                                            @elseif ($error['severity'] === 'تحذير') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $error['severity'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state>
                    <p class="text-gray-500">لا توجد أخطاء مسجلة</p>
                </x-empty-state>
            @endif
        </div>
    </div>
</x-dashboard-layout>