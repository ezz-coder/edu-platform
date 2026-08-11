<x-dashboard-layout title="جلسات الحضور">
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">جلسات الحضور</h2>
            <a href="{{ route('assistant.attendance.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium"><i class="bi bi-plus-lg"></i> بدء حصة جديدة</a>
        </div>

        @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div> @endif
        @if (session('info')) <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">{{ session('info') }}</div> @endif

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            @if ($sessions->isEmpty())
                <div class="p-8 text-center text-gray-500">لا توجد جلسات حضور بعد.</div>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">المجموعة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">رقم الحصة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($sessions as $session)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $session->group->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">الحصة {{ $session->lesson_number }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $session->attendance_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="px-2 py-0.5 text-xs rounded-full
                                        @if ($session->status === 'open') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $session->status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <a href="{{ route('assistant.attendance.show', $session) }}" class="text-blue-600 hover:text-blue-900 font-medium">فتح</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-dashboard-layout>