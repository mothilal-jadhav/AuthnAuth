@extends('layouts.app')

@section('title', $department->name.' - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Organization"
        :title="$department->name"
        subtitle="Head: {{ $department->head->name ?? '—' }}"
        back="{{ route('departments.index') }}"
        backLabel="Back to Departments"
    />

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Email</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Role</th>
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
                        <x-badge :variant="$user->role->badgeVariant()">{{ strtoupper($user->role->name) }}</x-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        <x-empty-state icon="👥" title="No users in this department" description="Assign users to this department from the Users page." class="m-4 border-0" />
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
