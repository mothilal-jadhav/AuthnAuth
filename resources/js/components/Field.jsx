// as="select" renders a <select> around the given <option> children;
// anything else is an <input>.
export default function Field({ name, label, error, hint, type = 'text', as = 'input', className = '', children, ...props }) {
    const inputClasses =
        'block w-full rounded-md border bg-paper px-3 py-2 text-sm text-ink placeholder:text-ink-muted '
        + 'transition focus:outline-none focus:ring-2 focus:ring-brand-500/40 '
        + (error ? 'border-danger focus:border-danger' : 'border-line focus:border-brand-500');

    return (
        <div className="flex flex-col gap-1.5">
            {label && (
                <label htmlFor={name} className="text-sm font-medium text-ink">
                    {label}
                </label>
            )}

            {as === 'select' ? (
                <select id={name} name={name} className={`${inputClasses} ${className}`} {...props}>
                    {children}
                </select>
            ) : (
                <input id={name} name={name} type={type} className={`${inputClasses} ${className}`} {...props} />
            )}

            {hint && !error && <p className="text-xs text-ink-muted">{hint}</p>}

            {error && <p className="text-sm text-danger">{error}</p>}
        </div>
    );
}
