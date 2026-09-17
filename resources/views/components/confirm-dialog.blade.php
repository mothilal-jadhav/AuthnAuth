{{--
    One global instance, rendered in the layout. `resources/js/app.js` intercepts any
    form submit with a `data-confirm="..."` attribute (same contract as the old
    window.confirm()-based confirm.js) and opens this dialog instead.
--}}
{{--
    `hidden`/`flex` are toggled by app.js (never an inline `style` attribute —
    the app's CSP sends `style-src 'self'`, which silently drops inline styles).
--}}
<div
    id="confirm-dialog"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-ink/40 p-4 backdrop-blur-sm"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="confirm-dialog-title"
>
    <div class="w-full max-w-sm rounded-lg border border-line bg-paper p-6 shadow-lg">
        <h2 id="confirm-dialog-title" class="font-display text-base font-semibold text-ink">Are you sure?</h2>
        <p id="confirm-dialog-message" class="mt-2 text-sm text-ink-muted"></p>

        <div class="mt-6 flex justify-end gap-3">
            <x-button type="button" variant="secondary" data-confirm-cancel>Cancel</x-button>
            <x-button type="button" variant="danger" data-confirm-accept>Confirm</x-button>
        </div>
    </div>
</div>
