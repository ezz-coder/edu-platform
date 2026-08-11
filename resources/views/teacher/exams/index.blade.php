<x-dashboard-layout title="الامتحانات">
    <x-page-header
        title="الامتحانات"
        description="اختباراتك ومنجزات طلابك"
        :action="'<a href=\''.route('teacher.exams.create').'\' class=\'btn-primary\'><i class=\'bi bi-plus-lg\'></i> امتحان</a>'"
    />

    @if (session('success'))
        <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('teacher.exams.recycle-bin') }}" class="text-text-secondary hover:text-primary-600 text-sm transition-colors duration-150"><i class="bi bi-trash"></i> سلة المحذوفات - الاختبارات</a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <p class="stat-label">عدد الامتحانات</p>
            <p class="stat-value">{{ $total }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">المنشورة</p>
            <p class="stat-value text-success-600">{{ $published }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">المخفية</p>
            <p class="stat-value text-text-muted">{{ $hidden }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">أنهوا الامتحان</p>
            <p class="stat-value text-primary-600">{{ $finishedStudents }}</p>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>المدة</th>
                    <th>الأسئلة</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($exams as $exam)
                    <tr>
                        <td class="font-medium text-text-primary">{{ $exam->title }}</td>
                        <td>{{ $exam->duration_minutes }} د</td>
                        <td>{{ $exam->questions_count }}</td>
                        <td>
                            <span class="badge {{ $exam->status === 'visible' ? 'badge-success' : 'badge-neutral' }}">
                                {{ $exam->status === 'visible' ? 'منشور' : 'مخفي' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('teacher.exams.attempts', $exam) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors duration-150">المحاولات</a>
                                <a href="{{ route('teacher.exams.questions', $exam) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors duration-150">الأسئلة</a>
                                <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors duration-150">تعديل</a>
                                <form method="POST" action="{{ route('teacher.exams.duplicate', $exam) }}" class="inline">
                                    @csrf
                                    <button class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors duration-150">نسخ</button>
                                </form>
                                <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" class="hidden" id="delExam{{ $exam->id }}">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button" class="text-danger-600 hover:text-danger-700 font-medium text-sm transition-colors duration-150" data-bs-toggle="modal" data-bs-target="#delExamModal{{ $exam->id }}">حذف</button>
                                <x-confirm-modal
                                    :id="'delExamModal' . $exam->id"
                                    title="حذف الامتحان"
                                    body="سيتم حذف الامتحان وجميع أسئلته ومحاولات الطلاب. لا يمكن التراجع."
                                    confirm-text="حذف"
                                    cancel-text="إلغاء"
                                    :form-id="'delExam' . $exam->id"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="bi-file-earmark-text" title="لا توجد امتحانات بعد" message="أنشئ أول امتحان لطلابك." :action="'<a href=\''.route('teacher.exams.create').'\' class=\'btn-primary\'><i class=\'bi bi-plus-lg\'></i> امتحان</a>'" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $exams->links() }}
    </div>
</x-dashboard-layout>