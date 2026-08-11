<div class="card-hover p-4">
    <div class="flex items-start justify-between mb-2">
        <h3 class="font-semibold text-text-primary">{{ $resource->title }}</h3>
        <span class="text-xl">
            @switch($resource->type)
                @case('pdf') 📄 @break
                @case('image') 🖼️ @break
                @case('zip') 🗜️ @break
                @case('google_drive') 📁 @break
                @case('external_link') 🔗 @break
                @case('video_link') 🎬 @break
                @default 📎
            @endswitch
        </span>
    </div>
    <p class="text-xs text-text-muted mb-1">المدرس: {{ $resource->teacher->name }}</p>
    <p class="text-xs text-text-muted mb-2">تاريخ النشر: {{ $resource->publish_date?->format('Y-m-d') ?? '—' }}</p>
    @if ($resource->isExpired())
        <span class="badge badge-danger">مؤرشف</span>
    @else
        <div class="flex gap-2 mt-2">
            <a href="{{ route('student.resources.show', $resource) }}" class="btn-primary btn-sm">فتح</a>
            @if (in_array($resource->type, ['pdf','image','zip','other']) && $resource->file_path)
                <a href="{{ asset('storage/' . $resource->file_path) }}" download class="btn-secondary btn-sm">تحميل</a>
            @endif
        </div>
    @endif
</div>