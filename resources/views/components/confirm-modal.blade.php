@props([
    'id' => 'confirmModal',
    'title' => 'تأكيد',
    'body' => '',
    'confirmText' => 'تأكيد',
    'cancelText' => 'إلغاء',
    'formId' => null,
    'danger' => true,
])

{{--
    Uses the app's .modal / .modal.show CSS (not Bootstrap).
    Open via data-bs-toggle="modal" data-bs-target="#id" or bootstrap.Modal shim in app.js.
--}}
<div class="modal" id="{{ $id }}" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header flex items-center justify-between">
                <h5 class="modal-title text-lg font-bold text-text-primary">{{ $title }}</h5>
                <button type="button" class="btn-close text-text-muted hover:text-text-primary text-xl leading-none" data-bs-dismiss="modal" aria-label="إغلاق">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-text-secondary">{{ $body }}</p>
                {{ $slot ?? '' }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">{{ $cancelText }}</button>
                @if ($formId)
                    <button type="submit" form="{{ $formId }}" class="{{ $danger ? 'btn-danger' : 'btn-primary' }}" data-bs-dismiss="modal">{{ $confirmText }}</button>
                @else
                    <button type="button" class="{{ $danger ? 'btn-danger' : 'btn-primary' }}" data-bs-dismiss="modal">{{ $confirmText }}</button>
                @endif
            </div>
        </div>
    </div>
</div>
