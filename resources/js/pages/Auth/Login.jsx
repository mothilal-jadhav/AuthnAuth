import { Head, useForm } from '@inertiajs/react';
import Alert from '../../components/Alert';
import AuthShell from '../../components/AuthShell';
import Button from '../../components/Button';
import Field from '../../components/Field';

export default function Login() {
    const form = useForm({
        email: '',
        password: '',
    });

    const errors = Object.values(form.errors);

    function submit(e) {
        e.preventDefault();

        // Like the Blade form's onlyInput('email'): keep the email on a
        // failed attempt, never the password.
        form.post('/login', { onError: () => form.reset('password') });
    }

    return (
        <>
            <Head title="Login" />

            <AuthShell title="Good to see you again">
                {errors.length > 0 && (
                    <Alert type="error" className="mb-6">
                        <ul className="list-disc space-y-1 pl-4">
                            {errors.map((error) => (
                                <li key={error}>{error}</li>
                            ))}
                        </ul>
                    </Alert>
                )}

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

                        {/* Plain <a>, not Inertia's <Link>: this page is still Blade. */}
                        <a href="/forgot-password" className="self-end text-sm font-medium text-brand-600 hover:text-brand-700">
                            Forgot your password?
                        </a>
                    </div>

                    <Button size="lg" className="w-full" disabled={form.processing}>
                        {form.processing ? 'Signing in…' : 'Sign in'}
                    </Button>
                </form>
            </AuthShell>
        </>
    );
}
