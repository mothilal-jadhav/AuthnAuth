import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../components/AppLayout';
import Button from '../../components/Button';
import Card from '../../components/Card';
import Field from '../../components/Field';
import PageHeader from '../../components/PageHeader';
import { ucfirst } from '../../lib/format';

export default function Edit({ user, roles, functionalRoles, canAssignFunctionalRoles }) {
    // Hidden while no functional roles exist (none are seeded) or the actor
    // may not grant them (UserPolicy::assignFunctionalRoles).
    const showFunctionalRoles = functionalRoles.length > 0 && canAssignFunctionalRoles;

    const form = useForm({
        name: user.name,
        email: user.email,
        role_id: user.role_id,
        functional_role_ids: user.functional_role_ids,
    });

    function toggleFunctionalRole(id) {
        const ids = form.data.functional_role_ids;
        form.setData('functional_role_ids', ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]);
    }

    function submit(e) {
        e.preventDefault();

        // The update route only syncs functional roles when this marker is
        // present, so it's sent only when the section was actually shown.
        form.transform((data) => {
            const { functional_role_ids: functionalRoleIds, ...rest } = data;

            return showFunctionalRoles
                ? { ...rest, functional_role_ids: functionalRoleIds, functional_role_ids_submitted: '1' }
                : rest;
        });
        form.put(`/users/${user.id}`);
    }

    return (
        <AppLayout>
            <Head title="Edit User" />

            <main className="mx-auto max-w-2xl px-4 py-8 sm:px-6">
                <PageHeader
                    eyebrow="User Management"
                    title="Edit User"
                    subtitle="Update account information and role."
                    back="/users"
                    backLabel="Back to Users"
                />

                <Card>
                    <h2 className="font-display text-lg font-semibold text-ink">User Information</h2>
                    <p className="mt-1 text-sm text-ink-muted">Update the user's account details.</p>

                    <form onSubmit={submit} className="mt-6 flex flex-col gap-5">
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

                        <Field
                            as="select"
                            name="role_id"
                            label="Role"
                            value={form.data.role_id}
                            onChange={(e) => form.setData('role_id', Number(e.target.value))}
                            error={form.errors.role_id}
                            required
                        >
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {ucfirst(role.name)}
                                </option>
                            ))}
                        </Field>

                        {showFunctionalRoles && (
                            <div className="flex flex-col gap-2">
                                <p className="text-sm font-medium text-ink">Functional Roles</p>
                                <p className="text-xs text-ink-muted">
                                    Additive permissions that don't affect {user.name}'s management hierarchy.
                                </p>

                                <div className="mt-1 flex flex-col gap-2 rounded-md border border-line p-3">
                                    {functionalRoles.map((role) => (
                                        <label key={role.id} className="flex items-center gap-2 text-sm text-ink">
                                            <input
                                                type="checkbox"
                                                checked={form.data.functional_role_ids.includes(role.id)}
                                                onChange={() => toggleFunctionalRole(role.id)}
                                                className="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500/40"
                                            />
                                            {role.name}
                                        </label>
                                    ))}
                                </div>

                                {form.errors.functional_role_ids && (
                                    <p className="text-sm text-danger">{form.errors.functional_role_ids}</p>
                                )}
                            </div>
                        )}

                        <Button size="lg" className="w-full" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Update User'}
                        </Button>
                    </form>
                </Card>
            </main>
        </AppLayout>
    );
}
