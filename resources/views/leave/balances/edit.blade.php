@extends('layouts.app')

@section('title', 'Adjust Leave Balance - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        :title="$leaveBalance->user->name.' — '.$leaveBalance->leaveType->name"
        back="{{ route('leave.balances.index') }}"
        backLabel="Back to Leave Balances"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Balance Details</h2>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('leave.balances.update', $leaveBalance) }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <x-field name="allocated_days" label="Allocated Days" type="number" step="0.5" value="{{ old('allocated_days', $leaveBalance->allocated_days) }}" required />
            <x-field name="used_days" label="Used Days" type="number" step="0.5" value="{{ old('used_days', $leaveBalance->used_days) }}" required />
            <x-field name="carried_over_days" label="Carried Over Days" type="number" step="0.5" value="{{ old('carried_over_days', $leaveBalance->carried_over_days) }}" required />

            <x-button size="lg" class="w-full">Save Changes</x-button>
        </form>
    </x-card>

</main>

@endsection
