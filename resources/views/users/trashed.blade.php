@extends('layouts.app')

@section('title', 'Deleted Users - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="User Management"
        title="Deleted Users"
        subtitle="Deleted accounts, kept for restore. Nothing here is permanently removed."
        back="{{ route('users.index') }}"
        backLabel="Back to Users"
    />

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Email</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Role</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Deleted</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($users as $user)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-paper-alt font-display text-sm font-semibold text-ink-muted">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-ink">{{ $user->name }}</span>
                        </div>
                    </td>

                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $user->email }}</td>

                    <td class="px-4 py-3">
                        <x-badge variant="brand">{{ strtoupper($user->role->name) }}</x-badge>
                    </td>

                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $user->deleted_at->diffForHumans() }}</td>

                    <td class="px-4 py-3">
                        @can('restore', $user)
                            <form method="POST" action="{{ route('users.restore', $user->id) }}" data-confirm="Restore this user's account?">
                                @csrf

                                <button type="submit" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                                    Restore
                                </button>
                            </form>
                        @else
                            <span class="text-sm text-ink-muted">View only</span>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="🗂️" title="No deleted users" description="Anything removed from Users will show up here first." class="m-4 border-0" />
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
