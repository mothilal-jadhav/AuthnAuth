import { Head, useForm } from '@inertiajs/react';
import AuthShell from '../../components/AuthShell';
import Button from '../../components/Button';
import ErrorSummary from '../../components/ErrorSummary';
import Field from '../../components/Field';

// The forced first-login change (ForcePasswordChange redirects here while
// must_change_password is set).
export default function ChangePassword() {
    const form = useForm({ password: '', password_confirmation: '' });

    function submit(e) {
        e.preventDefault();
        form.post('/password/change', { onError: () => form.reset() });
    }

    return (
        <>
            <Head title="Change Password" />

            <AuthShell title="Let's set a fresh password before you continue">
                <ErrorSummary errors={form.errors} />

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Field
                        name="password"
                        label="New Password"
                        type="password"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                        error={form.errors.password}
                        autoComplete="new-password"
                        required
                        autoFocus
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
                        {form.processing ? 'Saving…' : 'Set password'}
                    </Button>
                </form>
            </AuthShell>
        </>
    );
}
