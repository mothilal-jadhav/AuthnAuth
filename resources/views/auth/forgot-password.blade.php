@extends('layouts.app')

@section('title', 'Forgot Password - AuthnAuth')

@section('content')

<x-auth-shell title="Let's get you a new link">

    @if (session('status'))
        <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert type="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ url('/forgot-password') }}" class="flex flex-col gap-5">
        @csrf

        <x-field
            name="email"
            label="Email"
            type="email"
            value="{{ old('email') }}"
            required
            autofocus
            hint="We'll send a reset link to this address."
        />

        <x-button size="lg" class="w-full">Send reset link</x-button>
    </form>

    <x-slot:footer>
        Remember your password? <a href="{{ url('/login') }}" class="font-medium text-brand-600 hover:text-brand-700">Login</a>
    </x-slot:footer>

</x-auth-shell>

@endsection
