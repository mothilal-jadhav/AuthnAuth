@extends('layouts.app')

@section('title', 'Edit User - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="User Management"
        title="Edit User"
        subtitle="Update account information and role."
        back="{{ route('users.index') }}"
        backLabel="Back to Users"
    />

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">User Information</h2>
        <p class="mt-1 text-sm text-ink-muted">Update the user's account details.</p>

        @if ($errors->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <x-field name="name" label="Full Name" value="{{ old('name', $user->name) }}" required />
            <x-field name="email" label="Email Address" type="email" value="{{ old('email', $user->email) }}" required />

            <x-field name="role_id" label="Role" as="select" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ ucfirst($role->name) }}</option>
                @endforeach
            </x-field>

            <x-field name="department_id" label="Department" as="select">
                <option value="">No department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-field>

            @can('assignFunctionalRoles', $user)
                <input type="hidden" name="functional_role_ids_submitted" value="1">

                <div class="flex flex-col gap-2">
                    <p class="text-sm font-medium text-ink">Functional Roles</p>
                    <p class="text-xs text-ink-muted">
                        Additive permissions that don't affect {{ $user->name }}'s management hierarchy.
                    </p>

                    <div class="mt-1 flex flex-col gap-2 rounded-md border border-line p-3">
                        @foreach ($functionalRoles as $functionalRole)
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    name="functional_role_ids[]"
                                    value="{{ $functionalRole->id }}"
                                    class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500/40"
                                    @checked(in_array($functionalRole->id, old('functional_role_ids', $user->functionalRoles->pluck('id')->all())))
                                >
                                {{ $functionalRole->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endcan

            <x-button size="lg" class="w-full">Update User</x-button>
        </form>
    </x-card>

</main>

@endsection
