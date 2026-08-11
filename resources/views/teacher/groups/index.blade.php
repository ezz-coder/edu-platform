<x-dashboard-layout title="مجموعاتي">
    <x-page-header
        title="مجموعاتي"
        description="كل المجموعات التي تدرّسها"
        :action="'<a href=\''.route('teacher.groups.create').'\' class=\'btn-primary\'><i class=\'bi bi-plus-lg\'></i> مجموعة جديدة</a>'"
    />

    @if (session('success'))
        <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif

    @if ($groups->isEmpty())
        <x-empty-state icon="bi-collection" title="لا توجد مجموعات حالياً" message="ابدأ بإنشاء مجموعتك الأولى لإضافة الطلاب." :action="'<a href=\''.route('teacher.groups.create').'\' class=\'btn-primary\'><i class=\'bi bi-plus-lg\'></i> مجموعة جديدة</a>'" />
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($groups as $group)
                <div class="card-hover p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary">{{ $group->title }}</h3>
                            <p class="text-sm text-text-muted mt-1">{{ $group->grade_level }}</p>
                        </div>
                        <span class="badge {{ $group->status === 'active' ? 'badge-success' : ($group->status === 'archived' ? 'badge-neutral' : 'badge-warning') }}">
                            {{ match($group->status) { 'active' => 'نشط', 'archived' => 'مؤرشف', default => 'غير نشط' } }}
                        </span>
                    </div>

                    @if ($group->description)
                        <p class="text-text-secondary text-sm mb-4">{{ $group->description }}</p>
                    @endif

                    <div class="space-y-2 text-sm text-text-secondary mb-4">
                        <div class="flex items-center"><i class="bi bi-calendar3 ml-2 text-text-muted"></i> {{ $group->day }}</div>
                        <div class="flex items-center"><i class="bi bi-clock ml-2 text-text-muted"></i> {{ $group->start_time }} - {{ $group->end_time }}</div>
                        <div class="flex items-center"><i class="bi bi-person-badge ml-2 text-text-muted"></i> {{ match($group->gender_type) { 'boys' => 'بنين', 'girls' => 'بنات', 'mixed' => 'مشترك', default => '' } }}</div>
                        <div class="flex items-center"><i class="bi bi-people ml-2 text-text-muted"></i> الطلاب: {{ $group->currentStudents() }} / {{ $group->max_students }} (مقاعد شاغرة: {{ $group->remainingSeats() }})</div>
                    </div>

                    <div class="flex items-center flex-wrap gap-4 pt-4 border-t border-border-light">
                        <a href="{{ route('teacher.groups.show', $group) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors duration-150">عرض</a>

                        @if ($group->status === 'active')
                            <a href="{{ route('teacher.groups.edit', $group) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors duration-150">تعديل</a>
                            <form method="POST" action="{{ route('teacher.groups.archive', $group) }}" class="inline">
                                @csrf
                                <button class="text-warning-600 hover:text-warning-700 font-medium text-sm transition-colors duration-150">أرشفة</button>
                            </form>
                            <form method="POST" action="{{ route('teacher.groups.destroy', $group) }}" class="hidden" id="delGrp{{ $group->id }}">
                                @csrf @method('DELETE')
                            </form>
                            <button type="button" class="text-danger-600 hover:text-danger-700 font-medium text-sm transition-colors duration-150" data-bs-toggle="modal" data-bs-target="#delGrpModal{{ $group->id }}">حذف</button>
                            <x-confirm-modal
                                :id="'delGrpModal' . $group->id"
                                title="حذف المجموعة"
                                body="سيتم حذف المجموعة وكل ما يتعلق بها من طلاب وحصص. لا يمكن التراجع."
                                confirm-text="حذف"
                                cancel-text="إلغاء"
                                :form-id="'delGrp' . $group->id"
                            />
                        @elseif ($group->status === 'archived')
                            <form method="POST" action="{{ route('teacher.groups.restore', $group) }}" class="inline">
                                @csrf
                                <button class="text-success-600 hover:text-success-700 font-medium text-sm transition-colors duration-150">استرجاع</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-dashboard-layout>