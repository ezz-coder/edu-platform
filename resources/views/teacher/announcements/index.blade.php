<x-dashboard-layout>
    <x-slot name="header">الإعلانات</x-slot>
    <x-slot name="title">الإعلانات</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        @if(session('success'))
            <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3">{{ session('success') }}</div>
        @endif

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-text-primary">الإعلانات</h1>
            <a href="{{ route('teacher.announcements.create') }}" class="btn-primary btn-sm">+ إعلان جديد</a>
        </div>

        @if($announcements->isEmpty())
            <div class="bg-bg-card border border-border-light rounded-lg p-6 text-center text-text-muted">
                لا توجد إعلانات بعد.
            </div>
        @else
            <div class="space-y-3">
                @foreach($announcements as $announcement)
                    <div class="bg-bg-card border border-border-light rounded-lg p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h2 class="font-semibold text-text-primary">{{ $announcement->title }}</h2>
                                <p class="text-sm text-text-secondary mt-1 whitespace-pre-line">{{ $announcement->body }}</p>
                                <p class="text-xs text-text-muted mt-2">
                                    المجموعات: {{ $announcement->groups->pluck('title')->implode('، ') }}
                                    • نُشر: {{ $announcement->published_at?->format('Y-m-d H:i') }}
                                </p>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <a href="{{ route('teacher.announcements.edit', $announcement) }}" class="btn-ghost btn-sm">تعديل</a>
                                <form method="POST" action="{{ route('teacher.announcements.destroy', $announcement) }}"
                                      onsubmit="return confirm('هل أنت متأكد من حذف الإعلان؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost btn-sm text-red-600">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>