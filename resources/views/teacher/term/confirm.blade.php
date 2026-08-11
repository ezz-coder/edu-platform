<x-dashboard-layout title="تأكيد إنهاء الترم">
    <div class="space-y-6">
        <a href="{{ route('teacher.term.end') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            رجوع
        </a>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">ملخص إنهاء الترم</h1>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">الترم الحالي</p>
                    <p class="font-medium text-gray-900">{{ $term->title }} ({{ $term->year }})</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">عدد المجموعات</p>
                    <p class="font-medium text-gray-900">{{ $allGroups }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">عدد الطلاب</p>
                    <p class="font-medium text-gray-900">{{ $studentsCount }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">عدد الجلسات</p>
                    <p class="font-medium text-gray-900">{{ $sessionsCount }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">عدد سجلات الحضور</p>
                    <p class="font-medium text-gray-900">{{ $recordsCount }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">وضع التنفيذ</p>
                    <p class="font-medium text-gray-900">{{ $mode === 'keep_groups' ? 'إبقاء المجموعات' : 'مجموعات جديدة' }}</p>
                </div>
            </div>

            @if ($mode === 'keep_groups' && $selectedGroups->isNotEmpty())
                <div class="mb-6">
                    <p class="font-medium text-gray-900 mb-2">المجموعات المختارة للنقل</p>
                    <ul class="space-y-1 text-sm text-gray-600">
                        @foreach ($selectedGroups as $g)
                            <li>• {{ $g->title }} ({{ $g->current_students_count }} طالب)</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('teacher.term.execute') }}">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">
                @foreach ($selectedGroups as $g)
                    <input type="hidden" name="group_ids[]" value="{{ $g->id }}">
                @endforeach

                <div class="flex items-center gap-3">
                    <a href="{{ route('teacher.term.end') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">إلغاء</a>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">متابعة (تأكيد نهائي)</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>