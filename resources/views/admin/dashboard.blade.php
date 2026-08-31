@extends('layouts.app')

@section('title', 'Admin Dashboard - AuthnAuth')

@section('content')

<div class="auth-container">

    <div class="auth-card">

        <div class="logo">
            <h1>Admin Dashboard</h1>

            <p>
                Welcome, {{ auth()->user()->name }}
            </p>
        </div>

        <p>
            Your role:
            <strong>{{ auth()->user()->role }}</strong>
        </p>

        <form method="POST" action="{{ url('/logout') }}">
            @csrf

            <button type="submit" class="auth-button">
                Logout
            </button>
        </form>

    </div>

</div>

@endsection