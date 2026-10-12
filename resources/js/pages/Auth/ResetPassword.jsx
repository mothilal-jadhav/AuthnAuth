import { Head, useForm } from '@inertiajs/react';
import AuthShell from '../../components/AuthShell';
import Button from '../../components/Button';
import ErrorSummary from '../../components/ErrorSummary';
import Field from '../../components/Field';

export default function ResetPassword({ token, email }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    function submit(e) {
        e.preventDefault();
        form.post('/reset-password', { onError: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <>
            <Head title="Reset Password" />

            <AuthShell title="Choose a new password">
                <ErrorSummary errors={form.errors} />

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Field
                        name="email"
                        label="Email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        error={form.errors.email}
                        autoComplete="email"
                        required
                    />

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
                        {form.processing ? 'Resetting…' : 'Reset password'}
                    </Button>
                </form>
            </AuthShell>
        </>
    );
}
