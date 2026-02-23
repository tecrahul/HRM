import React from 'react';

export function QuickInfoGrid({ children, className = '' }) {
    return (
        <section className={`quick-info-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 ${className}`.trim()}>
            {children}
        </section>
    );
}
