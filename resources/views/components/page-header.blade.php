@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'back' => null,
    'backLabel' => 'Back',
])

<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $eyebrow }}</p>
        @endif

        <h1 class="font-display text-2xl font-bold text-ink">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($back || isset($actions))
        <div class="flex flex-col items-end gap-3">
            @if ($back)
                <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-muted transition hover:text-ink">
                    <span aria-hidden="true">&larr;</span> {{ $backLabel }}
                </a>
            @endif

            @isset($actions)
                <div class="flex flex-wrap items-center justify-end gap-3">{{ $actions }}</div>
            @endisset
        </div>
    @endif
</div>
