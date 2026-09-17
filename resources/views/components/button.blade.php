@props([
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $variants = [
        'primary' => 'bg-brand-solid text-white hover:bg-brand-solid-hover focus-visible:outline-brand-solid shadow-sm',
        'secondary' => 'bg-paper text-ink border border-line hover:bg-paper-alt focus-visible:outline-brand-600',
        'danger' => 'bg-danger text-white hover:brightness-110 focus-visible:outline-danger shadow-sm',
        'ghost' => 'bg-transparent text-ink-muted hover:bg-paper-alt hover:text-ink focus-visible:outline-brand-600',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm gap-1.5',
        'md' => 'px-4 py-2 text-sm gap-2',
        'lg' => 'px-5 py-2.5 text-base gap-2',
    ];

    $classes = 'inline-flex items-center justify-center rounded-md font-medium transition duration-150 ease-out '
        . 'active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none '
        . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($attributes->has('href'))
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
