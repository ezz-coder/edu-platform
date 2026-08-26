<x-dashboard-layout title="استيراد الطلاب">
    <div class="space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-lg font-bold text-gray-900">استيراد الطلاب</h1>
                <p class="text-sm text-gray-500">استيراد جماعي للطلاب من ملف CSV أو Excel إلى إحدى مجموعاتك</p>
            </div>
            <a href="{{ route('teacher.students.index') }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">العودة</a>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <!-- Import result -->
        @if ($results)
            <div class="bg-white rounded-lg shadow-sm p-4 space-y-2">
                <h2 class="font-semibold text-sm">نتيجة الاستيراد</h2>
                <p class="text-sm"><span class="text-green-600 font-bold">{{ $results['success'] }}</span> صف ناجح —
                    <span class="text-red-600 font-bold">{{ $results['rejected'] }}</span> صف مرفوض</p>
                @if (!empty($results['errors']))
                    <ul class="list-disc pr-5 text-xs text-red-700 space-y-1 max-h-64 overflow-y-auto">
                        @foreach ($results['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <!-- Upload form -->
        <form method="POST" action="{{ route('teacher.students.import.store') }}"
              enctype="multipart/form-data"
              class="bg-white rounded-lg shadow-sm p-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">المجموعة المستهدفة (يجب أن تكون نشطة وتتبعك)</label>
                <select name="group_id" required class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full md:w-80">
                    <option value="">اختر مجموعة...</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->title }} — المقاعد المتبقية: {{ $g->remainingSeats() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">الملف (CSV أو XLSX — بحد أقصى 5MB)</label>
                <input type="file" name="file" accept=".csv,.xlsx" required
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full md:w-96">
            </div>
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">استيراد</button>
        </form>

        <!-- Format help -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-900 space-y-1">
            <p class="font-semibold">صيغة الملف المطلوبة:</p>
            <p>الصف الأول: عناوين الأعمدة — <code>name, mobile, parent_mobile, password</code> (تُقبل العناوين العربية أيضاً).</p>
            <p>كل صف: اسم الطالب، رقم الجوال، جوال ولي الأمر، كلمة المرور (8 أحرف على الأقل).</p>
            <p>الصفوف غير الصحيحة سيتم رفضها مع بيان السبب، ولن تؤثر على الصفوف الصحيحة.</p>
        </div>
    </div>
</x-dashboard-layout>