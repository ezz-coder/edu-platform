<x-dashboard-layout title="إنشاء مجموعة جديدة">
    <div class="space-y-6">
        <!-- Back Button -->
        <a href="{{ route('teacher.groups.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            العودة إلى مجموعاتي
        </a>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">إنشاء مجموعة جديدة</h1>

            <form method="POST" action="{{ route('teacher.groups.store') }}">
                @csrf

                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">اسم المجموعة *</label>
                        <input type="text" id="title" name="title" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="{{ old('title') }}">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">الوصف (اختياري)</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Day -->
                    <div>
                        <label for="day" class="block text-sm font-medium text-gray-700 mb-2">اليوم *</label>
                        <select id="day" name="day" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">اختر اليوم</option>
                            <option value="السبت" {{ old('day') === 'السبت' ? 'selected' : '' }}>السبت</option>
                            <option value="الأحد" {{ old('day') === 'الأحد' ? 'selected' : '' }}>الأحد</option>
                            <option value="الاثنين" {{ old('day') === 'الاثنين' ? 'selected' : '' }}>الاثنين</option>
                            <option value="الثلاثاء" {{ old('day') === 'الثلاثاء' ? 'selected' : '' }}>الثلاثاء</option>
                            <option value="الأربعاء" {{ old('day') === 'الأربعاء' ? 'selected' : '' }}>الأربعاء</option>
                            <option value="الخميس" {{ old('day') === 'الخميس' ? 'selected' : '' }}>الخميس</option>
                            <option value="الجمعة" {{ old('day') === 'الجمعة' ? 'selected' : '' }}>الجمعة</option>
                        </select>
                        @error('day')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Time -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">وقت البداية *</label>
                            <input type="time" id="start_time" name="start_time" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="{{ old('start_time') }}">
                            @error('start_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">وقت النهاية *</label>
                            <input type="time" id="end_time" name="end_time" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="{{ old('end_time') }}">
                            @error('end_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Gender Type -->
                    <div>
                        <label for="gender_type" class="block text-sm font-medium text-gray-700 mb-2">نوع المجموعة *</label>
                        <select id="gender_type" name="gender_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">اختر النوع</option>
                            <option value="boys" {{ old('gender_type') === 'boys' ? 'selected' : '' }}>بنين</option>
                            <option value="girls" {{ old('gender_type') === 'girls' ? 'selected' : '' }}>بنات</option>
                            <option value="mixed" {{ old('gender_type') === 'mixed' ? 'selected' : '' }}>مشترك</option>
                        </select>
                        @error('gender_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Grade Level -->
                    <div>
                        <label for="grade_level" class="block text-sm font-medium text-gray-700 mb-2">المرحلة الدراسية *</label>
                        <input type="text" id="grade_level" name="grade_level" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="مثل: تالتة ثانوي"
                            value="{{ old('grade_level') }}">
                        @error('grade_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            إنشاء المجموعة
                        </button>
                        <a href="{{ route('teacher.groups.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
