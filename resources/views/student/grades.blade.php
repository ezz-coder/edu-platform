<x-dashboard-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Summary -->
        <div class="bg-bg-card rounded-card border border-border-light p-5 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-text-primary">درجاتي</h2>
                <p class="text-sm text-text-muted">آخر الامتحانات المؤداة ({{ $examAttempts->count() }})</p>
            </div>
            <a href="{{ route('student.report') }}" class="btn-ghost btn-sm">التقرير الكامل</a>
        </div>

        @if ($examAttempts->isEmpty())
            <div class="bg-bg-card rounded-card border border-border-light p-8 text-center text-text-muted">
                لا توجد درجات بعد.
            </div>
        @else
            <div class="bg-bg-card rounded-card border border-border-light divide-y divide-border-light">
                @foreach ($examAttempts as $attempt)
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <p class="font-medium text-text-primary">{{ $attempt->exam?->title ?? 'امتحان' }}</p>
                            <p class="text-xs text-text-muted">{{ optional($attempt->finished_at)->translatedFormat('j M Y — g:i A') }}</p>
                        </div>
                        <div class="text-left">
                            <span class="inline-block px-3 py-1 rounded-full bg-primary-100 text-primary-700 font-bold text-sm" dir="ltr">
                                {{ $attempt->score }} / {{ $attempt->total_marks }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>