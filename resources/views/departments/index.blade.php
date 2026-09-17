@extends('layouts.app')

@section('title', 'Departments - AuthnAuth')

@section('content')

@include('partials.navbar')

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

    <x-page-header
        eyebrow="Organization"
        title="Departments"
        subtitle="Manage departments and their heads."
        back="{{ route('dashboard') }}"
        backLabel="Back to Dashboard"
    >
        <x-slot:actions>
            @if (auth()->user()->hasPermission('departments.create'))
                <x-button :href="route('departments.create')" size="sm">+ Create Department</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-table>
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Department</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Head</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Users</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($departments as $department)
                <tr class="border-t border-line hover:bg-paper-alt">
                    <td class="px-4 py-3 font-medium text-ink">{{ $department->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $department->head->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $department->users_count }}</td>

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-4">
                            @if (auth()->user()->hasPermission('departments.update'))
                                <a href="{{ route('departments.edit', $department) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                                    Edit
                                </a>
                            @endif

                            @if (auth()->user()->hasPermission('departments.delete'))
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" data-confirm="Are you sure you want to delete this department?">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="text-sm font-medium text-danger hover:opacity-80">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <x-empty-state icon="🏢" title="No departments found" description="Create your first department to start organizing users." class="m-4 border-0" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $departments->links() }}
    </div>

</main>

@endsection
