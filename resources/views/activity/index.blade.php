@extends('layouts.app')

@section('title', 'Activity Log - AuthnAuth')

@section('content')

<div class="dashboard-page">

    @include('partials.navbar')


<div class="users-page">

    <a href="{{ route('dashboard') }}" class="back-dashboard">
        ← Back to Dashboard
    </a>

    <div class="users-header">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Activity Log</h1>
            <p class="users-subtitle">
                A record of account changes across the app.
            </p>
        </div>
    </div>


    <section class="user-group">

        <div class="table-wrapper">

            <table class="users-table">

                <thead>
                    <tr>
                        <th>When</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($activity as $entry)

                        <tr>

                            <td class="email">
                                {{ $entry->created_at->diffForHumans() }}
                            </td>

                            <td class="email">
                                {{ $entry->causer?->name ?? 'System' }}
                            </td>

                            <td>
                                <span class="activity-action">
                                    {{ $entry->action }}
                                </span>
                            </td>

                            <td class="email">
                                {{ $entry->subject?->name ?? '—' }}
                            </td>

                            <td class="email">
                                {{ $entry->description }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="empty-state">
                                No activity recorded yet.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $activity->links() }}
        </div>

    </section>

</div>

</div>

@endsection
