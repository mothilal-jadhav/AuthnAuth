import { Head, Link, useForm } from '@inertiajs/react';
import Alert from '../../components/Alert';
import AuthShell from '../../components/AuthShell';
import Button from '../../components/Button';
import ErrorSummary from '../../components/ErrorSummary';
import Field from '../../components/Field';

export default function ForgotPassword({ status }) {
    const form = useForm({ email: '' });

    function submit(e) {
        e.preventDefault();
        form.post('/forgot-password');
    }

    return (
        <>
            <Head title="Forgot Password" />

            <AuthShell
                title="Let's get you a new link"
                footer={
                    <>
                        Remember your password?{' '}
                        <Link href="/login" className="font-medium text-brand-600 hover:text-brand-700">
                            Login
                        </Link>
                    </>
                }
            >
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
                        hint="We'll send a reset link to this address."
                        autoComplete="email"
                        required
                        autoFocus
                    />

                    <Button size="lg" className="w-full" disabled={form.processing}>
                        {form.processing ? 'Sending…' : 'Send reset link'}
                    </Button>
                </form>
            </AuthShell>
        </>
    );
}
