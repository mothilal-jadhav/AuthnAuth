import { Head, Link, useForm } from '@inertiajs/react';
import Alert from '../../components/Alert';
import AppLayout from '../../components/AppLayout';
import Button from '../../components/Button';
import Card from '../../components/Card';
import Field from '../../components/Field';
import PageHeader from '../../components/PageHeader';
import { ucfirst } from '../../lib/format';

function Credential({ label, value, mono = false }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-ink-muted">{label}</dt>
            <dd className={`mt-0.5 text-sm ${mono ? 'font-mono font-semibold text-brand-700' : 'font-medium text-ink'}`}>{value}</dd>
        </div>
    );
}

// createdUser comes from a one-time session flash: present only on the
// page load right after a successful create, gone on reload.
function CreatedUser({ user }) {
    return (
        <Card>
            <span className="inline-flex items-center rounded-full border border-success-line bg-success-bg px-2.5 py-0.5 text-xs font-medium text-success">
                User Created
            </span>

            <h2 className="mt-3 font-display text-lg font-semibold text-ink">Account created successfully</h2>
            <p className="mt-1 text-sm text-ink-muted">Give these temporary credentials to the employee.</p>

            <Alert type="warning" className="mt-4">
                Save these credentials now. The temporary password should not be displayed again after leaving this page.
            </Alert>

            <dl className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Credential label="Name" value={user.name} />
                <Credential label="Email" value={user.email} />
                <Credential label="Role" value={ucfirst(user.role)} />
                <Credential label="Temporary Password" value={user.password} mono />
            </dl>

            <div className="mt-6">
                <Link
                    href="/users/create"
                    className="inline-flex items-center justify-center rounded-md border border-line bg-paper px-4 py-2 text-sm font-medium text-ink transition hover:bg-paper-alt"
                >
                    Create Another User
                </Link>
            </div>
        </Card>
    );
}

export default function Create({ roles, createdUser }) {
    const form = useForm({ name: '', email: '', role_id: '' });

    function submit(e) {
        e.preventDefault();
        form.post('/users');
    }

    return (
        <AppLayout>
            <Head title="Create User" />

            <main className="mx-auto max-w-2xl px-4 py-8 sm:px-6">
                <PageHeader
                    eyebrow="User Management"
                    title="Create User"
                    subtitle="Create an account and assign the appropriate role."
                    back="/users"
                    backLabel="Back to Users"
                />

                {createdUser ? (
                    <CreatedUser user={createdUser} />
                ) : (
                    <Card>
                        <h2 className="font-display text-lg font-semibold text-ink">User Information</h2>
                        <p className="mt-1 text-sm text-ink-muted">The account will be created with the selected role.</p>

                        <form onSubmit={submit} className="mt-6 flex flex-col gap-5">
                            <Field
                                name="name"
                                label="Full Name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                                placeholder="Enter employee name"
                                required
                            />

                            <Field
                                name="email"
                                label="Email Address"
                                type="email"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                error={form.errors.email}
                                placeholder="employee@example.com"
                                required
                            />

                            <Field
                                as="select"
                                name="role_id"
                                label="Role"
                                value={form.data.role_id}
                                onChange={(e) => form.setData('role_id', e.target.value)}
                                error={form.errors.role_id}
                                required
                            >
                                <option value="">Select a role</option>
                                {roles.map((role) => (
                                    <option key={role.id} value={role.id}>
                                        {ucfirst(role.name)}
                                    </option>
                                ))}
                            </Field>

                            <Alert type="info">
                                <strong className="font-semibold">Password.</strong> A secure temporary password will be generated
                                automatically for the new user.
                            </Alert>

                            <Button size="lg" className="w-full" disabled={form.processing}>
                                {form.processing ? 'Creating…' : 'Create User'}
                            </Button>
                        </form>
                    </Card>
                )}
            </main>
        </AppLayout>
    );
}
