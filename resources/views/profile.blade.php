@extends('layouts.app')

@section('title', 'My Profile - AuthnAuth')

@section('content')

@include('partials.navbar')

@php
    $infoHasErrors = $errors->updateProfile->any();
    $passwordHasErrors = $errors->updatePassword->any();

    $activeTab = 'general';

    if ($passwordHasErrors || session('success') === 'Password updated successfully.') {
        $activeTab = 'password';
    }
@endphp

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Account"
        title="My Profile"
        subtitle="View and update your personal account information."
    >
        <x-slot:actions>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-muted transition hover:text-ink">
                <span aria-hidden="true">&larr;</span> Back to Dashboard
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr] lg:gap-10">

        <aside class="flex flex-row gap-1 overflow-x-auto lg:flex-col lg:overflow-visible lg:border-r lg:border-line lg:pr-6">
            <button
                type="button"
                data-tab-button="general"
                class="rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap transition {{ $activeTab === 'general' ? 'bg-paper-alt text-ink' : 'text-ink-muted hover:bg-paper-alt' }}"
            >
                General
            </button>

            <button
                type="button"
                data-tab-button="password"
                class="rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap transition {{ $activeTab === 'password' ? 'bg-paper-alt text-ink' : 'text-ink-muted hover:bg-paper-alt' }}"
            >
                Change Password
            </button>

            <button
                type="button"
                data-tab-button="settings"
                class="rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap transition {{ $activeTab === 'settings' ? 'bg-paper-alt text-ink' : 'text-ink-muted hover:bg-paper-alt' }}"
            >
                Settings
            </button>

            <form method="POST" action="{{ url('/logout') }}" class="lg:mt-4">
                @csrf
                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap text-danger transition hover:bg-danger-bg">
                    Logout
                </button>
            </form>
        </aside>

        <section class="max-w-2xl">

            <div data-tab-panel="general" class="{{ $activeTab === 'general' ? '' : 'hidden' }}">
                <div id="profile-info-view" class="{{ $infoHasErrors ? 'hidden' : '' }}">
                    <div class="flex items-center gap-4 border-b border-line pb-6">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-xl font-semibold text-brand-700">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>

                        <div>
                            <h2 class="font-display text-lg font-semibold text-ink">{{ auth()->user()->name }}</h2>
                            <p class="text-sm text-ink-muted">{{ auth()->user()->email }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 border-b border-line py-6">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Role</p>
                            <x-badge :variant="auth()->user()->role->badgeVariant()" class="mt-1">{{ strtoupper(auth()->user()->role->name) }}</x-badge>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Account Status</p>
                            <x-badge variant="success" class="mt-1">Active</x-badge>
                        </div>
                    </div>

                    <div class="pt-6">
                        <x-button type="button" variant="secondary" data-show="profile-info-edit" data-hide="profile-info-view">
                            Change Info
                        </x-button>
                    </div>
                </div>

                <div id="profile-info-edit" class="{{ $infoHasErrors ? '' : 'hidden' }}">
                    <h2 class="font-display text-lg font-semibold text-ink">Profile Information</h2>
                    <p class="mt-1 text-sm text-ink-muted">Update your name and email address.</p>

                    @if ($infoHasErrors)
                        <x-alert type="error" class="mt-4">
                            <ul class="list-disc space-y-1 pl-4">
                                @foreach ($errors->updateProfile->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 flex flex-col gap-5 border-t border-line pt-6">
                        @csrf
                        @method('PUT')

                        <x-field name="name" label="Full Name" value="{{ old('name', auth()->user()->name) }}" required />
                        <x-field name="email" label="Email Address" type="email" value="{{ old('email', auth()->user()->email) }}" required />

                        <div class="flex gap-3">
                            <x-button>Save Changes</x-button>
                            <x-button type="button" variant="secondary" data-show="profile-info-view" data-hide="profile-info-edit">
                                Cancel
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>

            <div data-tab-panel="password" class="{{ $activeTab === 'password' ? '' : 'hidden' }}">
                <h2 class="font-display text-lg font-semibold text-ink">Change Password</h2>
                <p class="mt-1 text-sm text-ink-muted">Enter your current password to continue, then choose a new one.</p>

                @if ($passwordHasErrors)
                    <x-alert type="error" class="mt-4">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->updatePassword->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 flex flex-col gap-5 border-t border-line pt-6">
                    @csrf
                    @method('PUT')

                    <x-field name="current_password" label="Current Password" type="password" required />

                    <p id="password-verify-error" class="hidden text-sm text-danger"></p>

                    <div id="password-verify-action" class="{{ $passwordHasErrors ? 'hidden' : '' }}">
                        <x-button type="button" id="password-verify-continue" variant="secondary" data-verify-url="{{ route('profile.password.verify') }}">
                            Continue
                        </x-button>
                    </div>

                    <div id="password-step-2" class="flex-col gap-5 {{ $passwordHasErrors ? 'flex' : 'hidden' }}">
                        <x-field name="password" label="New Password" type="password" required />
                        <x-field name="password_confirmation" label="Confirm New Password" type="password" required />

                        <x-button size="lg" class="w-full">Update Password</x-button>
                    </div>
                </form>
            </div>

            <div data-tab-panel="settings" class="{{ $activeTab === 'settings' ? '' : 'hidden' }}">
                <h2 class="font-display text-lg font-semibold text-ink">Settings</h2>
                <p class="mt-1 text-sm text-ink-muted border-b border-line pb-6">More settings coming soon.</p>
            </div>

        </section>

    </div>

</main>

@endsection
