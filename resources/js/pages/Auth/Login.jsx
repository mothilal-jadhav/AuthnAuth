import { Head, Link, useForm } from '@inertiajs/react';
import Alert from '../../components/Alert';
import AuthShell from '../../components/AuthShell';
import Button from '../../components/Button';
import ErrorSummary from '../../components/ErrorSummary';
import Field from '../../components/Field';

// `status` is the broker's "your password has been reset" message.
export default function Login({ status }) {
    const form = useForm({
        email: '',
        password: '',
    });

    function submit(e) {
        e.preventDefault();

        // Keep the email on a failed attempt, never the password.
        form.post('/login', { onError: () => form.reset('password') });
    }

    return (
        <>
            <Head title="Login" />

            <AuthShell title="Good to see you again">
                {status && (
                    <Alert type="success" className="mb-6">
                        {status}
                    </Alert>
                )}

                <ErrorSummary errors={form.errors} />

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Field
                        name="email"
                        label="Email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        error={form.errors.email}
                        autoComplete="username"
                        required
                        autoFocus
                    />

                    <div className="flex flex-col gap-1.5">
                        <Field
                            name="password"
                            label="Password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            error={form.errors.password}
                            autoComplete="current-password"
                            required
                        />

                        <Link href="/forgot-password" className="self-end text-sm font-medium text-brand-600 hover:text-brand-700">
                            Forgot your password?
                        </Link>
                    </div>

                    <Button size="lg" className="w-full" disabled={form.processing}>
                        {form.processing ? 'Signing in…' : 'Sign in'}
                    </Button>
                </form>
            </AuthShell>
        </>
    );
}
