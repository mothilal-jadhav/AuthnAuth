<nav class="flex items-center justify-between border-b border-line bg-paper px-4 py-3 sm:px-6">

    <a href="{{ route('dashboard') }}" class="flex flex-col leading-tight">
        <span class="font-display text-lg font-bold text-brand-600">AuthnAuth</span>
        <span class="hidden text-xs text-ink-muted sm:block">Authentication &amp; Authorization</span>
    </a>

    <div class="flex items-center gap-4">

        <label class="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full bg-line transition has-[:checked]:bg-brand-solid" title="Toggle dark mode">
            <input type="checkbox" data-theme-toggle class="peer sr-only">
            <span class="pointer-events-none absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
        </label>

        <div class="hidden items-center gap-3 sm:flex">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 font-display font-semibold text-brand-700">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div class="leading-tight">
                <p class="text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
                <p class="text-xs text-ink-muted">{{ auth()->user()->role->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <x-button type="submit" variant="ghost" size="sm">Logout</x-button>
        </form>

    </div>

</nav>
