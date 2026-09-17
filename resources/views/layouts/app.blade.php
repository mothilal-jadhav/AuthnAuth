<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="{{ asset('js/theme-init.js') }}"></script>

    <title>@yield('title', 'AuthnAuth')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('js/theme.js') }}" defer></script>
</head>

<body>

    @yield('content')

    <x-toast />
    <x-confirm-dialog />

</body>

</html>