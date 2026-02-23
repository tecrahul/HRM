import React from 'react';
import { PermissionGuard } from '../shared/PermissionGuard';

export function HolidaysHeader({ canCreate, onCreate, disabledCreate = false }) {
    return (
        <section className="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 className="text-xl font-extrabold">Holidays</h2>
                <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                    Manage yearly holiday calendars across company and branch operations.
                </p>
            </div>
            <PermissionGuard allowed={canCreate}>
                <button
                    type="button"
                    className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed"
                    style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                    onClick={onCreate}
                    disabled={disabledCreate}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    + Create Holiday
                </button>
            </PermissionGuard>
        </section>
    );
}
