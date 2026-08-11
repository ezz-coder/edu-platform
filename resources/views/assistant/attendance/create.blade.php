<x-dashboard-layout title="بدء حصة حضور">
    <div class="space-y-6">
        <a href="{{ route('assistant.attendance.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            العودة إلى جلسات الحضور
        </a>

        <div class="bg-white rounded-lg shadow-sm p-6 max-w-lg">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">بدء حصة حضور</h1>
            <p class="text-sm text-gray-500 mb-6">اختر المجموعة ورقم الحصة. التاريخ والوقت يُسجّلان تلقائياً.</p>

            <form method="POST" action="{{ route('assistant.attendance.store') }}">
                @csrf
                <div class="mb-4">
                    <x-input-label for="group_id" :value="__('المجموعة')" />
                    <select id="group_id" name="group_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->title }} ({{ $group->grade_level }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('group_id')" class="mt-2" />
                </div>

                <div class="mb-6">
                    <x-input-label for="lesson_number" :value="__('رقم الحصة')" />
                    <input id="lesson_number" name="lesson_number" type="number" min="1" value="1"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <x-input-error :messages="$errors->get('lesson_number')" class="mt-2" />
                </div>

                <div>
                    <x-primary-button>بدء الحضور</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>