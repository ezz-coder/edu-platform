<x-dashboard-layout title="إنهاء الترم">
    <div class="space-y-6">
        <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            العودة إلى لوحة التحكم
        </a>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
            تحذير: سيتم إنشاء أرشيف لهذا الترم ولن يتم حذف أي بيانات. هذه العملية يمكن التراجع عنها لفترة قصيرة فقط.
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">إنهاء الترم: {{ $term->title }}</h1>
            <p class="text-sm text-gray-500 mb-6">السنة: {{ $term->year }} — الترم رقم {{ $term->term_number }}</p>

            <form method="POST" action="{{ route('teacher.term.confirm') }}">
                @csrf

                <!-- Mode selection -->
                <div class="space-y-3 mb-6">
                    <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="mode" value="keep_groups" class="mt-1" checked>
                        <div>
                            <p class="font-medium text-gray-900">إنهاء الترم مع الإبقاء على نفس المجموعات</p>
                            <p class="text-sm text-gray-500">يُنشأ أرشيف، وتبقى المجموعات والمواعيد والطلاب كما هم. مفيد بعد إجازة قصيرة.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="mode" value="new_groups" class="mt-1">
                        <div>
                            <p class="font-medium text-gray-900">إنهاء الترم وإنشاء ترم جديد بمجموعات جديدة</p>
                            <p class="text-sm text-gray-500">يُؤرشف كل شيء، ويبدأ المدرس من مجموعات فارغة.</p>
                        </div>
                    </label>
                </div>

                <!-- Group selection (only relevant for keep_groups) -->
                <div>
                    <p class="font-medium text-gray-900 mb-2">اختر المجموعات التي تريد إبقاءها في الترم الجديد</p>
                    <p class="text-xs text-gray-400 mb-3">اختيار المجموعات غير إلزامي. اتركها فارغة لعدم نقل أي مجموعة.</p>
                    <div class="space-y-2">
                        @forelse ($groups as $group)
                            <label class="flex items-center gap-3 p-3 border border-gray-100 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="group_ids[]" value="{{ $group->id }}" class="group-check">
                                <span class="font-medium text-gray-900">{{ $group->title }}</span>
                                <span class="text-sm text-gray-500">({{ $group->current_students_count }} طالب)</span>
                            </label>
                        @empty
                            <p class="text-gray-500 text-sm">لا توجد مجموعات.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6">
                    <x-primary-button>متابعة</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>