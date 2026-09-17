@extends('layouts.app')

@section('title', 'My Attendance - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Attendance"
        title="My Attendance"
        subtitle="Clock in and out, and track your monthly record."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    >
        <x-slot:actions>
            <x-button :href="route('attendance.regularize.create')" variant="secondary" size="sm">Request Correction</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert type="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="mb-8 rounded-lg border border-line bg-paper p-6 shadow-sm">
        @if (! $today || ! $today->clock_in)
            <p class="text-sm text-ink-muted">You haven't clocked in today.</p>
            <form method="POST" action="{{ route('attendance.clock-in') }}" class="mt-4">
                @csrf
                <x-button size="lg">Clock In</x-button>
            </form>
        @elseif (! $today->clock_out)
            <p class="text-sm text-ink-muted">Clocked in at {{ $today->clock_in->format('g:i A') }}.</p>
            <form method="POST" action="{{ route('attendance.clock-out') }}" class="mt-4">
                @csrf
                <x-button size="lg" variant="secondary">Clock Out</x-button>
            </form>
        @else
            <p class="text-sm text-ink-muted">
                You're done for today &mdash; {{ $today->clock_in->format('g:i A') }} to {{ $today->clock_out->format('g:i A') }}
                ({{ $today->worked_minutes }} minutes worked).
            </p>
            <x-badge :variant="$today->badgeVariant()" class="mt-3">{{ ucfirst(str_replace('_', ' ', $today->status)) }}</x-badge>
        @endif
    </div>

    @php
        $statLabels = ['present' => 'Present', 'late' => 'Late', 'half_day' => 'Half-day', 'absent' => 'Absent', 'on_leave' => 'On Leave'];
        $statColors = ['present' => 'success', 'late' => 'warning', 'half_day' => 'accent', 'absent' => 'danger', 'on_leave' => 'info'];
    @endphp

    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($statLabels as $key => $label)
            <x-stat :label="$label" :value="$counts->get($key, 0)" hint="{{ now()->format('F Y') }}" :color="$statColors[$key]" />
        @endforeach
    </div>

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Clock In</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Clock Out</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $record)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">{{ $record->date->format('M j, Y') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_in?->format('g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_out?->format('g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <x-badge :variant="$record->badgeVariant()">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</x-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <x-empty-state icon="🕐" title="No attendance records yet" description="Clock in to start tracking your attendance." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $records->links() }}
    </div>

</main>

@endsection
