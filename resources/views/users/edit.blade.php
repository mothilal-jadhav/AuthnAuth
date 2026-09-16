@extends('layouts.app')

@section('title', 'Edit User - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


    <main class="user-page-container">

        <div class="page-header">

            <div>
                <p class="eyebrow">User Management</p>

                <h1>Edit User</h1>

                <p>
                    Update account information and role.
                </p>
            </div>

            <a
                href="{{ route('users.index') }}"
                class="secondary-button"
            >
                ← Back to Users
            </a>

        </div>


        <div class="form-card">

            <div class="form-card-header">

                <h2>User Information</h2>

                <p>
                    Update the user's account details.
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
                action="{{ route('users.update', $user) }}"
            >

                @csrf
                @method('PUT')


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
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
                        value="{{ old('email', $user->email) }}"
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

                        @foreach($roles as $role)

                            <option
                                value="{{ $role->id }}"
                                {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}
                            >
                                {{ ucfirst($role->name) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="form-group">

                    <label for="department_id">
                        Department
                    </label>

                    <select
                        id="department_id"
                        name="department_id"
                    >

                        <option value="">
                            No department
                        </option>

                        @foreach ($departments as $department)

                            <option
                                value="{{ $department->id }}"
                                {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}
                            >
                                {{ $department->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                @can('assignFunctionalRoles', $user)

                    <input type="hidden" name="functional_role_ids_submitted" value="1">

                    <div class="form-group">

                        <label>
                            Functional Roles
                        </label>

                        <p>
                            Additive permissions that don't affect
                            {{ $user->name }}'s management hierarchy.
                        </p>

                        @foreach ($functionalRoles as $functionalRole)

                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="functional_role_ids[]"
                                    value="{{ $functionalRole->id }}"
                                    {{ in_array($functionalRole->id, old('functional_role_ids', $user->functionalRoles->pluck('id')->all())) ? 'checked' : '' }}
                                >
                                {{ $functionalRole->name }}
                            </label>

                        @endforeach

                    </div>

                @endcan


                <button
                    type="submit"
                    class="auth-button"
                >
                    Update User
                </button>

            </form>

        </div>

    </main>

</div>

@endsection