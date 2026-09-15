@extends('layouts.app')

@section('title', 'Change Password - AuthnAuth')

@section('content')

<div class="auth-container">

    <div class="auth-card">

        <div class="logo">
            <h1>AuthnAuth</h1>
            <p>You must set a new password before continuing</p>
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

        <form method="POST" action="{{ url('/password/change') }}">

            @csrf

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
                Set Password
            </button>

        </form>

    </div>

</div>

@endsection
