import { Head, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import Alert from '../../components/Alert';
import AppLayout from '../../components/AppLayout';
import Button from '../../components/Button';
import { api } from '../../lib/api';

const ROLE_FILTERS = [
    ['', 'All'],
    ['admin', 'Admins'],
    ['manager', 'Managers'],
    ['user', 'Users'],
];

const BADGES = {
    admin: 'bg-danger-bg text-danger border-danger-line',
    manager: 'bg-warning-bg text-warning border-warning-line',
};

const STAT_BORDERS = {
    brand: 'border-t-brand-500',
    danger: 'border-t-danger',
    warning: 'border-t-warning',
    info: 'border-t-info',
};

const TH = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted';

function Stat({ label, value, color }) {
    return (
        <div className={`rounded-lg border-x border-b border-t-4 border-x-line border-b-line bg-paper p-4 shadow-sm ${STAT_BORDERS[color]}`}>
            <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">{label}</p>
            <p className="mt-1 font-display text-2xl font-bold text-ink">{value}</p>
        </div>
    );
}

// Filters live in the query string so reload/back keep them, same as the
// Blade list's ?role= links.
function readFilters() {
    const params = new URLSearchParams(window.location.search);

    return {
        role: params.get('role') ?? '',
        name: params.get('name') ?? '',
        page: Number(params.get('page')) || 1,
    };
}

export default function Index({ roleCounts, can }) {
    const { flash } = usePage().props;
    const [filters, setFilters] = useState(readFilters);
    const [search, setSearch] = useState(filters.name);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const [notice, setNotice] = useState(flash.success);
    const [loading, setLoading] = useState(true);

    const latestRequest = useRef(0);

    const total = Object.values(roleCounts).reduce((sum, n) => sum + Number(n), 0);

    const load = useCallback(async () => {
        // Filter changes can overlap; only the newest response may land.
        const id = ++latestRequest.current;
        setLoading(true);

        try {
            const data = await api('/users', { query: filters });

            if (id === latestRequest.current) {
                setResult(data);
                setError(null);
            }
        } catch (e) {
            if (id === latestRequest.current) {
                setError(e.message);
            }
        } finally {
            if (id === latestRequest.current) {
                setLoading(false);
            }
        }
    }, [filters]);

    useEffect(() => {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value && !(key === 'page' && value === 1)) {
                params.set(key, value);
            }
        });
        const qs = params.toString();
        window.history.replaceState(window.history.state, '', qs ? `?${qs}` : window.location.pathname);

        load();
    }, [filters, load]);

    // Debounced so typing doesn't burn through the API's 60/min limit.
    useEffect(() => {
        if (search === filters.name) {
            return undefined;
        }

        const timer = setTimeout(() => setFilters((f) => ({ ...f, name: search, page: 1 })), 300);

        return () => clearTimeout(timer);
    }, [search, filters.name]);

    async function destroy(user) {
        if (!window.confirm(`Are you sure you want to delete ${user.name}?`)) {
            return;
        }

        try {
            await api(`/users/${user.id}`, { method: 'DELETE' });
            setNotice('User deleted successfully.');

            // Step back if that emptied the last page.
            if (result.data.length === 1 && filters.page > 1) {
                setFilters((f) => ({ ...f, page: f.page - 1 }));
            } else {
                load();
            }
        } catch (e) {
            setError(e.message);
        }
    }

    const meta = result?.meta;

    return (
        <AppLayout>
            <Head title="Users" />

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <a href="/dashboard" className="text-sm font-medium text-brand-600 hover:text-brand-700">
                            ← Back to Dashboard
                        </a>
                        <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-brand-600">User Management</p>
                        <h1 className="font-display text-2xl font-bold text-ink">Users</h1>
                        <p className="mt-1 text-sm text-ink-muted">Manage users and their access levels.</p>
                    </div>

                    {can.create && (
                        <a
                            href="/users/create"
                            className="inline-flex items-center rounded-md bg-brand-solid px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-brand-solid-hover"
                        >
                            + Create User
                        </a>
                    )}
                </div>

                <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Stat label="Total Users" value={total} color="brand" />
                    <Stat label="Administrators" value={roleCounts.admin ?? 0} color="danger" />
                    <Stat label="Managers" value={roleCounts.manager ?? 0} color="warning" />
                    <Stat label="Users" value={roleCounts.user ?? 0} color="info" />
                </div>

                {notice && (
                    <Alert type="success" className="mb-6">
                        {notice}
                    </Alert>
                )}

                {error && (
                    <Alert type="error" className="mb-6">
                        {error}
                    </Alert>
                )}

                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div className="flex flex-wrap gap-2">
                        {ROLE_FILTERS.map(([value, label]) => (
                            <button
                                key={label}
                                type="button"
                                onClick={() => setFilters((f) => ({ ...f, role: value, page: 1 }))}
                                className={`rounded-full px-3 py-1.5 text-sm font-medium transition ${
                                    filters.role === value ? 'bg-brand-solid text-white' : 'text-ink-muted hover:bg-paper-alt'
                                }`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name…"
                        aria-label="Search by name"
                        className="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40 sm:w-64"
                    />
                </div>

                <div className={`overflow-x-auto rounded-lg border border-line bg-paper shadow-sm transition ${loading ? 'opacity-60' : ''}`}>
                    <table className="min-w-full divide-y divide-line">
                        <thead>
                            <tr>
                                <th className={TH}>User</th>
                                <th className={TH}>Email</th>
                                <th className={TH}>Role</th>
                                <th className={TH}>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            {result?.data.map((user) => (
                                <tr key={user.id} className="border-t border-line hover:bg-paper-alt">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-sm font-semibold text-brand-700">
                                                {user.name.charAt(0).toUpperCase()}
                                            </div>
                                            <span className="font-medium text-ink">{user.name}</span>
                                        </div>
                                    </td>

                                    <td className="px-4 py-3 text-sm text-ink-muted">{user.email}</td>

                                    <td className="px-4 py-3">
                                        <span
                                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ${
                                                BADGES[user.role?.name] ?? 'bg-paper-alt text-ink-muted border-line'
                                            }`}
                                        >
                                            {user.role?.name.toUpperCase()}
                                        </span>
                                    </td>

                                    <td className="px-4 py-3">
                                        {/* can.* are hints from the API; the edit/delete routes enforce the real rules. */}
                                        {user.can.update || user.can.delete ? (
                                            <div className="flex items-center gap-4">
                                                {user.can.update && (
                                                    <a href={`/users/${user.id}/edit`} className="text-sm font-medium text-brand-600 hover:text-brand-700">
                                                        Edit
                                                    </a>
                                                )}

                                                {user.can.delete && (
                                                    <button
                                                        type="button"
                                                        onClick={() => destroy(user)}
                                                        className="text-sm font-medium text-danger hover:opacity-80"
                                                    >
                                                        Delete
                                                    </button>
                                                )}
                                            </div>
                                        ) : (
                                            <span className="text-sm text-ink-muted">View only</span>
                                        )}
                                    </td>
                                </tr>
                            ))}

                            {result && result.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-10 text-center">
                                        <p className="font-medium text-ink">No users found</p>
                                        <p className="mt-1 text-sm text-ink-muted">Try a different filter, or create the first account.</p>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {meta && meta.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-between text-sm text-ink-muted">
                        <span>
                            Page {meta.current_page} of {meta.last_page} · {meta.total} users
                        </span>

                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                disabled={meta.current_page <= 1 || loading}
                                onClick={() => setFilters((f) => ({ ...f, page: f.page - 1 }))}
                            >
                                Previous
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                disabled={meta.current_page >= meta.last_page || loading}
                                onClick={() => setFilters((f) => ({ ...f, page: f.page + 1 }))}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </main>
        </AppLayout>
    );
}
