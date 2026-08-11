<x-dashboard-layout title="حسابي">
    <div class="max-w-4xl mx-auto space-y-4">
        <x-page-header
            title="حسابي"
            description="ملخص بياناتك ومسيرتك في مجموعات المدرس"
            :action="$currentUser->role === 'student'
                ? '<a href=\''.route('student.accounts').'\' class=\'px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm\'><i class=\'bi bi-arrow-left-right\'></i> تبديل الحساب</a>'
                : null"
        />

        <!-- Identity card -->
        <div class="bg-white rounded-lg shadow-sm p-6 flex flex-wrap items-center gap-4">
            <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">{{ mb_substr($account->name, 0, 1) }}</div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900">{{ $account->name }}</h2>
                <p class="text-sm text-gray-500">كود الطالب: {{ $account->student_code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('student.card') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm"><i class="bi bi-person-badge"></i> بطاقة الطالب</a>
                <a href="{{ route('student.report') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"><i class="bi bi-download"></i> تحميل التقرير</a>
            </div>
        </div>

            <!-- Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 text-sm space-y-2">
                    <h3 class="font-semibold text-gray-800 mb-2"><i class="bi bi-info-circle"></i> البيانات الأساسية</h3>
                    <p><span class="text-gray-500">كود الطالب:</span> <span class="font-medium text-gray-900">{{ $account->student_code ?? '—' }}</span></p>
                    <p><span class="text-gray-500">كود الكارنيه:</span> <span class="font-medium text-gray-900 font-mono">{{ $enrollment?->card_id ?? '—' }}</span></p>
                    <p><span class="text-gray-500">رمز QR:</span> <span class="font-medium text-gray-900 font-mono text-xs break-all">{{ $enrollment?->qr_token ?? '—' }}</span></p>
                    <p><span class="text-gray-500">المدرس:</span> <span class="font-medium text-gray-900">{{ $enrollment?->group?->teacher?->name ?? '—' }}</span></p>
                    <p><span class="text-gray-500">المجموعة الحالية:</span> <span class="font-medium text-gray-900">{{ $enrollment?->group?->title ?? '—' }}</span></p>
                    <p><span class="text-gray-500">الترم الحالي:</span> <span class="font-medium text-gray-900">{{ $enrollment?->term?->name ?? '—' }}</span></p>
                    <p><span class="text-gray-500">تاريخ التسجيل:</span> <span class="font-medium text-gray-900">{{ $enrollment?->created_at?->format('Y-m-d') ?? '—' }}</span></p>
                    <p><span class="text-gray-500">حالة التسجيل:</span>
                        <span class="font-medium text-gray-900">
                            {{ match($enrollment?->status) { 'enrolled' => 'مسجل', 'pending_transfer' => 'نقل معلق', 'removed' => 'مزال يدوياً', 'inactive_due_to_absence' => 'مزال لغياب', 'inactive_due_to_teacher' => 'مزال بواسطة المدرس', default => '—' } }}
                        </span>
                    </p>
                    @if ($enrollment?->inactivated_at)
                        <p><span class="text-gray-500">تاريخ الإزالة:</span> <span class="font-medium text-gray-900">{{ $enrollment->inactivated_at->format('Y-m-d H:i') }}</span></p>
                    @endif
                    @if ($enrollment?->isInactive())
                        <p><span class="text-gray-500">حالة الاسترجاع:</span> <span class="font-medium text-red-700">مزال — يمكن استرجاعه من قبل المدرس</span></p>
                    @else
                        <p><span class="text-gray-500">حالة الاسترجاع:</span> <span class="font-medium text-green-700">نشط</span></p>
                    @endif
                    <p><span class="text-gray-500">الهاتف:</span> <span class="font-medium text-gray-900">{{ $account->mobile ?? '—' }}</span></p>
                    <p><span class="text-gray-500">هاتف ولي الأمر:</span> <span class="font-medium text-gray-900">{{ $account->parent_mobile ?? '—' }}</span></p>
                </div>

            <!-- Attendance summary (PART 5) -->
            <div class="bg-white rounded-lg shadow-sm p-5 text-sm">
                <h3 class="font-semibold text-gray-800 mb-2"><i class="bi bi-calendar-check"></i> ملخص الحضور</h3>
                @if ($enrollment)
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-green-50 rounded-lg py-3"><p class="text-lg font-bold text-green-700">{{ $attendance['present'] ?? 0 }}</p><p class="text-xs text-gray-500">حضور</p></div>
                        <div class="bg-orange-50 rounded-lg py-3"><p class="text-lg font-bold text-orange-600">{{ $attendance['late'] ?? 0 }}</p><p class="text-xs text-gray-500">متأخر</p></div>
                        <div class="bg-red-50 rounded-lg py-3"><p class="text-lg font-bold text-red-600">{{ $attendance['absent'] ?? 0 }}</p><p class="text-xs text-gray-500">غياب</p></div>
                    </div>
                @else
                    <p class="text-gray-500">غير مسجل في مجموعة.</p>
                @endif
            </div>
        </div>

        <!-- Exam summary (PART 5) -->
        <div class="bg-white rounded-lg shadow-sm p-5 text-sm">
            <h3 class="font-semibold text-gray-800 mb-2"><i class="bi bi-file-earmark-text"></i> ملخص الامتحانات</h3>
            @if ($examAttempts->isNotEmpty())
                <div class="space-y-1">
                    @foreach ($examAttempts as $a)
                        <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                            <span class="text-gray-700">{{ $a->exam->title }}</span>
                            <span class="font-medium text-blue-700">{{ $a->score }}/{{ $a->total_marks }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">لا توجد امتحانات مؤداة بعد.</p>
            @endif
        </div>

        <!-- Reservation summary (PART 5) -->
        <div class="bg-white rounded-lg shadow-sm p-5 text-sm">
            <h3 class="font-semibold text-gray-800 mb-2"><i class="bi bi-calendar-event"></i> ملخص الحجوزات</h3>
            @if ($reservations->isNotEmpty())
                <div class="space-y-1">
                    @foreach ($reservations as $r)
                        <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                            <span class="text-gray-700">{{ $r->title }}</span>
                            <span class="text-gray-500">{{ $r->reserved_at?->format('Y-m-d') ?? '—' }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">لا توجد حجوزات بعد.</p>
            @endif
        </div>
    </div>
</x-dashboard-layout>