import { usePage } from '@inertiajs/react';
import Button from './Button';

// React counterpart of resources/views/partials/navbar.blade.php. Every link
// is a plain <a> and logout a real form post: those pages are still Blade,
// so leaving this app has to be a full page load, never an Inertia visit.
export default function AppLayout({ children }) {
    const { auth, csrf_token: csrfToken } = usePage().props;
    const user = auth.user;

    return (
        <div className="min-h-screen bg-paper-alt">
            <nav className="flex items-center justify-between border-b border-line bg-paper px-4 py-3 sm:px-6">
                <a href="/dashboard" className="flex flex-col leading-tight">
                    <span className="font-display text-lg font-bold text-brand-600">AuthnAuth</span>
                    <span className="hidden text-xs text-ink-muted sm:block">Authentication &amp; Authorization</span>
                </a>

                <div className="flex items-center gap-4">
                    <a href="/profile" className="-mx-2 flex items-center gap-3 rounded-md px-2 py-1 transition hover:bg-paper-alt" title="My Profile">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display font-semibold text-brand-700">
                            {user.name.charAt(0).toUpperCase()}
                        </div>

                        <div className="hidden leading-tight sm:block">
                            <p className="text-sm font-semibold text-ink">{user.name}</p>
                            <p className="text-xs text-ink-muted">{user.role}</p>
                        </div>
                    </a>

                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <Button variant="ghost" size="sm">Logout</Button>
                    </form>
                </div>
            </nav>

            {children}
        </div>
    );
}
