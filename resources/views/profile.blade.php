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
                View your personal account information.
            </p>
        </div>
    </div>


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
                    Full Name
                </span>

                <strong>
                    {{ auth()->user()->name }}
                </strong>

            </div>


            <div class="profile-detail">

                <span class="detail-label">
                    Email Address
                </span>

                <strong>
                    {{ auth()->user()->email }}
                </strong>

            </div>


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

</div>

</div>

@endsection