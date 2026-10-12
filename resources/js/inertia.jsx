import { createInertiaApp } from '@inertiajs/react';

// Entry point for pages already migrated to React. Pages that are still
// Blade keep loading resources/js/app.js instead.
const pages = import.meta.glob('./pages/**/*.jsx');

// SecurityHeaders puts the per-request CSP nonce on this meta tag. Inertia
// stamps it on the <style> elements it injects (progress bar, error dialog),
// which the strict style-src would otherwise block.
const nonce = document.querySelector('meta[property="csp-nonce"]')?.nonce || undefined;

createInertiaApp({
    resolve: (name) => {
        const page = pages[`./pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page().then((module) => module.default);
    },
    title: (title) => (title ? `${title} - AuthnAuth` : 'AuthnAuth'),
    nonce,
    progress: { color: '#059669' },
});
