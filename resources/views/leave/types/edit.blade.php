@extends('layouts.app')

@section('title', 'Edit Leave Type - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Leave"
        title="Edit Leave Type"
        back="{{ route('leave.types.index') }}"
        backLabel="Back to Leave Types"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Leave Type Details</h2>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('leave.types.update', $leaveType) }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <x-field name="name" label="Name" value="{{ old('name', $leaveType->name) }}" required />
            <x-field name="default_days_per_year" label="Default Days per Year" type="number" value="{{ old('default_days_per_year', $leaveType->default_days_per_year) }}" required />

            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <input id="paid" name="paid" type="checkbox" value="1" @checked(old('paid', $leaveType->paid)) class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500/40">
                    <label for="paid" class="text-sm font-medium text-ink">Paid leave</label>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_unlimited" name="is_unlimited" type="checkbox" value="1" @checked(old('is_unlimited', $leaveType->is_unlimited)) class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500/40">
                    <label for="is_unlimited" class="text-sm font-medium text-ink">Unlimited (skip balance checks)</label>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $leaveType->is_active)) class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500/40">
                    <label for="is_active" class="text-sm font-medium text-ink">Active</label>
                </div>
            </div>

            <x-button size="lg" class="w-full">Save Changes</x-button>
        </form>
    </x-card>

</main>

@endsection
