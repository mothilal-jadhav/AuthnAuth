<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="{{ asset('js/theme-init.js') }}"></script>

    <title>@yield('title', 'AuthnAuth')</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <script src="{{ asset('js/theme.js') }}" defer></script>
    <script src="{{ asset('js/confirm.js') }}" defer></script>
</head>

<body>

    @yield('content')

</body>

</html>