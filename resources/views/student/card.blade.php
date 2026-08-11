<x-dashboard-layout title="بطاقة الطالب">
    <div class="max-w-md mx-auto space-y-4">
        <x-page-header
            title="بطاقة الطالب"
            description="هذه البطاقة تحدد هوية الطالب فقط ولا تحدد المجموعة"
        />

        @include('components.student-card', [
            'account' => $account,
            'enrollment' => $enrollment,
            'qr' => $qr,
        ])

        <div class="flex gap-2 print:hidden">
            <button type="button" onclick="window.print()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm text-center">
                <i class="bi bi-printer"></i> طباعة البطاقة
            </button>
            <a href="{{ route('student.account') }}" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm text-center">
                العودة للحساب
            </a>
        </div>
    </div>
</x-dashboard-layout>