@extends('layouts.app')

@section('title', 'Dashboard - AuthnAuth')

@section('content')

<div class="dashboard-page">

    <nav class="navbar">

        <div class="brand">
            <span class="brand-name">AuthnAuth</span>
            <span class="brand-subtitle">Authentication & Authorization</span>
        </div>

        <div class="nav-user">

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


    <main class="dashboard-container">

        <section class="welcome-section">

            <div>
                <p class="eyebrow">Dashboard</p>

                <h1>
                    Welcome, {{ auth()->user()->name }}
                </h1>

                <p class="welcome-text">
                    Manage your account and access available resources.
                </p>
            </div>

            <div class="role-badge">
                {{ strtoupper(auth()->user()->role->name) }}
            </div>

        </section>


        <section class="stats-grid">

            <div class="stat-card">
                <span class="stat-label">Account</span>
                <strong>Active</strong>
                <small>Authenticated user</small>
            </div>

            <div class="stat-card">
                <span class="stat-label">Role</span>
                <strong>{{ ucfirst(auth()->user()->role->name) }}</strong>
                <small>Assigned access level</small>
            </div>

            <div class="stat-card">
                <span class="stat-label">Access</span>
                <strong>
                    {{ auth()->user()->role->permissions->count() }}
                </strong>
                <small>Available actions</small>
            </div>

        </section>


        <section class="dashboard-card">

            <div class="card-header">
                <div>
                    <h2>Available Actions</h2>

                    <p>
                        Actions available to your account based on your role.
                    </p>
                </div>
            </div>


            <div class="action-grid">

                @if (auth()->user()->hasPermission('users.view'))

                    <a href="{{ url('/users') }}" class="action-card">

                        <div class="action-icon">
                            U
                        </div>

                        <div>
                            <h3>View Users</h3>

                            <p>
                                View users you are authorized to access.
                            </p>

                            <span class="permission-label">
                                users.view
                            </span>
                        </div>

                    </a>

                @endif


                @if (auth()->user()->hasPermission('users.create'))

                    <a href="{{ url('/users/create') }}" class="action-card">

                        <div class="action-icon">
                            +
                        </div>

                        <div>
                            <h3>Create User</h3>

                            <p>
                                Create a new user account.
                            </p>

                            <span class="permission-label">
                                users.create
                            </span>
                        </div>

                    </a>

                @endif


                @if (auth()->user()->hasPermission('users.update'))

                    <a href="#" class="action-card">

                        <div class="action-icon">
                            E
                        </div>

                        <div>
                            <h3>Update Users</h3>

                            <p>
                                Modify user information.
                            </p>

                            <span class="permission-label">
                                users.update
                            </span>
                        </div>

                    </a>

                @endif


                @if (auth()->user()->hasPermission('users.delete'))

                    <a href="#" class="action-card">

                        <div class="action-icon">
                            D
                        </div>

                        <div>
                            <h3>Delete Users</h3>

                            <p>
                                Remove authorized user accounts.
                            </p>

                            <span class="permission-label">
                                users.delete
                            </span>
                        </div>

                    </a>

                @endif


                @if (auth()->user()->hasPermission('profile.view'))

                    <a href="#" class="action-card">

                        <div class="action-icon">
                            P
                        </div>

                        <div>
                            <h3>My Profile</h3>

                            <p>
                                View your account information.
                            </p>

                            <span class="permission-label">
                                profile.view
                            </span>
                        </div>

                    </a>

                @endif

            </div>

        </section>

    </main>

</div>

@endsection