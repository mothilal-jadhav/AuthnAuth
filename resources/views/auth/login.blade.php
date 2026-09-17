@extends('layouts.app')

@section('title', 'Login - AuthnAuth')

@section('content')

<x-auth-shell title="Good to see you again">

    @if ($errors->any())
        <x-alert type="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ url('/login') }}" class="flex flex-col gap-5">
        @csrf

        <x-field name="email" label="Email" type="email" value="{{ old('email') }}" required autofocus />

        <div class="flex flex-col gap-1.5">
            <x-field name="password" label="Password" type="password" required />

            <a href="{{ url('/forgot-password') }}" class="self-end text-sm font-medium text-brand-600 hover:text-brand-700">
                Forgot your password?
            </a>
        </div>

        <x-button size="lg" class="w-full">Sign in</x-button>
    </form>

</x-auth-shell>

@endsection
