<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="csp-nonce" nonce="{{ Vite::cspNonce() }}">

    <script src="{{ asset('js/theme-init.js') }}"></script>

    <title inertia>AuthnAuth</title>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/inertia.jsx'])
    @inertiaHead
</head>

<body>

    @inertia

</body>

</html>
