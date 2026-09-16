@extends('layouts.app')

@section('title', 'Create User - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


    <main class="user-page-container">

        <div class="page-header">

            <div>
                <p class="eyebrow">User Management</p>

                <h1>Create User</h1>

                <p>
                    Create an account and assign the appropriate role.
                </p>
            </div>

            <a href="{{ route('users.index') }}" class="secondary-button">
                ← Back to Users
            </a>

        </div>


        @if (session('created_user'))

            <div class="credentials-card">

                <div class="credentials-header">

                    <div>
                        <span class="success-badge">User Created</span>

                        <h2>
                            Account created successfully
                        </h2>

                        <p>
                            Give these temporary credentials to the employee.
                        </p>
                    </div>

                </div>


                <div class="credentials-warning">
                    Save these credentials now. The temporary password
                    should not be displayed again after leaving this page.
                </div>


                <div class="credentials-grid">

                    <div class="credential-item">
                        <span>Name</span>
                        <strong>
                            {{ session('created_user.name') }}
                        </strong>
                    </div>

                    <div class="credential-item">
                        <span>Email</span>
                        <strong>
                            {{ session('created_user.email') }}
                        </strong>
                    </div>

                    <div class="credential-item">
                        <span>Role</span>
                        <strong>
                            {{ ucfirst(session('created_user.role')) }}
                        </strong>
                    </div>

                    <div class="credential-item">
                        <span>Temporary Password</span>
                        <strong class="temporary-password">
                            {{ session('created_user.password') }}
                        </strong>
                    </div>

                </div>

                <div class="credentials-actions">

                    <a
                        href="{{ route('users.create') }}"
                        class="auth-button"
                    >
                        Create Another User
                    </a>

                </div>

            </div>

        @else

            <div class="form-card">

                <div class="form-card-header">

                    <h2>User Information</h2>

                    <p>
                        The account will be created with the selected role.
                    </p>

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


                <form
                    method="POST"
                    action="{{ route('users.store') }}"
                >

                    @csrf


                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter employee name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="employee@example.com"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="role_id">
                            Role
                        </label>

                        <select
                            id="role_id"
                            name="role_id"
                            required
                        >

                            <option value="">
                                Select a role
                            </option>

                            @foreach ($roles as $role)

                                <option
                                    value="{{ $role->id }}"
                                    {{ old('role_id') == $role->id ? 'selected' : '' }}
                                >
                                    {{ ucfirst($role->name) }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="form-info">

                        <strong>Password</strong>

                        <p>
                            A secure temporary password will be generated
                            automatically for the new user.
                        </p>

                    </div>


                    <button
                        type="submit"
                        class="auth-button"
                    >
                        Create User
                    </button>

                </form>

            </div>

        @endif

    </main>

</div>

@endsection