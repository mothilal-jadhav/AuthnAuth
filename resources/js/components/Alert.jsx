const styles = {
    success: { bg: 'bg-success-bg', text: 'text-success', border: 'border-success-line', icon: '✓' },
    error: { bg: 'bg-danger-bg', text: 'text-danger', border: 'border-danger-line', icon: '!' },
    warning: { bg: 'bg-warning-bg', text: 'text-warning', border: 'border-warning-line', icon: '!' },
    info: { bg: 'bg-info-bg', text: 'text-info', border: 'border-info-line', icon: 'i' },
};

export default function Alert({ type = 'info', className = '', children }) {
    const style = styles[type];

    return (
        <div
            role="alert"
            className={`flex items-start gap-3 rounded-md border ${style.border} ${style.bg} ${style.text} px-4 py-3 text-sm ${className}`}
        >
            <span className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ${style.border} text-xs font-bold`}>
                {style.icon}
            </span>

            <div className="flex-1">{children}</div>
        </div>
    );
}
