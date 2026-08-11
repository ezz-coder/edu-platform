<x-dashboard-layout title="طلاب المدرس">
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900">طلاب المدرس</h2>

        @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div> @endif
        @if (session('info')) <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">{{ session('info') }}</div> @endif

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            @if ($enrollments->isEmpty())
                <div class="p-8 text-center text-gray-500">لا يوجد طلاب بعد.</div>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">كود الطالب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">المجموعة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            @if ($currentUser->canPerm('can_manage_students') || $currentUser->canPerm('can_restore_students'))
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">إجراء</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($enrollments as $enrollment)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $enrollment->student->student_code }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $enrollment->group->title }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="px-2 py-0.5 text-xs rounded-full
                                        @if ($enrollment->status === 'enrolled') bg-green-100 text-green-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $enrollment->status === 'enrolled' ? 'مسجل' : 'مزال' }}
                                    </span>
                                </td>
                                @if ($currentUser->canPerm('can_manage_students') || $currentUser->canPerm('can_restore_students'))
                                    <td class="px-6 py-3 text-sm">
                                        @if ($currentUser->canPerm('can_manage_students'))
                                            <form method="POST" action="{{ route('assistant.students.move', $enrollment) }}" class="flex items-center gap-2">
                                                @csrf
                                                <select name="group_id" class="border-gray-300 rounded-md text-sm">
                                                    @foreach ($groups as $g)
                                                        @if ($g->id !== $enrollment->group_id)
                                                            <option value="{{ $g->id }}">{{ $g->title }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="text-xs px-3 py-1 bg-blue-50 text-blue-700 rounded hover:bg-blue-100">نقل</button>
                                            </form>
                                        @endif
                                        @if ($currentUser->canPerm('can_restore_students') && $enrollment->status !== 'enrolled')
                                            <form method="POST" action="{{ route('assistant.students.restore', $enrollment) }}" class="mt-1">
                                                @csrf
                                                <button type="submit" class="text-xs px-3 py-1 bg-green-50 text-green-700 rounded hover:bg-green-100">استرجاع</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-dashboard-layout>