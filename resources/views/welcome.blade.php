<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AuthnAuth</title>

    <script src="{{ asset('js/theme-init.js') }}"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-brand-50 to-paper-alt px-4 text-center">

        <h1 class="font-display text-4xl font-bold text-ink sm:text-5xl">AuthnAuth</h1>

        <p class="mt-3 max-w-md text-base text-ink-muted">
            Authentication &amp; authorization for your team, without the noise.
        </p>

        <div class="mt-8">
            <x-button :href="url('/login')" size="lg">Login</x-button>
        </div>

    </div>

</body>
</html>
