import Alert from './Alert';

// All of a form's validation errors in one box above the fields.
export default function ErrorSummary({ errors, className = 'mb-6' }) {
    const messages = Object.values(errors);

    if (messages.length === 0) {
        return null;
    }

    return (
        <Alert type="error" className={className}>
            <ul className="list-disc space-y-1 pl-4">
                {messages.map((message) => (
                    <li key={message}>{message}</li>
                ))}
            </ul>
        </Alert>
    );
}
