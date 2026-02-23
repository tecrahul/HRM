import React from 'react';

export function PermissionGuard({ allowed, fallback = null, children }) {
    if (!allowed) {
        return fallback;
    }

    return children;
}
