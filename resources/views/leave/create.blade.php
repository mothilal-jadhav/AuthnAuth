@extends('layouts.app')

@section('title', 'Apply for Leave - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        title="Apply for Leave"
        back="{{ route('leave.index') }}"
        backLabel="Back to My Leave"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Leave Details</h2>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('leave.store') }}" class="mt-6 flex flex-col gap-5">
            @csrf

            <x-field name="leave_type_id" label="Leave Type" as="select" required>
                <option value="">Select a leave type</option>
                @foreach ($leaveTypes as $leaveType)
                    <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                @endforeach
            </x-field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-field name="start_date" label="Start Date" type="date" value="{{ old('start_date') }}" required />
                <x-field name="end_date" label="End Date" type="date" value="{{ old('end_date') }}" required />
            </div>

            <div class="flex items-center gap-2">
                <input id="is_half_day" name="is_half_day" type="checkbox" value="1" @checked(old('is_half_day')) class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500/40">
                <label for="is_half_day" class="text-sm font-medium text-ink">Half-day (start and end date must match)</label>
            </div>

            <x-field name="reason" label="Reason (optional)" as="textarea" value="{{ old('reason') }}" />

            <x-button size="lg" class="w-full">Submit Request</x-button>
        </form>
    </x-card>

</main>

@endsection
