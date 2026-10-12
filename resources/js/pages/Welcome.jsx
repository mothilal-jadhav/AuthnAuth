import { Head, Link } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />

            <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-brand-50 to-paper-alt px-4 text-center">
                <h1 className="font-display text-4xl font-bold text-brand-600 sm:text-5xl">AuthnAuth</h1>

                <p className="mt-3 max-w-md text-base text-ink-muted">
                    Authentication &amp; authorization for your team, without noise.
                </p>

                <div className="mt-8">
                    <Link
                        href="/login"
                        className="inline-flex items-center justify-center gap-2 rounded-md bg-brand-solid px-5 py-2.5 text-base font-medium text-white shadow-sm transition hover:bg-brand-solid-hover"
                    >
                        Login
                    </Link>
                </div>
            </div>
        </>
    );
}
