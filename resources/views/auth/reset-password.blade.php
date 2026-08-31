@extends('layouts.app')

@section('title', 'Reset Password - AuthnAuth')

@section('content')

<div class="auth-container">

    <div class="auth-card">

        <div class="logo">
            <h1>AuthnAuth</h1>
            <p>Choose a new password</p>
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

        <form method="POST" action="{{ url('/reset-password') }}">

            @csrf

            <input
                type="hidden"
                name="token"
                value="{{ $token }}"
            >

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">New Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password_confirmation">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                >
            </div>

            <button type="submit" class="auth-button">
                Reset Password
            </button>

        </form>

    </div>

</div>

@endsection