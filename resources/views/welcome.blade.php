<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AuthnAuth</title>

    <script src="{{ asset('js/theme-init.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>

<body>

    <div class="container">

        <h1>AuthnAuth</h1>

        <p>
            Authentication & Authorization System
        </p>

        <div class="buttons">

            <a href="/login" class="btn login">
                Login
            </a>

            <a href="/register" class="btn register">
                Register
            </a>

        </div>

    </div>

</body>
</html>