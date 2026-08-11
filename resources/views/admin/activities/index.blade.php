<x-dashboard-layout title="سجل نشاط المنصة">
    <div class="space-y-6">
        <x-page-header title="سجل نشاط المنصة" description="مراجعة جميع الأنشطة داخل المنصة" />

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('admin.activity-log') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">التصنيف</label>
                    <select name="category" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">الكل</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">نوع المستخدم</label>
                    <select name="actor_type" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">الكل</option>
                        @foreach ($actorTypes as $type)
                            <option value="{{ $type }}" @selected(request('actor_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">بحث</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث..." class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div class="md:col-span-5 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">بحث</button>
                    <a href="{{ route('admin.activity-log') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            @if ($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوقت</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستخدم</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نوع المستخدم</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراء</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التصنيف</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">IP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $log->created_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $log->created_at->format('H:i') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->actor_id ? optional($log->actor)->name : '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 text-xs rounded-full
                                            @if ($log->actor_type_label === 'أدمن') bg-purple-100 text-purple-800
                                            @elseif ($log->actor_type_label === 'مدرس') bg-blue-100 text-blue-800
                                            @elseif ($log->actor_type_label === 'طالب') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $log->actor_type_label ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">{{ $log->action }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">{{ $log->category }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $log->description }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state>
                    <p class="text-gray-500">لا توجد نشاطات بعد</p>
                </x-empty-state>
            @endif
        </div>

        <div class="mt-4">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
</x-dashboard-layout>