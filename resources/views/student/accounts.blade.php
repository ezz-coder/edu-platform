<x-dashboard-layout title="اختيار الحساب">
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900">اختيار الحساب</h1>
            <p class="mt-2 text-gray-600">اختر الحساب الذي تريد الدخول إليه</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($accounts as $acc)
                @php
                    $enrollment = $acc->enrollments->first();
                    $group = $enrollment?->group;
                @endphp
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="space-y-2 text-sm text-gray-600">
                        <p><span class="font-medium text-gray-700">المدرس:</span> {{ $group?->teacher?->name ?? '—' }}</p>
                        <p><span class="font-medium text-gray-700">كود المدرس:</span> {{ $group?->teacher?->teacher_code ?? '—' }}</p>
                        <p><span class="font-medium text-gray-700">اسم الطالب:</span> {{ $acc->name }}</p>
                        <p><span class="font-medium text-gray-700">كود الطالب:</span> {{ $acc->student_code }}</p>
                        <p><span class="font-medium text-gray-700">المجموعة:</span> {{ $group?->title ?? '—' }}</p>
                        <p><span class="font-medium text-gray-700">المرحلة:</span> {{ $group?->grade_level ?? '—' }}</p>
                        <p><span class="font-medium text-gray-700">حالة الحساب:</span>
                            <span class="px-2 py-0.5 text-xs rounded-full
                                @if ($acc->status === 'active') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $acc->status === 'active' ? 'نشط' : $acc->status }}
                            </span>
                        </p>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <form method="POST" action="{{ route('student.accounts.switch') }}">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $acc->id }}">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">دخول</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <a href="{{ route('register.student') }}" class="inline-flex items-center text-blue-600 hover:text-blue-900 text-sm font-medium">
                تسجيل حساب جديد مع مدرس آخر
            </a>
        </div>
    </div>
</x-dashboard-layout>