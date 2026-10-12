import Card from './Card';

export default function AuthShell({ title, children }) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-paper-alt px-4 py-12">
            <div className="w-full max-w-sm">
                <div className="mb-8 text-center">
                    <h1 className="font-display text-2xl font-bold text-brand-600">AuthnAuth</h1>
                    {title && <p className="mt-1 text-sm text-ink-muted">{title}</p>}
                </div>

                <Card>{children}</Card>
            </div>
        </div>
    );
}
