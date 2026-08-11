<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="bg-blue-600 text-white px-5 py-3 flex items-center justify-between">
        <span class="font-bold text-lg">بطاقة الطالب</span>
        <span class="text-xs opacity-90">Bayan</span>
    </div>
    <div class="p-5">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold shrink-0">
                {{ mb_substr($account->name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-bold text-gray-900 truncate">{{ $account->name }}</h3>
                <p class="text-sm text-gray-500">كود الطالب: <span class="font-mono font-medium">{{ $account->student_code }}</span></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
            <div class="bg-gray-50 rounded-lg px-3 py-2">
                <p class="text-gray-400 text-xs">المدرس</p>
                <p class="font-medium text-gray-900 truncate">{{ $enrollment->group?->teacher?->name ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2">
                <p class="text-gray-400 text-xs">الترم الحالي</p>
                <p class="font-medium text-gray-900 truncate">{{ $enrollment->term?->name ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2 col-span-2">
                <p class="text-gray-400 text-xs">المجموعة الحالية</p>
                <p class="font-medium text-gray-900 truncate">{{ $enrollment->group?->title ?? '—' }}</p>
            </div>
        </div>

        <div class="mt-4 flex justify-center bg-white rounded-lg border border-gray-100 p-3">
            {!! $qr !!}
        </div>
        <p class="text-center text-xs text-gray-400 mt-2 font-mono break-all">رمز البطاقة: {{ $enrollment->card_id }}</p>
    </div>
</div>