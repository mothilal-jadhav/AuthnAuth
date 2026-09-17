@extends('layouts.app')

@section('title', 'Reset Password - AuthnAuth')

@section('content')

<x-auth-shell title="Choose a new password">

    @if ($errors->any())
        <x-alert type="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ url('/reset-password') }}" class="flex flex-col gap-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-field name="email" label="Email" type="email" value="{{ old('email', $email) }}" required />
        <x-field name="password" label="New Password" type="password" required />
        <x-field name="password_confirmation" label="Confirm New Password" type="password" required />

        <x-button size="lg" class="w-full">Reset password</x-button>
    </form>

</x-auth-shell>

@endsection
