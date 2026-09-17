{{--
    Thin wrapper only — callers supply their own <thead>/<tbody> markup. Header cells
    should use `px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide
    text-ink-muted`, data cells `px-4 py-3 text-sm text-ink`, matching rows
    `border-t border-line` (and `hover:bg-paper-alt` for interactive rows).
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-line bg-paper shadow-sm']) }}>
    <table class="min-w-full divide-y divide-line">
        {{ $slot }}
    </table>
</div>
