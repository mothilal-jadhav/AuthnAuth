@props([
    'label',
    'value',
    'hint' => null,
    'color' => null,
])

@php
    $topBorders = [
        'brand' => 'border-t-brand-500',
        'accent' => 'border-t-accent-500',
        'success' => 'border-t-success',
        'danger' => 'border-t-danger',
        'warning' => 'border-t-warning',
        'info' => 'border-t-info',
    ];

    // border-x-line/border-b-line (not the border-line shorthand) so they
    // never touch border-top-color — no cascade-order ambiguity with the
    // top-only color utility below.
    $topBorderClass = $topBorders[$color] ?? 'border-t-line';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border-x border-b border-t-4 border-x-line border-b-line bg-paper p-4 shadow-sm ' . $topBorderClass]) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">{{ $label }}</p>
    <p class="mt-1 font-display text-2xl font-bold text-ink">{{ $value }}</p>

    @if ($hint)
        <p class="mt-0.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
