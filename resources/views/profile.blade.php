@extends('layouts.app')

@section('title', 'My Profile - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


<div class="profile-page">

    <a href="{{ route('dashboard') }}" class="back-dashboard">
        ← Back to Dashboard
    </a>

    <div class="profile-header">
        <div>
            <p class="eyebrow">Account</p>
            <h1>My Profile</h1>
            <p class="profile-subtitle">
                View and update your personal account information.
            </p>
        </div>
    </div>


    {{-- Success Message --}}

    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif


    <section class="profile-card">

        <div class="profile-card-header">

            <div class="profile-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div>
                <h2>{{ auth()->user()->name }}</h2>

                <p>
                    {{ auth()->user()->email }}
                </p>
            </div>

        </div>


        <div class="profile-divider"></div>


        <div class="profile-details">

            <div class="profile-detail">

                <span class="detail-label">
                    Role
                </span>

                <span class="role-badge role-{{ auth()->user()->role->name }}">
                    {{ strtoupper(auth()->user()->role->name) }}
                </span>

            </div>


            <div class="profile-detail">

                <span class="detail-label">
                    Account Status
                </span>

                <span class="status-badge">
                    Active
                </span>

            </div>

        </div>

    </section>


    {{-- Profile Information --}}

    <div class="form-card">

        <div class="form-card-header">
            <h2>Profile Information</h2>

            <p>
                Update your name and email address.
            </p>
        </div>

        @if ($errors->updateProfile->any())

            <div class="error-container">

                <ul>
                    @foreach ($errors->updateProfile->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>

        @endif

        <form method="POST" action="{{ route('profile.update') }}">

            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Full Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', auth()->user()->name) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', auth()->user()->email) }}"
                    required
                >
            </div>

            <button type="submit" class="auth-button">
                Save Changes
            </button>

        </form>

    </div>


    {{-- Change Password --}}

    <div class="form-card">

        <div class="form-card-header">
            <h2>Change Password</h2>

            <p>
                Update your password. You'll need to enter your current one.
            </p>
        </div>

        @if ($errors->updatePassword->any())

            <div class="error-container">

                <ul>
                    @foreach ($errors->updatePassword->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>

        @endif

        <form method="POST" action="{{ route('profile.password.update') }}">

            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password">Current Password</label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
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
                <label for="password_confirmation">Confirm New Password</label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                >
            </div>

            <button type="submit" class="auth-button">
                Update Password
            </button>

        </form>

    </div>

</div>

</div>

@endsection