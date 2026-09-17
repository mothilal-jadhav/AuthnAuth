@extends('layouts.app')

@section('title', 'Leave Approvals - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        title="Leave Approvals"
        subtitle="Review pending leave requests."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    />

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

    <div class="flex flex-col gap-4">
        @forelse ($pending as $request)
            <div class="rounded-lg border border-line bg-paper p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-display font-semibold text-ink">{{ $request->user->name }} &mdash; {{ $request->leaveType->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ $request->start_date->format('M j, Y') }} &ndash; {{ $request->end_date->format('M j, Y') }}
                            ({{ $request->total_days }} day(s){{ $request->is_half_day ? ', half-day' : '' }})
                        </p>

                        @if ($request->reason)
                            <p class="mt-2 text-sm text-ink">{{ $request->reason }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('leave.approvals.approve', $request) }}">
                            @csrf
                            @method('PUT')

                            <x-button type="submit" size="sm">Approve</x-button>
                        </form>

                        <x-button
                            type="button"
                            id="reject-trigger-{{ $request->id }}"
                            variant="secondary"
                            size="sm"
                            data-show="reject-form-{{ $request->id }}"
                            data-hide="reject-trigger-{{ $request->id }}"
                        >
                            Reject
                        </x-button>
                    </div>
                </div>

                <form
                    id="reject-form-{{ $request->id }}"
                    method="POST"
                    action="{{ route('leave.approvals.reject', $request) }}"
                    class="mt-4 hidden border-t border-line pt-4"
                >
                    @csrf
                    @method('PUT')

                    <x-field name="decision_note" label="Reason for rejection" as="textarea" required />

                    <x-button type="submit" variant="danger" size="sm" class="mt-3">Confirm Rejection</x-button>
                </form>
            </div>
        @empty
            <x-empty-state icon="✅" title="No pending requests" description="You're all caught up." />
        @endforelse
    </div>

    <div class="mt-4">
        {{ $pending->links() }}
    </div>

</main>

@endsection
