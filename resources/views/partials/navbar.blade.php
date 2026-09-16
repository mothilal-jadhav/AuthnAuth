<nav class="navbar">

    <a href="{{ route('dashboard') }}" class="brand">
        <span class="brand-name">AuthnAuth</span>
        <span class="brand-subtitle">
            Authentication & Authorization
        </span>
    </a>

    <div class="nav-user">

        <label class="theme-toggle" title="Toggle dark mode">
            <input type="checkbox" data-theme-toggle hidden>
            <span class="theme-toggle-track">
                <span class="theme-toggle-thumb"></span>
            </span>
        </label>

        <div class="nav-avatar">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>

        <div class="user-info">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ auth()->user()->role->name }}</span>
        </div>

        <form method="POST" action="{{ url('/logout') }}">
            @csrf

            <button type="submit" class="logout-button">
                Logout
            </button>
        </form>

    </div>

</nav>
