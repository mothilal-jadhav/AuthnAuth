import { Link } from '@inertiajs/react';

// React counterpart of <x-page-header>. `back` must be a React page — use a
// plain <a> for links into Blade (full page load).
export default function PageHeader({ eyebrow, title, subtitle, back, backLabel }) {
    return (
        <div className="mb-6">
            {back && (
                <Link href={back} className="text-sm font-medium text-brand-600 hover:text-brand-700">
                    ← {backLabel}
                </Link>
            )}
            {eyebrow && <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-brand-600">{eyebrow}</p>}
            <h1 className="font-display text-2xl font-bold text-ink">{title}</h1>
            {subtitle && <p className="mt-1 text-sm text-ink-muted">{subtitle}</p>}
        </div>
    );
}
