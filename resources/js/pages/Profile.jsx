import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Alert from '../components/Alert';
import AppLayout from '../components/AppLayout';
import Button from '../components/Button';
import ErrorSummary from '../components/ErrorSummary';
import Field from '../components/Field';
import PageHeader from '../components/PageHeader';
import RoleBadge from '../components/RoleBadge';
import { request } from '../lib/api';

const TABS = [
    ['general', 'General'],
    ['password', 'Change Password'],
    ['settings', 'Settings'],
];

function GeneralTab({ profile }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ name: profile.name, email: profile.email });

    function submit(e) {
        e.preventDefault();
        // The FormRequest uses the `updateProfile` bag so these errors never
        // show under the password form, and vice versa.
        form.put('/profile', { errorBag: 'updateProfile', onSuccess: () => setEditing(false) });
    }

    function cancel() {
        form.reset();
        form.clearErrors();
        setEditing(false);
    }

    if (!editing) {
        return (
            <div>
                <div className="flex items-center gap-4 border-b border-line pb-6">
                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-xl font-semibold text-brand-700">
                        {profile.name.charAt(0).toUpperCase()}
                    </div>

                    <div>
                        <h2 className="font-display text-lg font-semibold text-ink">{profile.name}</h2>
                        <p className="text-sm text-ink-muted">{profile.email}</p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-6 border-b border-line py-6">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Role</p>
                        <RoleBadge role={profile.role} className="mt-1" />
                    </div>

                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Account Status</p>
                        <span className="mt-1 inline-flex items-center rounded-full border border-success-line bg-success-bg px-2.5 py-0.5 text-xs font-medium text-success">
                            Active
                        </span>
                    </div>
                </div>

                <div className="pt-6">
                    <Button type="button" variant="secondary" onClick={() => setEditing(true)}>
                        Change Info
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div>
            <h2 className="font-display text-lg font-semibold text-ink">Profile Information</h2>
            <p className="mt-1 text-sm text-ink-muted">Update your name and email address.</p>

            <ErrorSummary errors={form.errors} className="mt-4" />

            <form onSubmit={submit} className="mt-6 flex flex-col gap-5 border-t border-line pt-6">
                <Field
                    name="name"
                    label="Full Name"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    error={form.errors.name}
                    required
                />

                <Field
                    name="email"
                    label="Email Address"
                    type="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    error={form.errors.email}
                    required
                />

                <div className="flex gap-3">
                    <Button disabled={form.processing}>{form.processing ? 'Saving…' : 'Save Changes'}</Button>
                    <Button type="button" variant="secondary" onClick={cancel}>
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    );
}

// Two steps: check the current password first (POST /profile/verify-password,
// a UX pre-check only), then reveal the new-password fields. The update
// endpoint re-validates current_password itself regardless.
function PasswordTab() {
    const [verified, setVerified] = useState(false);
    const [verifying, setVerifying] = useState(false);
    const [verifyError, setVerifyError] = useState(null);
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });

    const showStep2 = verified || Object.keys(form.errors).length > 0;

    async function verify() {
        setVerifying(true);
        setVerifyError(null);

        try {
            await request('/profile/verify-password', {
                method: 'POST',
                body: { current_password: form.data.current_password },
            });
            setVerified(true);
        } catch (e) {
            setVerifyError(e.message);
        } finally {
            setVerifying(false);
        }
    }

    function submit(e) {
        e.preventDefault();

        if (!showStep2) {
            verify();

            return;
        }

        form.put('/profile/password', {
            errorBag: 'updatePassword',
            onSuccess: () => {
                form.reset();
                setVerified(false);
            },
            onError: () => form.reset('password', 'password_confirmation'),
        });
    }

    return (
        <div>
            <h2 className="font-display text-lg font-semibold text-ink">Change Password</h2>
            <p className="mt-1 text-sm text-ink-muted">Enter your current password to continue, then choose a new one.</p>

            <ErrorSummary errors={form.errors} className="mt-4" />

            <form onSubmit={submit} className="mt-6 flex flex-col gap-5 border-t border-line pt-6">
                <Field
                    name="current_password"
                    label="Current Password"
                    type="password"
                    value={form.data.current_password}
                    onChange={(e) => {
                        form.setData('current_password', e.target.value);
                        setVerified(false);
                    }}
                    error={verifyError ?? form.errors.current_password}
                    autoComplete="current-password"
                    required
                />

                {showStep2 ? (
                    <>
                        <Field
                            name="password"
                            label="New Password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            error={form.errors.password}
                            autoComplete="new-password"
                            required
                        />

                        <Field
                            name="password_confirmation"
                            label="Confirm New Password"
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                            autoComplete="new-password"
                            required
                        />

                        <Button size="lg" className="w-full" disabled={form.processing}>
                            {form.processing ? 'Updating…' : 'Update Password'}
                        </Button>
                    </>
                ) : (
                    <div>
                        <Button variant="secondary" disabled={verifying}>
                            {verifying ? 'Checking…' : 'Continue'}
                        </Button>
                    </div>
                )}
            </form>
        </div>
    );
}

export default function Profile({ profile }) {
    const { flash, csrf_token: csrfToken } = usePage().props;
    const [tab, setTab] = useState(flash.success === 'Password updated successfully.' ? 'password' : 'general');

    return (
        <AppLayout>
            <Head title="My Profile" />

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <PageHeader
                    eyebrow="Account"
                    title="My Profile"
                    subtitle="View and update your personal account information."
                    back="/dashboard"
                    backLabel="Back to Dashboard"
                />

                {flash.success && (
                    <Alert type="success" className="mb-6">
                        {flash.success}
                    </Alert>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr] lg:gap-10">
                    <aside className="flex flex-row gap-1 overflow-x-auto lg:flex-col lg:overflow-visible lg:border-r lg:border-line lg:pr-6">
                        {TABS.map(([key, label]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => setTab(key)}
                                className={`rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap transition ${
                                    tab === key ? 'bg-paper-alt text-ink' : 'text-ink-muted hover:bg-paper-alt'
                                }`}
                            >
                                {label}
                            </button>
                        ))}

                        <form method="POST" action="/logout" className="lg:mt-4">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <button
                                type="submit"
                                className="w-full rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap text-danger transition hover:bg-danger-bg"
                            >
                                Logout
                            </button>
                        </form>
                    </aside>

                    <section className="max-w-2xl">
                        {tab === 'general' && <GeneralTab profile={profile} />}
                        {tab === 'password' && <PasswordTab />}
                        {tab === 'settings' && (
                            <div>
                                <h2 className="font-display text-lg font-semibold text-ink">Settings</h2>
                                <p className="mt-1 border-b border-line pb-6 text-sm text-ink-muted">More settings coming soon.</p>
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </AppLayout>
    );
}
