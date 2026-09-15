@extends('layouts.app')

@section('title', 'Edit User - AuthnAuth')

@section('content')

<div class="dashboard-page">

    <nav class="navbar">

        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-name">AuthnAuth</span>
            <span class="brand-subtitle">
                Authentication & Authorization
            </span>
        </a>

        <div class="nav-user">

            <div class="nav-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div class="user-info">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->role->name }}</span>
            </div>

            <form method="POST" action="{{ url('/logout') }}">
                @csrf

                <button type="submit" class="logout-button">
                    Logout
                </button>
            </form>

        </div>

    </nav>


    <main class="user-page-container">

        <div class="page-header">

            <div>
                <p class="eyebrow">User Management</p>

                <h1>Edit User</h1>

                <p>
                    Update account information and role.
                </p>
            </div>

            <a
                href="{{ route('users.index') }}"
                class="secondary-button"
            >
                ← Back to Users
            </a>

        </div>


        <div class="form-card">

            <div class="form-card-header">

                <h2>User Information</h2>

                <p>
                    Update the user's account details.
                </p>

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


            <form
                method="POST"
                action="{{ route('users.update', $user) }}"
            >

                @csrf
                @method('PUT')


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="role_id">
                        Role
                    </label>

                    <select
                        id="role_id"
                        name="role_id"
                        required
                    >

                        @foreach($roles as $role)

                            <option
                                value="{{ $role->id }}"
                                {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}
                            >
                                {{ ucfirst($role->name) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <button
                    type="submit"
                    class="auth-button"
                >
                    Update User
                </button>

            </form>

        </div>

    </main>

</div>

@endsection