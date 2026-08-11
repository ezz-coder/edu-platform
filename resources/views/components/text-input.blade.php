@props(['type' => 'text', 'name' => null, 'value' => null, 'required' => false])

<input 
    type="{{ $type }}" 
    name="{{ $name }}" 
    value="{{ $value }}" 
    {{ $attributes->merge(['class' => 'block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']) }}
    {{ $required ? 'required' : '' }}
/>