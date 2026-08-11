@props(['messages' => []])

@if (!empty($messages))
    <div class="mt-2 text-sm text-red-600">
        @foreach ($messages as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif