<x-dashboard-layout title="طلب نقل مجموعة">
    <div class="space-y-6">
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm p-6 max-w-lg">
            <p class="text-gray-600 mb-4">
                مجموعتك الحالية: <span class="font-semibold text-gray-900">{{ $enrollment->group->title }}</span>
            </p>

            @if ($otherGroups->isEmpty())
                <p class="text-gray-500">لا توجد مجموعات أخرى متاحة للنقل إليها.</p>
            @else
                <form method="POST" action="{{ route('student.transfer.store', $enrollment->group) }}">
                    @csrf

                    <div class="mt-4">
                        <x-input-label for="requested_group_id" :value="__('المجموعة المطلوبة')" />
                        <select id="requested_group_id" name="requested_group_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">-- اختر مجموعة --</option>
                            @foreach ($otherGroups as $g)
                                <option value="{{ $g->id }}" {{ $g->isFull() ? 'disabled' : '' }}>
                                    {{ $g->title }} ({{ $g->grade_level }}) — {{ $g->currentStudents() }}/{{ $g->max_students }}
                                    {{ $g->isFull() ? 'ممتلئة' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('requested_group_id')" class="mt-2" />
                    </div>

                    <div class="mt-6">
                        <x-primary-button>
                            {{ __('إرسال طلب النقل') }}
                        </x-primary-button>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">لن يتم نقلك فوراً، بانتظار موافقة المدرس.</p>
                </form>
            @endif
        </div>
    </div>
</x-dashboard-layout>