<x-dashboard-layout title="لوحة التحكم">
    <div class="space-y-8">
        <x-page-header title="لوحة التحكم" description="نظرة عامة على مجموعاتك وأنشطتك" />

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="stat-label">المجموعات</p>
                <p class="stat-value">{{ $stats['groups'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">الطلاب</p>
                <p class="stat-value">{{ $stats['enrollments'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">الامتحانات</p>
                <p class="stat-value">{{ $stats['exams'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">الحضور</p>
                <p class="stat-value">{{ $stats['attendance_sessions'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Recycle Bin Access -->
        <div class="card p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-text-primary">سلة المحذوفات</h2>
                    <p class="text-sm text-text-secondary mt-1">يمكنك استرجاع العناصر المحذوفة أو حذفها نهائياً.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.lessons.recycle-bin') }}" class="btn-secondary btn-sm">
                        <i class="bi bi-book"></i> الدروس
                    </a>
                    <a href="{{ route('teacher.resources.recycle-bin') }}" class="btn-secondary btn-sm">
                        <i class="bi bi-folder2"></i> المصادر
                    </a>
                    <a href="{{ route('teacher.exams.recycle-bin') }}" class="btn-secondary btn-sm">
                        <i class="bi bi-file-earmark-text"></i> الامتحانات
                    </a>
                    <a href="{{ route('teacher.reservations.recycle-bin') }}" class="btn-secondary btn-sm">
                        <i class="bi bi-calendar-check"></i> الحجوزات
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-text-primary">آخر النشاطات</h2>
                    <p class="text-sm text-text-secondary mt-1">آخر 15 نشاطاً قمت به على المنصة.</p>
                </div>
            </div>

            @if($activities && $activities->count() > 0)
                <div class="space-y-3">
                    @foreach($activities as $activity)
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-bg-secondary/50 border border-border-subtle">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="bi bi-activity text-primary text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <p class="font-medium text-text-primary text-sm">{{ $activity->category }}</p>
                                    <span class="text-xs text-text-secondary whitespace-nowrap">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-sm text-text-secondary mt-1">{{ $activity->description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-text-secondary">
                    <i class="bi bi-inbox text-4xl mb-2 opacity-50"></i>
                    <p>لا توجد نشاطات حتى الآن.</p>
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>