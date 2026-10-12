// fetch() wrapper for React pages calling /api/v1/* with the session cookie
// (Sanctum SPA auth — statefulApi() in bootstrap/app.php). No bearer token
// ever lives in JS. Writes are CSRF-checked: browsers send
// Sec-Fetch-Site: same-origin themselves, and the X-XSRF-TOKEN header below
// is the fallback for any that don't.

export class ApiError extends Error {
    constructor(status, message) {
        super(message);
        this.status = status;
    }
}

function xsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}

export async function api(path, { method = 'GET', query, body } = {}) {
    const url = new URL(`/api/v1${path}`, window.location.origin);

    Object.entries(query ?? {}).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            url.searchParams.set(key, value);
        }
    });

    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const token = xsrfToken();

    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        headers,
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    // Session gone, or the account was forced to change its password after
    // this page loaded (EnsurePasswordIsCurrent): both need a full page load
    // into the Blade flow, not an error message.
    if (response.status === 401) {
        window.location.assign('/login');
    } else if (response.status === 423) {
        window.location.assign('/password/change');
    }

    if (response.status === 204) {
        return null;
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = response.status === 419
            ? 'Your session expired. Reload the page and try again.'
            : data.message || 'Something went wrong. Please try again.';

        throw new ApiError(response.status, message);
    }

    return data;
}
