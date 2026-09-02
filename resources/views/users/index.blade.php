@extends('layouts.app')

@section('title', 'Users - AuthnAuth')

@section('content')

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
            <strong>
                {{ $admins->count() + $managers->count() + $normalUsers->count() }}
            </strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Administrators</span>
            <strong>{{ $admins->count() }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Managers</span>
            <strong>{{ $managers->count() }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Users</span>
            <strong>{{ $normalUsers->count() }}</strong>
        </div>

    </div>


    {{-- Success Message --}}

    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif


    {{-- Administrators --}}

    <section class="user-group">

        <div class="group-header">
            <div>
                <h2>Admins</h2>
            </div>

            <span class="group-count">
                {{ $admins->count() }}
            </span>
        </div>

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

                    @forelse($admins as $user)

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
                                <span class="role-badge role-admin">
                                    ADMIN
                                </span>
                            </td>

                            <td>

                                @if(auth()->user()->role->name === 'admin')

                                    <div class="user-actions">

                                        <a
                                            href="{{ route('users.edit', $user) }}"
                                            class="edit-button"
                                        >
                                            Edit
                                        </a>

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

                                    </div>

                                @else

                                    <span class="view-only">
                                        View only
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="empty-state">
                                No administrators found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    {{-- Managers --}}

    <section class="user-group">

        <div class="group-header">
            <div>
                <h2>Managers</h2>
            </div>

            <span class="group-count">
                {{ $managers->count() }}
            </span>
        </div>

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

                    @forelse($managers as $user)

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
                                <span class="role-badge role-manager">
                                    MANAGER
                                </span>
                            </td>

                            <td>

                                @if(auth()->user()->role->name === 'admin')

                                    <div class="user-actions">

                                        <a
                                            href="{{ route('users.edit', $user) }}"
                                            class="edit-button"
                                        >
                                            Edit
                                        </a>

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

                                    </div>

                                @else

                                    <span class="view-only">
                                        View only
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="empty-state">
                                No managers found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    {{-- Normal Users --}}

    <section class="user-group">

        <div class="group-header">
            <div>
                <h2>Users</h2>
            </div>

            <span class="group-count">
                {{ $normalUsers->count() }}
            </span>
        </div>

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

                    @forelse($normalUsers as $user)

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
                                <span class="role-badge role-user">
                                    USER
                                </span>
                            </td>

                            <td>

                                @if(
                                    auth()->user()->role->name === 'admin' ||
                                    auth()->user()->role->name === 'manager'
                                )

                                    <div class="user-actions">

                                        <a
                                            href="{{ route('users.edit', $user) }}"
                                            class="edit-button"
                                        >
                                            Edit
                                        </a>

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

                                    </div>

                                @else

                                    <span class="view-only">
                                        View only
                                    </span>

                                @endif

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

    </section>

</div>

@endsection