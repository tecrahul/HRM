import React from 'react';
import { PermissionGuard } from '../shared/PermissionGuard';
import { ViewToggle } from '../shared/ViewToggle';

export function HolidaysHeader({ canCreate, onCreate, disabledCreate = false, view = 'list', onViewChange }) {
    return (
        <section className="flex items-center justify-between gap-4 flex-wrap">
            <div className="leading-tight">
                <h2 className="text-lg font-extrabold">Holidays</h2>
                <p className="hidden md:block text-xs mt-2" style={{ color: 'var(--hr-text-muted)' }}>
                    Manage yearly holiday calendars across company and branch operations.
                </p>
            </div>
            <div className="flex items-center gap-2">
                <ViewToggle value={view} onChange={onViewChange} />
                <PermissionGuard allowed={canCreate}>
                    <button
                        type="button"
                        className="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed"
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
            </div>
        </section>
    );
}
