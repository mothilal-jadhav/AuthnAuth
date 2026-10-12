// React counterpart of <x-role-badge>: severity gradient for the hierarchy
// (admin stands out, manager is mid, user is neutral), one hue for every
// functional role.
const VARIANTS = {
    admin: 'bg-danger-bg text-danger border-danger-line',
    manager: 'bg-warning-bg text-warning border-warning-line',
    functional: 'bg-accent-50 text-accent-700 border-accent-100',
    neutral: 'bg-paper-alt text-ink-muted border-line',
};

export default function RoleBadge({ role, className = '' }) {
    const variant = role.type === 'functional' ? VARIANTS.functional : VARIANTS[role.name] ?? VARIANTS.neutral;

    return (
        <span className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ${variant} ${className}`}>
            {role.name.toUpperCase()}
        </span>
    );
}
