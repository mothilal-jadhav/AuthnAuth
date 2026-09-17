@php
    $flashes = [];

    if (session('success')) {
        $flashes[] = ['type' => 'success', 'message' => session('success')];
    }

    if (session('status')) {
        $flashes[] = ['type' => 'success', 'message' => session('status')];
    }

    if (session('error')) {
        $flashes[] = ['type' => 'error', 'message' => session('error')];
    }
@endphp

<div
    id="toast-region"
    class="pointer-events-none fixed inset-x-4 top-4 z-50 flex flex-col items-stretch gap-2 sm:inset-x-auto sm:right-4 sm:items-end"
    aria-live="polite"
    aria-atomic="true"
></div>

@if (count($flashes))
    <script type="application/json" id="toast-flash-data">{{ json_encode($flashes) }}</script>
@endif
