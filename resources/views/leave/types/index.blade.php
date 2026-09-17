@extends('layouts.app')

@section('title', 'Leave Types - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        title="Leave Types"
        subtitle="Configure the leave types available to your organization."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    >
        <x-slot:actions>
            <x-button :href="route('leave.types.create')" size="sm">+ Add Leave Type</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Name</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Days/Year</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Paid</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($leaveTypes as $leaveType)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">{{ $leaveType->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $leaveType->is_unlimited ? 'Unlimited' : $leaveType->default_days_per_year }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $leaveType->paid ? 'Yes' : 'No' }}</td>
                    <td class="px-4 py-3">
                        <x-badge :variant="$leaveType->is_active ? 'success' : 'neutral'">{{ $leaveType->is_active ? 'Active' : 'Archived' }}</x-badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-4">
                            <a href="{{ route('leave.types.edit', $leaveType) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Edit</a>

                            @if ($leaveType->is_active)
                                <form method="POST" action="{{ route('leave.types.destroy', $leaveType) }}" data-confirm="Archive this leave type?">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="text-sm font-medium text-danger hover:opacity-80">Archive</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="🌴" title="No leave types configured" description="Add a leave type to get started." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $leaveTypes->links() }}
    </div>

</main>

@endsection
