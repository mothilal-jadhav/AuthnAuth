@extends('layouts.app')

@section('title', 'Shift Settings - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Attendance"
        title="Shift Settings"
        subtitle="Configure each department's work hours."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    />

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Department</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Start</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">End</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Grace</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($departments as $department)
                @php $shift = $department->effectiveShift(); @endphp
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">{{ $department->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $shift['start_time'] }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $shift['end_time'] }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $shift['grace_minutes'] }} min</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('attendance.shifts.edit', $department) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                            {{ $department->shift ? 'Edit' : 'Configure' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="🏢" title="No departments yet" class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

</main>

@endsection
