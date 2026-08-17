<x-dashboard-layout title="الدروس">
    <x-page-header
        title="الدروس"
        description="كل الدروس المسموح لك بإدارتها"
        :action="'<a href=\''.route('assistant.lessons.create').'\' class=\'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> درس جديد</a>'"
    />

    <div class="mb-4">
        <a href="{{ route('assistant.lessons.recycle-bin') }}" class="text-gray-600 hover:underline">سلة المحذوفات - الدروس</a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">الترتيب</th>
                    <th class="px-4 py-3">العنوان</th>
                    <th class="px-4 py-3">المجموعة</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($lessons as $lesson)
                    <tr>
                        <td class="px-4 py-3 text-gray-600">{{ $lesson->display_order }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $lesson->title }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $lesson->group->title }}</td>
                        <td class="px-4 py-3">
                            @if ($lesson->is_active)
                                <span class="text-green-700">فعال</span>
                            @else
                                <span class="text-gray-500">متوقف</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('assistant.lessons.move-up', $lesson) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-600 hover:underline" title="تحريك للأعلى">↑</button>
                                </form>
                                <form method="POST" action="{{ route('assistant.lessons.move-down', $lesson) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-600 hover:underline" title="تحريك للأسفل">↓</button>
                                </form>
                                <a href="{{ route('assistant.lessons.edit', $lesson) }}" class="text-blue-600 hover:underline">تعديل</a>
                                <form method="POST" action="{{ route('assistant.lessons.destroy', $lesson) }}" class="hidden" id="delLesson{{ $lesson->id }}">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#delLessonModal{{ $lesson->id }}">حذف</button>
                                <x-confirm-modal
                                    :id="'delLessonModal' . $lesson->id"
                                    title="حذف الدرس"
                                    body="سيتم نقل الدرس إلى سلة المحذوفات. الموارد المرتبطة به تصبح مصادر عامة."
                                    confirm-text="حذف"
                                    cancel-text="إلغاء"
                                    :form-id="'delLesson' . $lesson->id"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8">
                            <x-empty-state icon="bi-book" title="لا توجد دروس بعد" message="ابدأ بإنشاء أول درس." :action="'<a href=\''.route('assistant.lessons.create').'\' class=\'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> درس جديد</a>'" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-dashboard-layout>