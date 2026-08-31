@extends('layouts.app')

@section('title', 'Forgot Password - AuthnAuth')

@section('content')

<div class="auth-container">

    <div class="auth-card">

        <div class="logo">
            <h1>AuthnAuth</h1>
            <p>Reset your password</p>
        </div>

        @if (session('status'))
            <div class="success-container">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-container">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/forgot-password') }}">

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

            <button type="submit" class="auth-button">
                Send Reset Link
            </button>

        </form>

        <div class="auth-footer">
            Remember your password?
            <a href="{{ url('/login') }}">Login</a>
        </div>

    </div>

</div>

@endsection