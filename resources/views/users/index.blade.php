@extends('layouts.app')

@section('title', 'Users - AuthnAuth')

@section('content')

<div class="dashboard-page">

    <nav class="navbar">

        <div class="brand">
            <span class="brand-name">AuthnAuth</span>
            <span class="brand-subtitle">
                Authentication & Authorization
            </span>
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


    <main class="user-page-container">

        <div class="page-header">

            <div>
                <p class="eyebrow">User Management</p>

                <h1>Users</h1>

                <p>
                    Manage users and their assigned roles.
                </p>
            </div>

            @if(auth()->user()->hasPermission('users.create'))
                <a
                    href="{{ route('users.create') }}"
                    class="auth-button"
                >
                    + Create User
                </a>
            @endif

        </div>


        @if(session('success'))

            <div class="success-message">
                {{ session('success') }}
            </div>

        @endif


        <div class="users-card">

            <div class="table-container">

                <table class="users-table">

                    <thead>

                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($users as $user)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $user->name }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $user->email }}
                                </td>

                                <td>
                                    <span class="role-badge">
                                        {{ ucfirst($user->role?->name ?? 'No Role') }}
                                    </span>
                                </td>

                                <td>
                                    {{ $user->created_at->format('d M Y') }}
                                </td>

                                <td>

                                    <div class="user-actions">

                                        @if(auth()->user()->hasPermission('users.update'))

                                            <a
                                                href="{{ route('users.edit', $user) }}"
                                                class="action-edit"
                                            >
                                                Edit
                                            </a>

                                        @endif


                                        @if(auth()->user()->hasPermission('users.delete'))

                                            <form
                                                method="POST"
                                                action="{{ route('users.destroy', $user) }}"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="action-delete"
                                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-users"
                                >
                                    No users found.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

@endsection