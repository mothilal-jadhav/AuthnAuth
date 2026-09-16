@extends('layouts.app')

@section('title', 'Edit Department - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


    <main class="user-page-container">

        <div class="page-header">

            <div>
                <p class="eyebrow">Organization</p>

                <h1>Edit Department</h1>
            </div>

            <a href="{{ route('departments.index') }}" class="secondary-button">
                ← Back to Departments
            </a>

        </div>


        <div class="form-card">

            <div class="form-card-header">
                <h2>Department Information</h2>
            </div>

            @if ($errors->any())

                <div class="error-container">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            @endif

            <form method="POST" action="{{ route('departments.update', $department) }}">

                @csrf
                @method('PUT')

                <div class="form-group">

                    <label for="name">
                        Department Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $department->name) }}"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="head_user_id">
                        Department Head
                    </label>

                    <select
                        id="head_user_id"
                        name="head_user_id"
                    >

                        <option value="">
                            No head assigned
                        </option>

                        @foreach ($users as $user)

                            <option
                                value="{{ $user->id }}"
                                {{ old('head_user_id', $department->head_user_id) == $user->id ? 'selected' : '' }}
                            >
                                {{ $user->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <button type="submit" class="auth-button">
                    Update Department
                </button>

            </form>

        </div>

    </main>

</div>

@endsection
