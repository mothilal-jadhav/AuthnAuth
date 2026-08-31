@extends('layouts.app')

@section('title', 'Users - AuthnAuth')

@section('content')

<div class="auth-container">

    <div class="auth-card">

        <div class="logo">
            <h1>User Management</h1>
            <p>Users you are authorized to view.</p>
        </div>

        <p>
            Welcome, {{ auth()->user()->name }}
        </p>

        <p>Your permissions:</p>

        <ul>
            @foreach (auth()->user()->role->permissions as $permission)
                <li>{{ $permission->name }}</li>
            @endforeach
        </ul>

        <form method="POST" action="{{ url('/logout') }}">
            @csrf

            <button type="submit" class="auth-button">
                Logout
            </button>
        </form>

    </div>

</div>

@endsection