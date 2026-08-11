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
