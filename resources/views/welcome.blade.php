<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AuthnAuth</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .container {
            width: 420px;
            padding: 40px;
            background: white;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 10px;
            font-size: 36px;
        }

        p {
            color: #666;
            margin-bottom: 30px;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
        }

        .login {
            background: #2563eb;
            color: white;
        }

        .register {
            background: #111827;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
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