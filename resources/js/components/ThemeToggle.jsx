import { useState } from 'react';

// public/js/theme-init.js sets data-theme before first paint (stored choice,
// else the OS preference); this flips it and remembers the choice.
export default function ThemeToggle() {
    const [dark, setDark] = useState(() => document.documentElement.getAttribute('data-theme') === 'dark');

    function toggle() {
        const theme = dark ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', theme);

        try {
            localStorage.setItem('theme', theme);
        } catch {
            // storage unavailable — the toggle still works for this page view
        }

        setDark(!dark);
    }

    return (
        <label className="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full bg-line transition has-[:checked]:bg-brand-solid" title="Toggle dark mode">
            <input type="checkbox" checked={dark} onChange={toggle} className="peer sr-only" aria-label="Dark mode" />
            <span className="pointer-events-none absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
        </label>
    );
}
