@extends('layouts.app')

@section('title', 'Departments - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


    <main class="user-page-container">

        <div class="page-header">

            <div>
                <p class="eyebrow">Organization</p>

                <h1>Departments</h1>

                <p>
                    Manage departments and their heads.
                </p>
            </div>

            <div class="header-actions">

                <a href="{{ route('dashboard') }}" class="secondary-button">
                    ← Back to Dashboard
                </a>

                @if(auth()->user()->hasPermission('departments.create'))
                    <a href="{{ route('departments.create') }}" class="primary-button">
                        <span>+</span>
                        Create Department
                    </a>
                @endif

            </div>

        </div>


        @if(session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif


        <section class="user-group">

            <div class="table-wrapper">

                <table class="users-table">

                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Head</th>
                            <th>Users</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($departments as $department)

                            <tr>

                                <td>
                                    <strong>{{ $department->name }}</strong>
                                </td>

                                <td>
                                    {{ $department->head->name ?? '—' }}
                                </td>

                                <td>
                                    {{ $department->users_count }}
                                </td>

                                <td>

                                    <div class="user-actions">

                                        @if(auth()->user()->hasPermission('departments.update'))
                                            <a
                                                href="{{ route('departments.edit', $department) }}"
                                                class="edit-button"
                                            >
                                                Edit
                                            </a>
                                        @endif

                                        @if(auth()->user()->hasPermission('departments.delete'))
                                            <form
                                                method="POST"
                                                action="{{ route('departments.destroy', $department) }}"
                                                data-confirm="Are you sure you want to delete this department?"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="delete-button"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="empty-state">
                                    No departments found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="pagination-wrapper">
                {{ $departments->links() }}
            </div>

        </section>

    </main>

</div>

@endsection
