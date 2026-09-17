@extends('layouts.app')

@section('title', 'Edit Shift - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Attendance"
        :title="$department->name.' Shift'"
        back="{{ route('attendance.shifts.index') }}"
        backLabel="Back to Shift Settings"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Shift Details</h2>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('attendance.shifts.update', $department) }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-field name="start_time" label="Start Time" type="time" value="{{ old('start_time', $shift['start_time']) }}" required />
                <x-field name="end_time" label="End Time" type="time" value="{{ old('end_time', $shift['end_time']) }}" required />
            </div>

            <x-field name="grace_minutes" label="Grace Period (minutes)" type="number" value="{{ old('grace_minutes', $shift['grace_minutes']) }}" required />

            <x-button size="lg" class="w-full">Save Changes</x-button>
        </form>
    </x-card>

</main>

@endsection
