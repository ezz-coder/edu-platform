<x-dashboard-layout title="المصادر">
    <div class="space-y-8">
        @if ($group)
            <div class="card p-4 text-sm text-text-secondary">
                حسابك النشط: <span class="font-semibold text-text-primary">{{ $account->name }}</span> — مجموعة: <span class="font-semibold text-text-primary">{{ $group->title }}</span>
            </div>
        @else
            <div class="card p-4 text-sm text-text-secondary">
                لا توجد مجموعة نشطة لهذا الحساب.
            </div>
        @endif

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <!-- Sprint 68: Latest announcements targeted at this student's group -->
        @if ($announcements->isNotEmpty())
            <div>
                <h2 class="section-title">📢 آخر الإعلانات</h2>
                <div class="space-y-3">
                    @foreach ($announcements as $announcement)
                        <div class="card p-4">
                            <h3 class="font-semibold text-text-primary">{{ $announcement->title }}</h3>
                            <p class="text-sm text-text-secondary mt-1 whitespace-pre-line">{{ $announcement->body }}</p>
                            <p class="text-xs text-text-muted mt-2">{{ $announcement->published_at?->format('Y-m-d H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Sprint 69: Latest unread notifications summary -->
        @if ($unreadCount > 0)
            <div>
                <h2 class="section-title">🔔 الإشعارات غير المقروءة ({{ $unreadCount }})</h2>
                <div class="space-y-3">
                    @foreach ($unreadNotifications as $notification)
                        <a href="{{ route('notifications.open', $notification) }}" class="card p-4 block hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-text-primary">{{ $notification->title }}</h3>
                                <span class="text-xs text-text-muted">{{ $notification->relativeTime() }}</span>
                            </div>
                            @if ($notification->message)
                                <p class="text-sm text-text-secondary mt-1">{{ $notification->message }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('notifications.index') }}" class="text-sm text-blue-600 hover:underline mt-2 inline-block">
                    عرض كل الإشعارات
                </a>
            </div>
        @endif

        <!-- Sprint 63: Search & Filters -->
        <form method="GET" action="{{ route('student.resources.home') }}" class="card p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs text-text-muted mb-1">بحث بالعنوان</label>
                <input type="text" name="q" value="{{ $filterQ }}" placeholder="اكتب عنوان المورد..." class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-text-muted mb-1">النوع</label>
                <select name="type" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">كل الأنواع</option>
                    @foreach (['pdf' => 'PDF', 'image' => 'صورة', 'zip' => 'ملف مضغوط', 'other' => 'ملف آخر', 'video_link' => 'فيديو', 'google_drive' => 'Google Drive', 'external_link' => 'رابط خارجي'] as $t => $label)
                        <option value="{{ $t }}" {{ $filterType === $t ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-text-muted mb-1">الدرس</label>
                <select name="lesson_id" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">كل الدروس</option>
                    @foreach ($filterLessons as $fl)
                        <option value="{{ $fl->id }}" {{ (string) $filterLessonId === (string) $fl->id ? 'selected' : '' }}>{{ $fl->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-text-muted mb-1">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $filterDateFrom }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-text-muted mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $filterDateTo }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary btn-sm flex-1">بحث / فلترة</button>
                <a href="{{ route('student.resources.home') }}" class="btn-secondary btn-sm">مسح</a>
            </div>
        </form>

        @if ($hasFilters)
            <!-- Sprint 63: filtered results (flat list) -->
            @php
                $filtered = $generalResources->concat($lessons->flatMap(fn ($item) => $item['resources']));
            @endphp
            <div>
                <h2 class="section-title">🔍 نتائج البحث والفلترة ({{ $filtered->count() }})</h2>
                @if ($filtered->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($filtered as $resource)
                            @include('student.resources.card', ['resource' => $resource])
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-search" title="لا توجد نتائج" message="جرّب تعديل كلمة البحث أو الفلاتر." />
                @endif
            </div>
        @else
        <!-- General Resources -->
        <div>
            <h2 class="section-title">📌 مصادر عامة</h2>
            @if ($generalResources->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($generalResources as $resource)
                        @include('student.resources.card', ['resource' => $resource])
                    @endforeach
                </div>
            @else
                <x-empty-state icon="bi-file-earmark" title="لا توجد مصادر عامة" message="ستظهر المصادر غير المرتبطة بدرس هنا." />
            @endif
        </div>

        <!-- Lessons -->
        @if ($lessons->isNotEmpty())
            @foreach ($lessons as $item)
                <div>
                    <h2 class="section-title">📚 {{ $item['lesson']->title }}</h2>
                    @if ($item['resources']->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($item['resources'] as $resource)
                                @include('student.resources.card', ['resource' => $resource])
                            @endforeach
                        </div>
                    @else
                        <x-empty-state icon="bi-folder" title="لا توجد مصادر في هذا الدرس" message="سيتم إضافة المصادر هنا." />
                    @endif
                </div>
            @endforeach
        @endif
        @endif

        <!-- Exams -->
        <div>
            <h2 class="section-title">الامتحانات</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($exams as $exam)
                    @php
                        $attempt = $exam->attempts->first();
                        $now = now();
                        $notStarted = $exam->starts_at && $now->lt($exam->starts_at);
                        $ended = $exam->ends_at && $now->gt($exam->ends_at);
                    @endphp
                    <div class="card-hover p-4">
                        <h3 class="font-semibold text-text-primary">{{ $exam->title }}</h3>
                        <p class="text-xs text-text-muted mt-1">المدة: {{ $exam->duration_minutes }} دقيقة</p>
                        <p class="text-xs text-text-muted">من: {{ $exam->starts_at?->format('Y-m-d H:i') ?? '—' }}</p>
                        <p class="text-xs text-text-muted">إلى: {{ $exam->ends_at?->format('Y-m-d H:i') ?? '—' }}</p>
                        <div class="mt-3">
                            @if ($notStarted)
                                <span class="badge badge-neutral">سيبدأ لاحقاً</span>
                            @elseif ($ended)
                                <span class="badge badge-danger">منتهي</span>
                            @elseif ($attempt && $attempt->isFinished())
                                <span class="badge badge-success">تم الحل</span>
                            @else
                                <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                                    @csrf
                                    <button class="btn-primary btn-sm w-full">ابدأ الامتحان</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="bi-file-earmark-text" title="لا توجد امتحانات متاحة" message="ستظهر الامتحانات المتاحة لك هنا." />
                @endforelse
            </div>
             <div class="pagination">
                 @if($exams instanceof \Illuminate\Pagination\LengthAwarePaginator)
                     {{ $exams->links() }}
                 @endif
             </div>
         </div>
     </div>
 </x-dashboard-layout>