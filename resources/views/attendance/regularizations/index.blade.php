@extends('layouts.app')

@section('title', 'Attendance Corrections - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Attendance"
        title="Attendance Corrections"
        subtitle="Review pending attendance correction requests."
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
        @forelse ($pending as $regularization)
            <div class="rounded-lg border border-line bg-paper p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-display font-semibold text-ink">{{ $regularization->user->name }} &mdash; {{ $regularization->date->format('M j, Y') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            Requested {{ $regularization->requested_clock_in->format('g:i A') }} &ndash; {{ $regularization->requested_clock_out->format('g:i A') }}
                        </p>

                        @if ($regularization->reason)
                            <p class="mt-2 text-sm text-ink">{{ $regularization->reason }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('attendance.regularizations.approve', $regularization) }}">
                            @csrf
                            @method('PUT')

                            <x-button type="submit" size="sm">Approve</x-button>
                        </form>

                        <x-button
                            type="button"
                            id="reject-trigger-{{ $regularization->id }}"
                            variant="secondary"
                            size="sm"
                            data-show="reject-form-{{ $regularization->id }}"
                            data-hide="reject-trigger-{{ $regularization->id }}"
                        >
                            Reject
                        </x-button>
                    </div>
                </div>

                <form
                    id="reject-form-{{ $regularization->id }}"
                    method="POST"
                    action="{{ route('attendance.regularizations.reject', $regularization) }}"
                    class="mt-4 hidden border-t border-line pt-4"
                >
                    @csrf
                    @method('PUT')

                    <x-field name="decision_note" label="Reason for rejection" as="textarea" required />

                    <x-button type="submit" variant="danger" size="sm" class="mt-3">Confirm Rejection</x-button>
                </form>
            </div>
        @empty
            <x-empty-state icon="✅" title="No pending correction requests" description="You're all caught up." />
        @endforelse
    </div>

    <div class="mt-4">
        {{ $pending->links() }}
    </div>

</main>

@endsection
