@props(['icon' => 'bi-inbox', 'title' => '', 'message' => '', 'action' => null])

<div class="empty-state">
    <div class="empty-state-icon">
        <i class="bi {{ $icon }}"></i>
    </div>
    <h3 class="empty-state-title">{{ $title }}</h3>
    @if ($message)
        <p class="empty-state-message">{{ $message }}</p>
    @endif
    @if ($action)
        <div class="mt-6">{!! $action !!}</div>
    @endif
</div>