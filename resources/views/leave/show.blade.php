@extends('layouts.app')

@section('title', 'Leave Request - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        :title="$leaveRequest->leaveType->name.' Leave'"
        back="{{ route('leave.index') }}"
        backLabel="Back to My Leave"
    >
        <x-slot:actions>
            <x-badge :variant="$leaveRequest->badgeVariant()">{{ ucfirst($leaveRequest->status) }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-6 border-b border-line pb-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Dates</p>
                <p class="mt-1 text-sm text-ink">{{ $leaveRequest->start_date->format('M j, Y') }} &ndash; {{ $leaveRequest->end_date->format('M j, Y') }}</p>
            </div>

            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Days</p>
                <p class="mt-1 text-sm text-ink">{{ $leaveRequest->total_days }}{{ $leaveRequest->is_half_day ? ' (half-day)' : '' }}</p>
            </div>
        </div>

        @if ($leaveRequest->reason)
            <div class="border-b border-line pb-4">
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Reason</p>
                <p class="mt-1 text-sm text-ink">{{ $leaveRequest->reason }}</p>
            </div>
        @endif

        @if ($leaveRequest->approver)
            <div class="border-b border-line pb-4">
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Decided By</p>
                <p class="mt-1 text-sm text-ink">{{ $leaveRequest->approver->name }}</p>

                @if ($leaveRequest->decision_note)
                    <p class="mt-1 text-sm text-ink-muted">{{ $leaveRequest->decision_note }}</p>
                @endif
            </div>
        @endif

        @can('cancel', $leaveRequest)
            <div>
                <form method="POST" action="{{ route('leave.cancel', $leaveRequest) }}" data-confirm="Cancel this leave request?">
                    @csrf
                    @method('DELETE')

                    <x-button type="submit" variant="danger">Cancel Request</x-button>
                </form>
            </div>
        @endcan
    </div>

</main>

@endsection
