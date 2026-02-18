import React from 'react';
import { createRoot } from 'react-dom/client';
import { buildGreetingText } from '../../utils/greeting';

export function GreetingHeader({ firstName, functionalTitle = '', showWave = true }) {
    return (
        <div>
            <h2 className="mt-2 text-2xl md:text-3xl font-extrabold tracking-tight" style={{ color: 'var(--hr-text-main)' }}>
                {buildGreetingText(firstName)}
                {showWave ? (
                    <span className="ml-2 align-middle" role="img" aria-label="waving hand">
                        👋
                    </span>
                ) : null}
            </h2>
            {functionalTitle ? (
                <p
                    className="mt-2 text-xs font-semibold uppercase tracking-[0.1em]"
                    style={{ color: 'var(--hr-text-muted)' }}
                >
                    {functionalTitle}
                </p>
            ) : null}
        </div>
    );
}

export function mountDashboardGreetings() {
    const roots = document.querySelectorAll('[data-dashboard-greeting-root]');
    if (roots.length === 0) {
        return;
    }

    roots.forEach((node) => {
        const firstName = node.getAttribute('data-first-name') || '';
        const functionalTitle = node.getAttribute('data-functional-title') || '';
        const showWave = node.getAttribute('data-show-wave') !== '0';
        const root = createRoot(node);
        root.render(
            <GreetingHeader
                firstName={firstName}
                functionalTitle={functionalTitle}
                showWave={showWave}
            />
        );
    });
}
