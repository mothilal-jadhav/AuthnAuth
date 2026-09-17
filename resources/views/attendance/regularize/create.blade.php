@extends('layouts.app')

@section('title', 'Request Attendance Correction - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Attendance"
        title="Request a Correction"
        back="{{ route('attendance.index') }}"
        backLabel="Back to My Attendance"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Correction Details</h2>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('attendance.regularize.store') }}" class="mt-6 flex flex-col gap-5">
            @csrf

            <x-field name="date" label="Date" type="date" value="{{ old('date') }}" required />

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-field name="requested_clock_in" label="Actual Clock In" type="time" value="{{ old('requested_clock_in') }}" required />
                <x-field name="requested_clock_out" label="Actual Clock Out" type="time" value="{{ old('requested_clock_out') }}" required />
            </div>

            <x-field name="reason" label="Reason" as="textarea" value="{{ old('reason') }}" required />

            <x-button size="lg" class="w-full">Submit Request</x-button>
        </form>
    </x-card>

</main>

@endsection
