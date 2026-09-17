@extends('layouts.app')

@section('title', 'Dashboard - AuthnAuth')

@section('content')

@include('partials.navbar')

@php
    $quickActions = collect([
        ['permission' => 'users.view', 'href' => route('users.index'), 'icon' => '👁', 'title' => 'View Users', 'description' => 'View users you are authorized to access.', 'color' => 'brand'],
        ['permission' => 'users.create', 'href' => route('users.create'), 'icon' => '➕', 'title' => 'Create User', 'description' => 'Create a new user account.', 'color' => 'success'],
        ['permission' => 'activity.view', 'href' => route('activity.index'), 'icon' => '🕘', 'title' => 'Activity Log', 'description' => 'Review recent account changes.', 'color' => 'info'],
        ['permission' => 'departments.view', 'href' => route('departments.index'), 'icon' => '🏢', 'title' => 'Departments', 'description' => 'Manage departments and their heads.', 'color' => 'accent'],
        ['permission' => 'profile.view', 'href' => route('profile'), 'icon' => strtoupper(substr(auth()->user()->name, 0, 1)), 'title' => 'My Profile', 'description' => 'View your account information.', 'color' => 'warning'],
    ])->filter(fn ($action) => auth()->user()->hasPermission($action['permission']));

    $chipColors = [
        'brand' => 'bg-brand-50 text-brand-700',
        'accent' => 'bg-accent-50 text-accent-700',
        'success' => 'bg-success-bg text-success',
        'warning' => 'bg-warning-bg text-warning',
        'info' => 'bg-info-bg text-info',
    ];
@endphp

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Dashboard</p>
            <h1 class="font-display text-2xl font-bold text-ink">Welcome, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-ink-muted">Manage your account and access available resources.</p>
        </div>

        <x-badge :variant="auth()->user()->role->badgeVariant()" class="text-sm">{{ strtoupper(auth()->user()->role->name) }}</x-badge>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Account" value="Active" hint="Authenticated user" color="info" />
        <x-stat label="Role" value="{{ ucfirst(auth()->user()->role->name) }}" hint="Assigned access level" color="brand" />
        <x-stat label="Access" value="{{ auth()->user()->role->permissions->count() }}" hint="Available actions" color="accent" />
    </div>

    <section>
        <h2 class="font-display text-lg font-semibold text-ink">Available Actions</h2>
        <p class="mt-1 text-sm text-ink-muted">Actions available to your account.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($quickActions as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="group flex items-start gap-4 rounded-lg border border-line bg-paper p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg {{ $chipColors[$action['color']] }}">
                        {{ $action['icon'] }}
                    </div>

                    <div>
                        <h3 class="font-display font-semibold text-ink group-hover:text-brand-700">{{ $action['title'] }}</h3>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $action['description'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

</main>

@endsection
