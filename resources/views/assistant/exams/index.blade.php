<x-dashboard-layout title="الامتحانات">
    <x-page-header
        title="الامتحانات"
        description="الامتحانات التي يديرها المدرس"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('assistant.exams.recycle-bin') }}" class="text-gray-600 hover:underline">سلة المحذوفات - الاختبارات</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">العنوان</th>
                    <th class="px-4 py-3">المدة</th>
                    <th class="px-4 py-3">الأسئلة</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($exams as $exam)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $exam->title }}</td>
                        <td class="px-4 py-3">{{ $exam->duration_minutes }} د</td>
                        <td class="px-4 py-3">{{ $exam->questions_count }}</td>
                        <td class="px-4 py-3">{{ $exam->status === 'visible' ? 'منشور' : 'مخفي' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('assistant.exams.questions', $exam) }}" class="text-blue-600 hover:underline">الأسئلة</a>
                                <a href="{{ route('assistant.exams.edit', $exam) }}" class="text-blue-600 hover:underline">تعديل</a>
                                <form method="POST" action="{{ route('assistant.exams.publish', $exam) }}" class="inline">
                                    @csrf
                                    <button class="text-green-600 hover:underline" @if($exam->status === 'visible') disabled @endif>نشر</button>
                                </form>
                                <form method="POST" action="{{ route('assistant.exams.close', $exam) }}" class="inline">
                                    @csrf
                                    <button class="text-orange-600 hover:underline" @if($exam->status === 'hidden' && !$exam->trashed()) disabled @endif>إيقاف</button>
                                </form>
                                <form method="POST" action="{{ route('assistant.exams.archive', $exam) }}" class="inline">
                                    @csrf
                                    <button class="text-yellow-600 hover:underline">أرشفة</button>
                                </form>
                                @if ($exam->trashed())
                                    <form method="POST" action="{{ route('assistant.exams.restore', $exam) }}" class="inline">
                                        @csrf
                                        <button class="text-emerald-600 hover:underline">استرجاع</button>
                                    </form>
                                    <form method="POST" action="{{ route('assistant.exams.force-delete', $exam) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">حذف نهائي</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('assistant.exams.destroy', $exam) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">حذف</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8">
                        <x-empty-state icon="bi-file-earmark-text" title="لا توجد امتحانات بعد" message="لم يتم إنشاء امتحانات للمدرس." />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $exams->links() }}
</x-dashboard-layout>