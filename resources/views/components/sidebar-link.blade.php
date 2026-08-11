@props(['active'])

@php
$classes = ($active ?? false)
            ? 'sidebar-link-active'
            : 'sidebar-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>