import { Head, Link, usePage } from '@inertiajs/react';
import Alert from '../components/Alert';
import AppLayout from '../components/AppLayout';
import RoleBadge from '../components/RoleBadge';

const CHIP_COLORS = {
    brand: 'bg-brand-50 text-brand-700',
    success: 'bg-success-bg text-success',
    info: 'bg-info-bg text-info',
};

const CARD = 'group flex w-96 shrink-0 snap-start items-start gap-6 rounded-xl border border-line bg-paper p-8 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:w-[26rem]';

function QuickAction({ action }) {
    const body = (
        <>
            <div className={`flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-3xl ${CHIP_COLORS[action.color]}`}>
                {action.icon}
            </div>

            <div>
                <h3 className="font-display text-xl font-semibold text-ink group-hover:text-brand-700">{action.title}</h3>
                <p className="mt-1.5 text-sm text-ink-muted">{action.description}</p>
            </div>
        </>
    );

    return (
        <Link href={action.href} className={CARD}>
            {body}
        </Link>
    );
}

export default function Dashboard({ role, can }) {
    const { auth, flash } = usePage().props;

    const actions = [
        { href: '/profile', icon: '👤', title: 'My Profile', description: 'Update your details and change your password.', color: 'info' },
        can.viewUsers && { href: '/users', icon: '👁', title: 'View Users', description: 'View users you are authorized to access.', color: 'brand' },
        can.createUsers && { href: '/users/create', icon: '➕', title: 'Create User', description: 'Create a new user account.', color: 'success' },
    ].filter(Boolean);

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-brand-600">Dashboard</p>
                        <h1 className="font-display text-2xl font-bold text-ink">Welcome, {auth.user.name}</h1>
                        <p className="mt-1 text-sm text-ink-muted">Manage your account and access available resources.</p>
                    </div>

                    <RoleBadge role={role} className="text-sm" />
                </div>

                {flash.success && (
                    <Alert type="success" className="mb-6">
                        {flash.success}
                    </Alert>
                )}

                <section>
                    <div className="scrollbar-hide -mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-4 pt-4 pb-3 sm:-mx-6 sm:px-6">
                        {actions.map((action) => (
                            <QuickAction key={action.href} action={action} />
                        ))}
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}
