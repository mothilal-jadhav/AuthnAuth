@extends('layouts.app')

@section('title', 'Leave Balances - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        title="Leave Balances"
        subtitle="{{ $year }} balances for every user."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    />

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Leave Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Allocated</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Used</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Carried Over</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($balances as $balance)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">{{ $balance->user->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->leaveType->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->allocated_days }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->used_days }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->carried_over_days }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('leave.balances.edit', $balance) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Adjust</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-empty-state icon="🌴" title="No balances yet" description="Balances appear once leave types and users exist." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

</main>

@endsection
