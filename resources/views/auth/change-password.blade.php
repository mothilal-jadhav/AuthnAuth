@extends('layouts.app')

@section('title', 'Change Password - AuthnAuth')

@section('content')

<x-auth-shell title="Let's set a fresh password before you continue">

    @if ($errors->any())
        <x-alert type="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ url('/password/change') }}" class="flex flex-col gap-5">
        @csrf

        <x-field name="password" label="New Password" type="password" required />
        <x-field name="password_confirmation" label="Confirm New Password" type="password" required />

        <x-button size="lg" class="w-full">Set password</x-button>
    </form>

</x-auth-shell>

@endsection
