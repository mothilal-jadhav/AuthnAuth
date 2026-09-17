@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'as' => 'input',
    'hint' => null,
])

@php
    $errorMessage = $errors->first($name);

    $inputClasses = 'block w-full rounded-md border bg-paper px-3 py-2 text-sm text-ink placeholder:text-ink-muted '
        . 'transition focus:outline-none focus:ring-2 focus:ring-brand-500/40 '
        . ($errorMessage ? 'border-danger focus:border-danger' : 'border-line focus:border-brand-500');
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $name }}" class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif

    @if ($as === 'select')
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => $inputClasses]) }}>
            {{ $slot }}
        </select>
    @elseif ($as === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => $inputClasses]) }}>{{ $value }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" {{ $attributes->merge(['class' => $inputClasses]) }}>
    @endif

    @if ($hint && !$errorMessage)
        <p class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif

    @if ($errorMessage)
        <p class="text-sm text-danger">{{ $errorMessage }}</p>
    @endif
</div>
