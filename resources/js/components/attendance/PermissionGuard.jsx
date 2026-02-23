import React from 'react';

export function PermissionGuard({ allowed, children, fallback = null }) {
    if (!allowed) {
        return fallback;
    }

    return children;
}
