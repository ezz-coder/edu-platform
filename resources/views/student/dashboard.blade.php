<x-dashboard-layout title="لوحة الطالب">
    <div class="space-y-6">
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Workspace Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
            <!-- Top left: switch account -->
            <a href="{{ route('student.accounts') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4"></path></svg>
                تبديل الحساب
            </a>

            <!-- Top right: current teacher -->
            <div class="text-left">
                <p class="text-lg font-bold text-gray-900">{{ $account->enrollments->first()?->group?->teacher?->name ?? '—' }}</p>
                <p class="text-sm text-gray-500">كود المدرس: {{ $account->enrollments->first()?->group?->teacher?->teacher_code ?? '—' }}</p>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900">مرحباً بك، {{ $account->name }}</h1>
            <p class="mt-2 text-gray-600">أهلاً بك في لوحة تحكم الطالب</p>
        </div>

        <!-- My Enrollment (selected account only) -->
        @php
            $enrollment = $account->enrollments()->with(['group', 'group.teacher', 'requestedGroup'])->where('status', '!=', 'removed')->first();
        @endphp

        @if ($enrollment)
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $enrollment->group->title }}</h3>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                        @if ($enrollment->status === 'enrolled') bg-green-100 text-green-800
                        @elseif ($enrollment->status === 'pending_transfer') bg-yellow-100 text-yellow-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ match($enrollment->status) {
                            'enrolled' => 'مسجل',
                            'pending_transfer' => 'طلب نقل معلق',
                            'removed' => 'مزال',
                            default => '',
                        } }}
                    </span>
                </div>

                <div class="space-y-2 text-sm text-gray-600">
                    <p><span class="font-medium text-gray-700">كود الطالب:</span> {{ $account->student_code }}</p>
                    <p><span class="font-medium text-gray-700">المدرس:</span> {{ $enrollment->group->teacher->name }}</p>
                    <p><span class="font-medium text-gray-700">كود المدرس:</span> {{ $enrollment->group->teacher->teacher_code }}</p>
                    <p><span class="font-medium text-gray-700">المجموعة:</span> {{ $enrollment->group->title }}</p>
                    <p><span class="font-medium text-gray-700">اليوم:</span> {{ $enrollment->group->day }}</p>
                    <p><span class="font-medium text-gray-700">الوقت:</span> {{ $enrollment->group->start_time }} - {{ $enrollment->group->end_time }}</p>
                    <p><span class="font-medium text-gray-700">المرحلة:</span> {{ $enrollment->group->grade_level }}</p>
                </div>

                @if ($enrollment->status === 'pending_transfer')
                    <div class="mt-4 p-3 bg-yellow-50 rounded-lg text-sm text-yellow-800">
                        طلب النقل إلى مجموعة "{{ $enrollment->requestedGroup->title }}" قيد المراجعة من المدرس.
                    </div>
                @elseif ($enrollment->status === 'enrolled')
                    <a href="{{ route('student.transfer.create', $enrollment->group) }}"
                       class="inline-block mt-4 text-blue-600 hover:text-blue-900 text-sm font-medium">
                        طلب نقل إلى مجموعة أخرى
                    </a>
                @endif
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-6">
                <p class="text-gray-500">أنت غير مسجل في أي مجموعة بعد.</p>
                <a href="{{ route('register.student') }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">سجّل في مجموعة الآن</a>
            </div>
        @endif
    </div>
</x-dashboard-layout>