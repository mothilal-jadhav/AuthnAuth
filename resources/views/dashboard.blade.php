@extends('layouts.app')

@section('title', 'Dashboard - AuthnAuth')

@section('content')

@include('partials.navbar')

@php
    $quickActions = collect([
        ['permission' => null, 'href' => route('profile'), 'icon' => '👤', 'title' => 'My Profile', 'description' => 'Update your details and change your password.', 'color' => 'info'],
        ['permission' => 'users.view', 'href' => route('users.index'), 'icon' => '👁', 'title' => 'View Users', 'description' => 'View users you are authorized to access.', 'color' => 'brand'],
        ['permission' => 'users.create', 'href' => route('users.create'), 'icon' => '➕', 'title' => 'Create User', 'description' => 'Create a new user account.', 'color' => 'success'],
    ])->filter(fn ($action) => $action['permission'] === null || auth()->user()->hasPermission($action['permission']));

    $chipColors = [
        'brand' => 'bg-brand-50 text-brand-700',
        'accent' => 'bg-accent-50 text-accent-700',
        'success' => 'bg-success-bg text-success',
        'info' => 'bg-info-bg text-info',
        'warning' => 'bg-warning-bg text-warning',
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

    <section>
        <div class="scrollbar-hide -mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-4 pt-4 pb-3 sm:-mx-6 sm:px-6">
            @foreach ($quickActions as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="group flex w-96 shrink-0 snap-start items-start gap-6 rounded-xl border border-line bg-paper p-8 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:w-[26rem]"
                >
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-3xl {{ $chipColors[$action['color']] }}">
                        {{ $action['icon'] }}
                    </div>

                    <div>
                        <h3 class="font-display text-xl font-semibold text-ink group-hover:text-brand-700">{{ $action['title'] }}</h3>
                        <p class="mt-1.5 text-sm text-ink-muted">{{ $action['description'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

</main>

@endsection
