@extends('layouts.app')

@section('title', 'Activity Log - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Administration"
        title="Activity Log"
        subtitle="A record of account changes across the app."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    />

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">When</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Action</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Subject</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Details</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($activity as $entry)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->causer?->name ?? 'System' }}</td>
                    <td class="px-4 py-3"><x-badge>{{ $entry->action }}</x-badge></td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->subject?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->description }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="🕘" title="Nothing to see yet" description="Once someone creates, edits, or removes an account, it'll show up here." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $activity->links() }}
    </div>

</main>

@endsection
