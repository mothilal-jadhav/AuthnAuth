@props([
    'icon' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-line bg-paper-alt px-6 py-12 text-center']) }}>
    @if ($icon)
        <span class="text-2xl" aria-hidden="true">{{ $icon }}</span>
    @endif

    <p class="font-display font-semibold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
