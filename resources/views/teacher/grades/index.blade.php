<x-dashboard-layout title="الدرجات">
    <div class="space-y-6">
        <x-page-header
            title="الدرجات"
            description="إدارة درجات الطلاب في امتحاناتك"
        />

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">إجمالي الامتحانات</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalExams }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">إجمالي الدرجات المسجلة</p>
                <p class="text-xl font-bold text-blue-600">{{ $totalGrades }}</p>
            </div>
        </div>

        <!-- Exams list -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-md font-bold text-gray-900">الامتحانات</h2>
            </div>
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2">الامتحان</th>
                        <th class="px-3 py-2">الحالة</th>
                        <th class="px-3 py-2">الدرجات المسجلة</th>
                        <th class="px-3 py-2">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($exams as $exam)
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-900">{{ $exam->title }}</td>
                            <td class="px-3 py-2">
                                @if ($exam->status === 'visible') <span class="text-green-600">منشور</span>
                                @elseif ($exam->status === 'hidden') <span class="text-orange-600">مغلق</span>
                                @else <span class="text-gray-400">مسودة</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $exam->finished_attempts }} / {{ $exam->total_attempts }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('teacher.grades.show', $exam) }}" class="text-blue-600 hover:underline">عرض الدرجات</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">لا توجد امتحانات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">
                {{ $exams->links() }}
            </div>
        </div>
    </div>
</x-dashboard-layout>