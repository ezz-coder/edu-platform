<x-dashboard-layout title="سجل التحويلات">
    <div class="space-y-6">
        <a href="{{ route('teacher.students.show', $enrollment) }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            العودة لملف الطالب
        </a>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h1 class="text-xl font-bold text-gray-900">{{ $enrollment->student->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">المجموعة: {{ $enrollment->group->title }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">سجل التحويلات (غير قابل للتعديل أو الحذف)</h2>
            @if ($enrollment->histories->isEmpty())
                <x-empty-state icon="bi-clock-history" title="لا يوجد سجل" message="لم يتم تسجيل أي حركة على هذا التسجيل بعد." />
            @else
                <div class="space-y-3">
                    @foreach ($enrollment->histories as $h)
                        <div class="flex items-start gap-3 border border-gray-100 rounded-lg p-4">
                            <span class="mt-1 w-3 h-3 rounded-full shrink-0
                                @if ($h->action === 'moved') bg-blue-500
                                @elseif ($h->action === 'restored') bg-green-500
                                @elseif ($h->action === 'removed' || $h->action === 'attendance_removal') bg-red-500
                                @else bg-gray-500 @endif"></span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <p class="text-sm font-semibold text-gray-900">{{ $h->actionLabel() }}</p>
                                    <span class="text-xs text-gray-400">{{ $h->changed_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if ($h->fromGroup) من: {{ $h->fromGroup->title }} @endif
                                    @if ($h->toGroup && $h->toGroup->id !== $h->fromGroup?->id) ← إلى: {{ $h->toGroup->title }} @endif
                                    @if (!$h->fromGroup && !$h->toGroup) — @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-1">بواسطة: {{ $h->performed_by ?? 'النظام' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>