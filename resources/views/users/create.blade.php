@extends('layouts.app')

@section('title', 'Create User - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="User Management"
        title="Create User"
        subtitle="Create an account and assign the appropriate role."
        back="{{ route('users.index') }}"
        backLabel="Back to Users"
    />

    @if (session('created_user'))

        <x-card>
            <x-badge variant="success">User Created</x-badge>

            <h2 class="mt-3 font-display text-lg font-semibold text-ink">Account created successfully</h2>
            <p class="mt-1 text-sm text-ink-muted">Give these temporary credentials to the employee.</p>

            <x-alert type="warning" class="mt-4">
                Save these credentials now. The temporary password should not be displayed again after leaving this page.
            </x-alert>

            <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Name</dt>
                    <dd class="mt-0.5 text-sm font-medium text-ink">{{ session('created_user.name') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Email</dt>
                    <dd class="mt-0.5 text-sm font-medium text-ink">{{ session('created_user.email') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Role</dt>
                    <dd class="mt-0.5 text-sm font-medium text-ink">{{ ucfirst(session('created_user.role')) }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Temporary Password</dt>
                    <dd class="mt-0.5 font-mono text-sm font-semibold text-brand-700">{{ session('created_user.password') }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <x-button :href="route('users.create')" variant="secondary">Create Another User</x-button>
            </div>
        </x-card>

    @else

        <x-card>
            <h2 class="font-display text-lg font-semibold text-ink">User Information</h2>
            <p class="mt-1 text-sm text-ink-muted">The account will be created with the selected role.</p>

            @if ($errors->any())
                <x-alert type="error" class="mt-4">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('users.store') }}" class="mt-6 flex flex-col gap-5">
                @csrf

                <x-field name="name" label="Full Name" value="{{ old('name') }}" placeholder="Enter employee name" required />
                <x-field name="email" label="Email Address" type="email" value="{{ old('email') }}" placeholder="employee@example.com" required />

                <x-field name="role_id" label="Role" as="select" required>
                    <option value="">Select a role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </x-field>

                <x-field name="department_id" label="Department" as="select">
                    <option value="">No department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-field>

                <x-alert type="info">
                    <strong class="font-semibold">Password.</strong>
                    A secure temporary password will be generated automatically for the new user.
                </x-alert>

                <x-button size="lg" class="w-full">Create User</x-button>
            </form>
        </x-card>

    @endif

</main>

@endsection
