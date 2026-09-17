@extends('layouts.app')

@section('title', 'Edit Department - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Organization"
        title="Edit Department"
        back="{{ route('departments.index') }}"
        backLabel="Back to Departments"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Department Information</h2>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('departments.update', $department) }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <x-field name="name" label="Department Name" value="{{ old('name', $department->name) }}" required />

            <x-field name="head_user_id" label="Department Head" as="select">
                <option value="">No head assigned</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('head_user_id', $department->head_user_id) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </x-field>

            <x-button size="lg" class="w-full">Update Department</x-button>
        </form>
    </x-card>

</main>

@endsection
