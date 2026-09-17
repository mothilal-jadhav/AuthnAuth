@extends('layouts.app')

@section('title', 'My Profile - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Account"
        title="My Profile"
        subtitle="View and update your personal account information."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    />

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-6">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-xl font-semibold text-brand-700">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div>
                <h2 class="font-display text-lg font-semibold text-ink">{{ auth()->user()->name }}</h2>
                <p class="text-sm text-ink-muted">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-6 border-t border-line pt-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Role</p>
                <x-badge variant="brand" class="mt-1">{{ strtoupper(auth()->user()->role->name) }}</x-badge>
            </div>

            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Account Status</p>
                <x-badge variant="success" class="mt-1">Active</x-badge>
            </div>
        </div>
    </x-card>

    <x-card class="mb-6">
        <h2 class="font-display text-lg font-semibold text-ink">Profile Information</h2>
        <p class="mt-1 text-sm text-ink-muted">Update your name and email address.</p>

        @if ($errors->updateProfile->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->updateProfile->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <x-field name="name" label="Full Name" value="{{ old('name', auth()->user()->name) }}" required />
            <x-field name="email" label="Email Address" type="email" value="{{ old('email', auth()->user()->email) }}" required />

            <x-button size="lg" class="w-full">Save Changes</x-button>
        </form>
    </x-card>

    <x-card>
        <h2 class="font-display text-lg font-semibold text-ink">Change Password</h2>
        <p class="mt-1 text-sm text-ink-muted">Update your password. You'll need to enter your current one.</p>

        @if ($errors->updatePassword->any())
            <x-alert type="error" class="mt-4">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->updatePassword->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <x-field name="current_password" label="Current Password" type="password" required />
            <x-field name="password" label="New Password" type="password" required />
            <x-field name="password_confirmation" label="Confirm New Password" type="password" required />

            <x-button size="lg" class="w-full">Update Password</x-button>
        </form>
    </x-card>

</main>

@endsection
