<x-dashboard-layout title="جلسة حضور">
    <div class="space-y-6">
        <a href="{{ route('assistant.attendance.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            العودة إلى جلسات الحضور
        </a>

        @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div> @endif

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $group->title }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $group->day }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full
                    @if ($session->status === 'open') bg-green-100 text-green-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $session->status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-sm text-gray-600">
                <div><p class="text-gray-400">رقم الحصة</p><p class="font-medium text-gray-900">{{ $session->lesson_number }}</p></div>
                <div><p class="text-gray-400">التاريخ</p><p class="font-medium text-gray-900">{{ $session->attendance_date->format('Y-m-d') }}</p></div>
                <div><p class="text-gray-400">وقت البداية</p><p class="font-medium text-gray-900">{{ $session->started_at->format('H:i') }}</p></div>
                <div><p class="text-gray-400">المسجل / الإجمالي</p><p class="font-medium text-gray-900">{{ $recorded }} / {{ $total }}</p></div>
            </div>
        </div>

        @if ($session->status === 'open')
            <div id="lastScanned" class="rounded-lg p-4 text-center {{ $lastRecord ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-gray-50 border border-gray-200 text-gray-500' }}">
                @if ($lastRecord)
                    <span class="text-xl font-bold">✅ {{ $lastRecord->student->name }}</span>
                    <p class="text-sm mt-1">تم تسجيل الحضور</p>
                @else
                    <span class="text-sm">لم يُسجَّل أي طالب بعد</span>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">المسح</h2>
                <form method="POST" action="{{ route('assistant.attendance.scan', $session) }}" class="space-y-3" id="scanForm">
                    @csrf
                    <input type="hidden" name="student_id" id="scanStudentId">
                    <input type="hidden" name="student_code" id="scanStudentCode">
                    <input type="hidden" name="qr_token" id="scanQrToken">
                    <input type="text" id="scanInput" autofocus inputmode="text"
                           placeholder="امسح بطاقة QR أو أدخل كود الطالب"
                           class="w-full text-lg border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-3">
                    <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-base font-medium">تسجيل حضور</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
                <span class="text-sm text-gray-600">المتبقي: <span class="font-bold text-gray-900">{{ $remaining }}</span> طالب</span>
                <form method="POST" action="{{ route('assistant.attendance.close', $session) }}" class="hidden" id="closeSessionForm{{ $session->id }}">@csrf</form>
                <button type="button" class="px-5 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium"
                        data-bs-toggle="modal" data-bs-target="#closeSessionModal{{ $session->id }}">إنهاء الحضور</button>
                <x-confirm-modal
                    :id="'closeSessionModal' . $session->id"
                    title="إنهاء الحضور"
                    body="هل أنت متأكد من إنهاء جلسة الحضور هذه؟ سيتم تسجيل الطلاب غير المسجلين كغياب."
                    confirm-text="إنهاء"
                    cancel-text="إلغاء"
                    :form-id="'closeSessionForm' . $session->id"
                />
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">الحضور المسجل</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">كود الطالب</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($enrollments as $enrollment)
                                @php $record = $recordsByStudent->get($enrollment->student_id); @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->student->student_code }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($record)
                                            <span class="px-2 py-0.5 text-xs rounded-full @if ($record->isPresent()) bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                                                {{ $record->isPresent() ? 'حاضر' : 'غائب' }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">لا يوجد طلاب في هذه المجموعة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-dashboard-layout>