<x-dashboard-layout title="الدروس">
     <x-page-header
             title="الدروس"
             description="اسحب وأفلت لتغيير الترتيب • أو استخدم زرّي الحركة"
             :action="'<a href=\''.route('teacher.lessons.create').'\' class=\'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> درس جديد</a>'"
     />

     <div class="mb-4">
         <a href="{{ route('teacher.lessons.recycle-bin') }}" class="text-gray-600 hover:underline">سلة المحذوفات - الدروس</a>
     </div>

     @if (session('success'))
         <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
     @endif

     <div class="bg-white rounded-lg shadow-sm overflow-hidden" id="lessons-drag-drop">
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
             <tbody class="divide-y divide-gray-100" id="lessons-tbody">
                 @forelse ($lessons as $lesson)
                     <tr data-lesson-id="{{ $lesson->id }}" class="cursor-move">
                         <td class="px-4 py-3 text-gray-600 order-cell">{{ $lesson->display_order }}</td>
                         <td class="px-4 py-3 font-medium text-gray-900">{{ $lesson->title }}</td>
                         <td class="px-4 py-3 text-gray-600">{{ $lesson->group->title ?? '—' }}</td>
                         <td class="px-4 py-3">
                             @if ($lesson->is_active)
                                 <span class="text-green-700">فعال</span>
                             @else
                                 <span class="text-gray-500">متوقف</span>
                             @endif
                         </td>
                         <td class="px-4 py-3">
                             <div class="flex flex-wrap gap-2">
                                 <form method="POST" action="{{ route('teacher.lessons.move-up', $lesson) }}" class="inline">
                                     @csrf
                                     <button type="submit" class="text-gray-600 hover:underline" title="تحريك للأعلى">↑</button>
                                 </form>
                                 <form method="POST" action="{{ route('teacher.lessons.move-down', $lesson) }}" class="inline">
                                     @csrf
                                     <button type="submit" class="text-gray-600 hover:underline" title="تحريك للأسفل">↓</button>
                                 </form>
                                 <a href="{{ route('teacher.lessons.edit', $lesson) }}" class="text-blue-600 hover:underline">تعديل</a>
                                 <form method="POST" action="{{ route('teacher.lessons.destroy', $lesson) }}" class="hidden" id="delLesson{{ $lesson->id }}">
                                     @csrf @method('DELETE')
                                 </form>
                                 <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#delLessonModal{{ $lesson->id }}">حذف</button>
                                 <x-confirm-modal
                                     :id="'delLessonModal' . $lesson->id"
                                     title="حذف الدرس"
                                     body="سيتم حذف الدرس نهائياً. الموارد المرتبطة به تصبح مصادر عامة."
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
                             <x-empty-state icon="bi-book" title="لا توجد دروس بعد" message="ابدأ بإنشاء أول درس لطلابك." :action="'<a href=\''.route('teacher.lessons.create').'\' class=\'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> درس جديد</a>'" />
                         </td>
                     </tr>
                 @endforelse
             </tbody>
         </table>
     </div>

     @push('scripts')
     <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
     <script>
     document.addEventListener('DOMContentLoaded', function() {
         var el = document.getElementById('lessons-tbody');
         if (el) {
             new Sortable(el, {
                 animation: 150,
                 handle: '.cursor-move',
                 onEnd: function() {
                     var orders = [];
                     document.querySelectorAll('#lessons-tbody tr').forEach(function(tr, index) {
                         var id = tr.dataset.lessonId;
                         var orderCell = tr.querySelector('.order-cell');
                         orderCell.textContent = index + 1;
                     });
                     // Submit the new order via AJAX
                     var formData = new FormData();
                     formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                     document.querySelectorAll('#lessons-tbody tr').forEach(function(tr, index) {
                         formData.append('orders[' + tr.dataset.lessonId + ']', index + 1);
                     });

                     fetch('{{ route("teacher.lessons.reorder") }}', {
                         method: 'POST',
                         headers: { 'X-Requested-With': 'XMLHttpRequest' },
                         body: formData
                     }).then(function(r) {
                         if (r.ok) location.reload();
                     });
                 }
             });
         }
     });
     </script>
     @endpush
</x-dashboard-layout>