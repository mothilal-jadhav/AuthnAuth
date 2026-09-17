@extends('layouts.app')

@section('title', 'Team Attendance - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Attendance"
        title="Team Attendance"
        subtitle="Daily attendance roster across the organization."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    />

    <form method="GET" action="{{ route('attendance.team.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
        <x-field name="date" label="Date" type="date" value="{{ request('date', $dateString) }}" data-auto-submit />

        <x-field name="department_id" label="Department" as="select" data-auto-submit>
            <option value="">All departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </x-field>
    </form>

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Department</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Clock In</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Clock Out</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $record)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">{{ $record->user->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->user->department->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_in?->format('g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_out?->format('g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <x-badge :variant="$record->badgeVariant()">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</x-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="🕐" title="No records for this day" description="No one has clocked in yet for the selected date." class="m-4 border-0" />
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
