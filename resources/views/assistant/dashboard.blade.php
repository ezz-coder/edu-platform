<x-dashboard-layout title="لوحة المساعد">
    <div class="space-y-6">
        <!-- Teacher info header -->
        <div class="bg-white rounded-lg shadow-sm p-6 flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-emerald-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                    {{ mb_substr($teacher->name, 0, 1) }}
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-900">{{ $assistant->name }}</p>
                    <p class="text-sm text-gray-500">تعمل تحت إشراف المدرس: <span class="font-semibold text-emerald-700">{{ $teacher->name }}</span></p>
                </div>
            </div>
            <form method="POST" action="{{ route('assistant.logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm font-medium">تسجيل الخروج</button>
            </form>
        </div>

        <!-- Allowed actions only -->
        <h2 class="text-lg font-bold text-gray-900">الإجراءات المتاحة</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @if ($assistant->canPerm('can_take_attendance'))
                <a href="{{ route('assistant.attendance.index') }}" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-indigo-400 transition">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-700 flex items-center justify-center text-xl"><i class="bi bi-calendar-check"></i></span>
                        <span class="font-semibold text-gray-900">تسجيل الحضور</span>
                    </div>
                </a>
            @endif

            @if ($assistant->canPerm('can_manage_students'))
                <a href="{{ route('assistant.students.index') }}" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-blue-400 transition">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xl"><i class="bi bi-people"></i></span>
                        <span class="font-semibold text-gray-900">إدارة الطلاب</span>
                    </div>
                </a>
            @endif

            @if ($assistant->canPerm('can_upload_resources'))
                <a href="{{ route('assistant.resources.create') }}" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-green-400 transition">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-green-50 text-green-700 flex items-center justify-center text-xl"><i class="bi bi-files"></i></span>
                        <span class="font-semibold text-gray-900">رفع الموارد</span>
                    </div>
                </a>
            @endif

            @if ($assistant->canPerm('can_manage_reservations'))
                <a href="{{ route('assistant.reservations.create') }}" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-purple-400 transition">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-purple-50 text-purple-700 flex items-center justify-center text-xl"><i class="bi bi-calendar-event"></i></span>
                        <span class="font-semibold text-gray-900">إدارة الحجوزات</span>
                    </div>
                </a>
            @endif

            @if ($assistant->canPerm('can_view_groups'))
                <a href="{{ route('assistant.groups.index') }}" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-yellow-400 transition">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-yellow-50 text-yellow-700 flex items-center justify-center text-xl"><i class="bi bi-collection"></i></span>
                        <span class="font-semibold text-gray-900">مشاهدة المجموعات</span>
                    </div>
                </a>
            @endif

            @if (! $assistant->permissions || collect([
                'can_take_attendance','can_manage_students','can_upload_resources',
                'can_manage_reservations','can_restore_students','can_view_groups'
            ])->every(fn ($p) => ! $assistant->canPerm($p)))
                <div class="col-span-full bg-gray-50 border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                    لا توجد صلاحيات مفعّلة لحسابك حالياً. يرجى التواصل مع المدرس.
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>