@props([
    'label' => '',
    'name',
    'id' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => '',
])

@php
    $fieldId = $id ?? $name;
    $fieldValue = old($name, $value);
    $hasError = $errors->has($name);
@endphp

<div>
    @if($label)
        <label for="{{ $fieldId }}" class="mb-2 block text-sm font-medium text-slate-700">
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $fieldValue }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl border px-4 py-3 text-sm outline-none transition ' .
                       ($hasError
                            ? 'border-red-300 focus:border-red-500 focus:ring-1 focus:ring-red-500'
                            : 'border-slate-300 focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]')
        ]) }}
    >

    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>