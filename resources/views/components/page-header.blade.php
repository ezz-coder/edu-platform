@props(['title', 'description' => '', 'action' => null, 'breadcrumb' => null])

<div class="page-header">
    @if ($breadcrumb)
        <nav class="text-xs text-text-muted mb-2" aria-label="breadcrumb">{{ $breadcrumb }}</nav>
    @endif
    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="page-title">{{ $title }}</h1>
            @if ($description)
                <p class="page-description">{{ $description }}</p>
            @endif
        </div>
        @if ($action)
            <div class="flex gap-2">{!! $action !!}</div>
        @endif
    </div>
</div>