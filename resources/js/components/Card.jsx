export default function Card({ padded = true, children }) {
    return <div className={`rounded-lg border border-line bg-paper shadow-sm ${padded ? 'p-6 sm:p-8' : ''}`}>{children}</div>;
}
