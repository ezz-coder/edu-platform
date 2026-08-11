<x-dashboard-layout title="لوحة التحكم">
    <div class="space-y-8">
        <x-page-header title="لوحة تحكم الأدمن" description="مرحباً بك في لوحة التحكم، {{ $currentUser->name }}" />

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Teachers -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">إجمالي المدرسين</p>
                        <p class="stat-value">{{ $stats['teachers'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-people text-xl text-primary-600"></i>
                    </div>
                </div>
            </div>

            <!-- Students -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">إجمالي الطلاب</p>
                        <p class="stat-value">{{ $stats['students'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-success-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-person-badge text-xl text-success-600"></i>
                    </div>
                </div>
            </div>

            <!-- Active Groups -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">المجموعات النشطة</p>
                        <p class="stat-value">{{ $stats['active_groups'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-info-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-collection text-xl text-info-600"></i>
                    </div>
                </div>
            </div>

            <!-- Resources -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">الموارد التعليمية</p>
                        <p class="stat-value">{{ $stats['resources'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-warning-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-folder2 text-xl text-warning-600"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Teachers -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">مدرسون منتظرون</p>
                        <p class="stat-value text-warning-600">{{ $stats['pending_teachers'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-warning-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-hourglass-split text-xl text-warning-600"></i>
                    </div>
                </div>
            </div>

            <!-- Suspended Teachers -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">مدرسون معلقون</p>
                        <p class="stat-value text-danger-600">{{ $stats['suspended_teachers'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-danger-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-person-x text-xl text-danger-600"></i>
                    </div>
                </div>
            </div>

            <!-- Exams -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">الامتحانات المنشورة</p>
                        <p class="stat-value">{{ $stats['published_exams'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-file-earmark-text text-xl text-primary-600"></i>
                    </div>
                </div>
            </div>

            <!-- Assistants -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">المساعدون</p>
                        <p class="stat-value">{{ $stats['assistants'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-info-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-person-workspace text-xl text-info-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: Additional Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <p class="stat-label">الحجوزات</p>
                <p class="stat-value">{{ $stats['reservations'] }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">جلسات الحضور</p>
                <p class="stat-value">{{ $stats['attendance_sessions'] }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">الطلاب غير النشطين</p>
                <p class="stat-value text-danger-600">{{ $stats['inactive_students'] }}</p>
            </div>
        </div>

        @if ($latestTerm)
        <div class="stat-card">
            <p class="stat-label">آخر فصل دراسي</p>
            <p class="text-lg font-bold text-text-primary mt-1">{{ $latestTerm->title }} - {{ $latestTerm->year }}</p>
            <span class="badge {{ $latestTerm->status === 'active' ? 'badge-success' : 'badge-neutral' }} mt-2">
                {{ $latestTerm->status === 'active' ? 'نشط' : 'مؤرشف' }}
            </span>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card p-6">
            <h3 class="section-title">إجراءات سريعة</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.teacher-requests') }}" class="btn-primary">
                    <i class="bi bi-person-check"></i> طلبات المدرسين
                </a>
                <a href="{{ route('admin.teachers') }}" class="btn-primary">
                    <i class="bi bi-people"></i> إدارة المدرسين
                </a>
                <a href="{{ route('admin.activity-log') }}" class="btn-secondary">
                    <i class="bi bi-activity"></i> سجل النشاط
                </a>
                <a href="{{ route('admin.settings') }}" class="btn-secondary">
                    <i class="bi bi-gear"></i> الإعدادات
                </a>
            </div>
        </div>
    </div>
</x-dashboard-layout>