<x-dashboard-layout title="{{ $resource->title }}">
    <div class="max-w-3xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900">{{ $resource->title }}</h1>
            <a href="{{ route('student.dashboard') }}" class="text-sm text-blue-600 hover:underline">العودة للمصادر</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-3 text-sm text-gray-700">
            <p>الوصف: {{ $resource->description ?? '—' }}</p>
            <p>المدرس: {{ $resource->teacher->name }}</p>
            <p>المجموعة: {{ $group->title }}</p>
            <p>النوع: {{ $resource->type }}</p>
            <p>تاريخ النشر: {{ $resource->publish_date?->format('Y-m-d') ?? '—' }}</p>
            @if ($resource->expires_at)
                <p>تاريخ الانتهاء: {{ $resource->expires_at->format('Y-m-d') }}</p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            @if ($resource->isExpired())
                <div class="text-center text-red-700 bg-red-50 rounded-lg px-4 py-6">هذا المصدر مؤرشف ولا يمكن فتحه.</div>
            @else
                @switch($resource->type)
                    @case('pdf')
                        @if($resource->file_path)
                            <iframe src="{{ asset('storage/' . $resource->file_path) }}" class="w-full h-[600px] border rounded-lg"></iframe>
                            <a href="{{ route('student.resources.download', $resource) }}" class="mt-3 inline-block px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">تحميل</a>
                        @else
                            <div class="text-center text-gray-500 bg-gray-50 border border-dashed border-gray-300 rounded-lg px-4 py-10">الملف غير متوفر للتحميل حالياً.</div>
                        @endif
                        @break
                    @case('image')
                        @if($resource->file_path)
                            <img src="{{ asset('storage/' . $resource->file_path) }}" class="max-w-full rounded-lg mx-auto" alt="{{ $resource->title }}">
                            <a href="{{ route('student.resources.download', $resource) }}" class="mt-3 inline-block px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">تحميل</a>
                        @else
                            <div class="text-center text-gray-500 bg-gray-50 border border-dashed border-gray-300 rounded-lg px-4 py-10">الملف غير متوفر للتحميل حالياً.</div>
                        @endif
                        @break
                    @case('zip')
                    @case('other')
                        @if($resource->file_path)
                            <a href="{{ route('student.resources.download', $resource) }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">تحميل الملف</a>
                        @else
                            <div class="text-center text-gray-500 bg-gray-50 border border-dashed border-gray-300 rounded-lg px-4 py-10">الملف غير متوفر للتحميل حالياً.</div>
                        @endif
                        @break
                    @case('video_link')
                        <a href="{{ $resource->url }}" target="_blank" class="inline-block px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">▶ تشغيل الفيديو</a>
                        @break
                    @case('google_drive')
                        <a href="{{ $resource->url }}" target="_blank" class="inline-block px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">فتح في Google Drive</a>
                        @break
                    @case('external_link')
                        <a href="{{ $resource->url }}" target="_blank" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">فتح الرابط</a>
                        @break
                @endswitch
            @endif
        </div>
    </div>
</x-dashboard-layout>