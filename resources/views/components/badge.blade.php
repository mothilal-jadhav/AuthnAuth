@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-paper-alt text-ink-muted border-line',
        'brand' => 'bg-brand-50 text-brand-700 border-brand-100',
        'accent' => 'bg-accent-50 text-accent-700 border-accent-100',
        'success' => 'bg-success-bg text-success border-success-line',
        'danger' => 'bg-danger-bg text-danger border-danger-line',
        'warning' => 'bg-warning-bg text-warning border-warning-line',
        'info' => 'bg-info-bg text-info border-info-line',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ' . ($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
