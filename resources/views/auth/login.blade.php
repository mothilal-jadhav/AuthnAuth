@extends('layouts.app')

@section('title', 'Login - AuthnAuth')

@section('content')

<div class="auth-container">

    <div class="auth-card">

        <div class="logo">
            <h1>AuthnAuth</h1>
            <p>Sign in to your account</p>
        </div>

        @if ($errors->any())
            <div class="error-container">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}">

            @csrf

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >

            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
                <br>
                

                <div class="auth-footer" style="margin-top: 10px; margin-bottom: 20px;">
                    <a href="{{ url('/forgot-password') }}">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <button type="submit" class="auth-button">
                Login
            </button>

        </form>

        <div class="auth-footer">
            Don't have an account?
            <a href="{{ url('/register') }}">Create an account</a>
        </div>

    </div>

</div>

@endsection