@props(['title' => null])

<div class="flex min-h-screen items-center justify-center bg-paper-alt px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <h1 class="font-display text-2xl font-bold text-brand-600">AuthnAuth</h1>
            @if ($title)
                <p class="mt-1 text-sm text-ink-muted">{{ $title }}</p>
            @endif
        </div>

        <x-card>
            {{ $slot }}
        </x-card>

        @isset($footer)
            <p class="mt-6 text-center text-sm text-ink-muted">{{ $footer }}</p>
        @endisset
    </div>
</div>
