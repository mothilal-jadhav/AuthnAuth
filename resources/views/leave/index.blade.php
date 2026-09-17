@extends('layouts.app')

@section('title', 'My Leave - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        title="My Leave"
        subtitle="Apply for leave and track your balances."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    >
        <x-slot:actions>
            <x-button :href="route('leave.create')" size="sm">+ Apply for Leave</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    @php
        $statColors = ['brand', 'accent', 'success', 'info'];
    @endphp

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($leaveTypes as $index => $leaveType)
            @php
                $balance = $balances->get($leaveType->id);
                $remaining = $leaveType->is_unlimited
                    ? 'Unlimited'
                    : ($balance ? $balance->allocated_days + $balance->carried_over_days - $balance->used_days : $leaveType->default_days_per_year);
            @endphp

            <x-stat
                :label="$leaveType->name"
                :value="$remaining"
                hint="Days remaining ({{ $year }})"
                :color="$statColors[$index % count($statColors)]"
            />
        @endforeach
    </div>

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Dates</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Days</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($requests as $request)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">
                        <a href="{{ route('leave.show', $request) }}" class="hover:text-brand-600 hover:underline">
                            {{ $request->leaveType->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">
                        {{ $request->start_date->format('M j, Y') }} &ndash; {{ $request->end_date->format('M j, Y') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $request->total_days }}</td>
                    <td class="px-4 py-3">
                        <x-badge :variant="$request->badgeVariant()">{{ ucfirst($request->status) }}</x-badge>
                    </td>
                    <td class="px-4 py-3">
                        @if ($request->status === 'pending')
                            <form method="POST" action="{{ route('leave.cancel', $request) }}" data-confirm="Cancel this leave request?">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="text-sm font-medium text-danger hover:opacity-80">
                                    Cancel
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="🌴" title="No leave requests yet" description="Apply for leave to see it listed here." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>

</main>

@endsection
