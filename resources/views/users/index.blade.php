@extends('layouts.app')

@section('title', 'Users - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="User Management"
        title="Users"
        subtitle="Manage users and their access levels."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    >
        <x-slot:actions>
            @if (auth()->user()->hasPermission('users.restore'))
                <x-button :href="route('users.trashed')" variant="secondary" size="sm">Deleted Users</x-button>
            @endif

            @if (auth()->user()->hasPermission('users.create'))
                <x-button :href="route('users.create')" size="sm">+ Create User</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-stat label="Total Users" value="{{ $roleCounts->sum() }}" />
        <x-stat label="Administrators" value="{{ $roleCounts->get('admin', 0) }}" />
        <x-stat label="Managers" value="{{ $roleCounts->get('manager', 0) }}" />
        <x-stat label="Users" value="{{ $roleCounts->get('user', 0) }}" />
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <div class="mb-6 flex flex-wrap gap-2">
        <a
            href="{{ route('users.index') }}"
            class="rounded-full px-3 py-1.5 text-sm font-medium transition {{ request('role') ? 'text-ink-muted hover:bg-paper-alt' : 'bg-brand-solid text-white' }}"
        >
            All
        </a>

        @foreach (['admin' => 'Admins', 'manager' => 'Managers', 'user' => 'Users'] as $value => $label)
            <a
                href="{{ route('users.index', ['role' => $value]) }}"
                class="rounded-full px-3 py-1.5 text-sm font-medium transition {{ request('role') === $value ? 'bg-brand-solid text-white' : 'text-ink-muted hover:bg-paper-alt' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Email</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Role</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Department</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($users as $user)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-ink">{{ $user->name }}</span>
                        </div>
                    </td>

                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $user->email }}</td>

                    <td class="px-4 py-3">
                        <x-badge variant="brand">{{ strtoupper($user->role->name) }}</x-badge>
                    </td>

                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $user->department->name ?? '—' }}</td>

                    <td class="px-4 py-3">
                        @canany(['update', 'delete'], $user)
                            <div class="flex items-center gap-4">
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                                        Edit
                                    </a>
                                @endcan

                                @can('delete', $user)
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Are you sure you want to delete this user?">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="text-sm font-medium text-danger hover:opacity-80">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @else
                            <span class="text-sm text-ink-muted">View only</span>
                        @endcanany
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="👥" title="No users found" description="Try a different filter, or create the first account." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

</main>

@endsection
