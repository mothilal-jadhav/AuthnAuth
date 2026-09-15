@extends('layouts.app')

@section('title', 'Users - AuthnAuth')

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


<div class="users-page">

    <a href="{{ route('dashboard') }}" class="back-dashboard">
        ← Back to Dashboard
    </a>

    <div class="users-header">
        <div>
            <p class="eyebrow">User Management</p>
            <h1>Users</h1>
            <p class="users-subtitle">
                Manage users and their access levels.
            </p>
        </div>

        @if(auth()->user()->hasPermission('users.create'))
            <a href="{{ route('users.create') }}" class="primary-button">
                <span>+</span>
                Create User
            </a>
        @endif
    </div>


    {{-- Statistics --}}

    <div class="user-stats">

        <div class="stat-card">
            <span class="stat-label">Total Users</span>
            <strong>{{ $roleCounts->sum() }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Administrators</span>
            <strong>{{ $roleCounts->get('admin', 0) }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Managers</span>
            <strong>{{ $roleCounts->get('manager', 0) }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Users</span>
            <strong>{{ $roleCounts->get('user', 0) }}</strong>
        </div>

    </div>


    {{-- Success Message --}}

    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif


    {{-- Role filter --}}

    <div class="role-filter">

        <a
            href="{{ route('users.index') }}"
            class="{{ request('role') ? '' : 'active' }}"
        >
            All
        </a>

        @foreach(['admin' => 'Admins', 'manager' => 'Managers', 'user' => 'Users'] as $value => $label)
            <a
                href="{{ route('users.index', ['role' => $value]) }}"
                class="{{ request('role') === $value ? 'active' : '' }}"
            >
                {{ $label }}
            </a>
        @endforeach

    </div>


    <section class="user-group">

        <div class="table-wrapper">

            <table class="users-table">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="actions-column">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($users as $user)

                        <tr>

                            <td>
                                <div class="user-cell">

                                    <div class="avatar">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>

                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                    </div>

                                </div>
                            </td>

                            <td class="email">
                                {{ $user->email }}
                            </td>

                            <td>
                                <span class="role-badge role-{{ $user->role->name }}">
                                    {{ strtoupper($user->role->name) }}
                                </span>
                            </td>

                            <td>

                                @canany(['update', 'delete'], $user)

                                    <div class="user-actions">

                                        @can('update', $user)
                                            <a
                                                href="{{ route('users.edit', $user) }}"
                                                class="edit-button"
                                            >
                                                Edit
                                            </a>
                                        @endcan

                                        @can('delete', $user)
                                            <form
                                                method="POST"
                                                action="{{ route('users.destroy', $user) }}"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="delete-button"
                                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan

                                    </div>

                                @else

                                    <span class="view-only">
                                        View only
                                    </span>

                                @endcanany

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="empty-state">
                                No users found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $users->links() }}
        </div>

    </section>

</div>

</div>

@endsection
