@extends('layouts.app')

@section('title', 'Deleted Users - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


<div class="users-page">

    <a href="{{ route('users.index') }}" class="back-dashboard">
        ← Back to Users
    </a>

    <div class="users-header">
        <div>
            <p class="eyebrow">User Management</p>
            <h1>Deleted Users</h1>
            <p class="users-subtitle">
                Deleted accounts, kept for restore. Nothing here is
                permanently removed.
            </p>
        </div>
    </div>


    {{-- Success Message --}}

    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif


    <section class="user-group">

        <div class="table-wrapper">

            <table class="users-table">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Deleted</th>
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

                            <td class="email">
                                {{ $user->deleted_at->diffForHumans() }}
                            </td>

                            <td>

                                @can('restore', $user)

                                    <form
                                        method="POST"
                                        action="{{ route('users.restore', $user->id) }}"
                                        data-confirm="Restore this user's account?"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="restore-button"
                                        >
                                            Restore
                                        </button>
                                    </form>

                                @else

                                    <span class="view-only">
                                        View only
                                    </span>

                                @endcan

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="empty-state">
                                No deleted users.
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
