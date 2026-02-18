import React, { useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';

const MODAL_ROOT_ID = 'app-modal-root';
let reactModalOpenCount = 0;

const ensureModalRoot = () => {
    if (typeof document === 'undefined') {
        return null;
    }

    let root = document.getElementById(MODAL_ROOT_ID);
    if (!root) {
        root = document.createElement('div');
        root.setAttribute('id', MODAL_ROOT_ID);
        document.body.appendChild(root);
    }

    return root;
};

const syncReactModalScrollLock = () => {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.classList.toggle('app-react-modal-open', reactModalOpenCount > 0);
};

export function AppModalPortal({
    open,
    children,
    onBackdropClick,
    onEscapeKeyDown,
    overlayClassName = 'app-modal-overlay',
    overlayStyle,
}) {
    const [root, setRoot] = useState(null);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        setRoot(ensureModalRoot());

        reactModalOpenCount += 1;
        syncReactModalScrollLock();

        const escapeHandler = (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const handler = typeof onEscapeKeyDown === 'function'
                ? onEscapeKeyDown
                : onBackdropClick;

            if (typeof handler === 'function') {
                handler();
            }
        };

        document.addEventListener('keydown', escapeHandler);

        return () => {
            document.removeEventListener('keydown', escapeHandler);
            reactModalOpenCount = Math.max(0, reactModalOpenCount - 1);
            syncReactModalScrollLock();
        };
    }, [onBackdropClick, onEscapeKeyDown, open]);

    const backdropProps = useMemo(() => ({
        className: overlayClassName,
        style: overlayStyle,
        role: 'presentation',
        'data-app-modal-overlay': 'true',
        onMouseDown: (event) => {
            if (event.target === event.currentTarget && typeof onBackdropClick === 'function') {
                onBackdropClick();
            }
        },
    }), [onBackdropClick, overlayClassName, overlayStyle]);

    if (!open || !root) {
        return null;
    }

    return createPortal(
        <div {...backdropProps}>
            {children}
        </div>,
        root,
    );
}
