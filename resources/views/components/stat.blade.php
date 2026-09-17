@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-paper p-4 shadow-sm']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">{{ $label }}</p>
    <p class="mt-1 font-display text-2xl font-bold text-ink">{{ $value }}</p>

    @if ($hint)
        <p class="mt-0.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
