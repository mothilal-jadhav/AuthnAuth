@props([
    'type' => 'info',
    'dismissible' => false,
])

@php
    $styles = [
        'success' => ['bg' => 'bg-success-bg', 'text' => 'text-success', 'border' => 'border-success-line', 'icon' => '✓'],
        'error' => ['bg' => 'bg-danger-bg', 'text' => 'text-danger', 'border' => 'border-danger-line', 'icon' => '!'],
        'warning' => ['bg' => 'bg-warning-bg', 'text' => 'text-warning', 'border' => 'border-warning-line', 'icon' => '!'],
        'info' => ['bg' => 'bg-info-bg', 'text' => 'text-info', 'border' => 'border-info-line', 'icon' => 'i'],
    ];

    $style = $styles[$type] ?? $styles['info'];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-md border {$style['border']} {$style['bg']} {$style['text']} px-4 py-3 text-sm"]) }} role="alert">
    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $style['border'] }} text-xs font-bold">
        {{ $style['icon'] }}
    </span>

    <div class="flex-1">{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" data-alert-dismiss class="text-current/60 transition hover:text-current" aria-label="Dismiss">
            &times;
        </button>
    @endif
</div>
