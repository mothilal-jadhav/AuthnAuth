@extends('layouts.app')

@section('title', 'Admin Dashboard - AuthnAuth')

@section('content')

<x-auth-shell title="Welcome, {{ auth()->user()->name }}">

    <p class="text-sm text-ink-muted">
        Your role: <strong class="font-semibold text-ink">{{ auth()->user()->role }}</strong>
    </p>

    <form method="POST" action="{{ url('/logout') }}" class="mt-6">
        @csrf

        <x-button size="lg" class="w-full">Logout</x-button>
    </form>

</x-auth-shell>

@endsection
