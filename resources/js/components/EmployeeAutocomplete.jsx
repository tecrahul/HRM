import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

const SEARCH_DEBOUNCE_MS = 300;
const DEFAULT_PLACEHOLDER = 'Search employee by name or email...';

const toString = (value) => String(value ?? '');
const toNumber = (value) => Number(value ?? 0);

const employeeLabel = (employee) => {
    if (!employee) {
        return '';
    }

    const name = toString(employee.name).trim();
    const employeeCode = toString(employee.employee_code).trim();
    const email = toString(employee.email).trim();
    if (employeeCode && name && email) {
        return `${employeeCode} • ${name} (${email})`;
    }
    if (employeeCode && name) {
        return `${employeeCode} • ${name}`;
    }
    if (name && email) {
        return `${name} (${email})`;
    }

    return name || email;
};

const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const renderHighlighted = (text, query) => {
    const rawText = toString(text);
    const trimmed = toString(query).trim();
    if (!trimmed) {
        return rawText;
    }

    const matcher = new RegExp(`(${escapeRegex(trimmed)})`, 'ig');
    const parts = rawText.split(matcher);

    const target = trimmed.toLowerCase();

    return parts.map((part, index) => {
        if (part.toLowerCase() === target) {
            return (
                <mark key={`hl-${index}`} className="rounded-sm px-0.5" style={{ background: 'rgb(59 130 246 / 0.2)', color: 'inherit' }}>
                    {part}
                </mark>
            );
        }

        return <span key={`txt-${index}`}>{part}</span>;
    });
};

export function EmployeeAutocomplete({
    apiUrl,
    name = '',
    inputId = '',
    placeholder = DEFAULT_PLACEHOLDER,
    disabled = false,
    required = false,
    initialEmployee = null,
    selectedEmployee = null,
    onSelect = null,
    showDepartment = true,
    allowClear = true,
    searchParams = null,
}) {
    const [query, setQuery] = useState(() => employeeLabel(selectedEmployee || initialEmployee));
    const [selected, setSelected] = useState(() => selectedEmployee || initialEmployee);
    const [open, setOpen] = useState(false);
    const [results, setResults] = useState([]);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const blurTimeoutRef = useRef(0);
    const requestTokenRef = useRef(0);
    const mountedRef = useRef(true);
    const inputRef = useRef(null);

    const listId = useMemo(() => `${inputId || 'employee_autocomplete'}_listbox`, [inputId]);
    const serializedSearchParams = useMemo(() => {
        if (!searchParams || typeof searchParams !== 'object') {
            return '{}';
        }

        return JSON.stringify(searchParams);
    }, [searchParams]);

    useEffect(() => {
        mountedRef.current = true;
        return () => {
            mountedRef.current = false;
            if (blurTimeoutRef.current) {
                window.clearTimeout(blurTimeoutRef.current);
            }
        };
    }, []);

    useEffect(() => {
        if (!selectedEmployee) {
            return;
        }

        setSelected(selectedEmployee);
        setQuery(employeeLabel(selectedEmployee));
    }, [selectedEmployee?.id]);

    useEffect(() => {
        if (disabled) {
            setOpen(false);
            return;
        }

        const term = query.trim();
        if (term === '' || (selected && employeeLabel(selected).toLowerCase() === term.toLowerCase())) {
            setLoading(false);
            setResults([]);
            setError('');
            return;
        }

        const debounceHandle = window.setTimeout(async () => {
            const requestToken = requestTokenRef.current + 1;
            requestTokenRef.current = requestToken;
            setLoading(true);
            setError('');

            try {
                const parsedSearchParams = JSON.parse(serializedSearchParams);
                const { data } = await axios.get(apiUrl, {
                    params: {
                        q: term,
                        ...(parsedSearchParams && typeof parsedSearchParams === 'object' ? parsedSearchParams : {}),
                    },
                });

                if (!mountedRef.current || requestTokenRef.current !== requestToken) {
                    return;
                }

                setResults(Array.isArray(data) ? data : []);
                setActiveIndex(-1);
            } catch (_error) {
                if (!mountedRef.current || requestTokenRef.current !== requestToken) {
                    return;
                }

                setResults([]);
                setError('Unable to fetch employees. Try again.');
            } finally {
                if (mountedRef.current && requestTokenRef.current === requestToken) {
                    setLoading(false);
                }
            }
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(debounceHandle);
    }, [apiUrl, disabled, query, selected?.id, serializedSearchParams]);

    const commitSelect = (employee) => {
        setSelected(employee);
        setQuery(employeeLabel(employee));
        setResults([]);
        setOpen(false);
        setActiveIndex(-1);
        setError('');
        if (inputRef.current instanceof HTMLInputElement) {
            inputRef.current.setCustomValidity('');
        }

        if (typeof onSelect === 'function') {
            onSelect(employee);
        }
    };

    const clearSelection = () => {
        setSelected(null);
        setQuery('');
        setResults([]);
        setOpen(false);
        setActiveIndex(-1);
        setError('');
        if (inputRef.current instanceof HTMLInputElement) {
            inputRef.current.setCustomValidity('');
        }
        if (typeof onSelect === 'function') {
            onSelect(null);
        }
    };

    const applyValidity = () => {
        if (!(inputRef.current instanceof HTMLInputElement)) {
            return;
        }

        if (disabled || selected) {
            inputRef.current.setCustomValidity('');
            return;
        }

        const trimmed = query.trim();
        if (trimmed === '' && required) {
            inputRef.current.setCustomValidity('Employee is required.');
            return;
        }

        if (trimmed !== '') {
            inputRef.current.setCustomValidity('Select a valid employee from suggestions.');
            return;
        }

        inputRef.current.setCustomValidity('');
    };

    const onInputKeyDown = (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
            setActiveIndex((prev) => Math.min(results.length - 1, prev + 1));
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setOpen(true);
            setActiveIndex((prev) => Math.max(0, prev - 1));
            return;
        }

        if (event.key === 'Enter') {
            if (open && activeIndex >= 0 && activeIndex < results.length) {
                event.preventDefault();
                commitSelect(results[activeIndex]);
            }
            return;
        }

        if (event.key === 'Escape') {
            setOpen(false);
            setActiveIndex(-1);
        }
    };

    return (
        <div className="relative">
            {name ? (
                <input type="hidden" name={name} value={selected ? toString(selected.id) : ''} />
            ) : null}

            <div
                role="combobox"
                aria-expanded={open}
                aria-controls={listId}
                aria-haspopup="listbox"
            >
                <input
                    ref={inputRef}
                    id={inputId || undefined}
                    type="text"
                    placeholder={placeholder}
                    value={query}
                    onFocus={() => {
                        if (!disabled) {
                            setOpen(true);
                        }
                    }}
                    onChange={(event) => {
                        const nextValue = event.target.value;
                        setQuery(nextValue);
                        if (selected && employeeLabel(selected) !== nextValue) {
                            setSelected(null);
                            if (typeof onSelect === 'function') {
                                onSelect(null);
                            }
                        }
                        if (nextValue.trim() === '') {
                            event.target.setCustomValidity(required ? 'Employee is required.' : '');
                        } else if (selected && employeeLabel(selected) === nextValue) {
                            event.target.setCustomValidity('');
                        } else {
                            event.target.setCustomValidity('Select a valid employee from suggestions.');
                        }
                        setOpen(true);
                    }}
                    onBlur={() => {
                        applyValidity();
                        blurTimeoutRef.current = window.setTimeout(() => {
                            setOpen(false);
                            setActiveIndex(-1);
                        }, 120);
                    }}
                    onInvalid={applyValidity}
                    onKeyDown={onInputKeyDown}
                    className="ui-input pr-10"
                    autoComplete="off"
                    aria-autocomplete="list"
                    aria-controls={listId}
                    disabled={disabled}
                    required={required}
                />

                {loading ? (
                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                        <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-500" />
                    </span>
                ) : (allowClear && selected && !disabled) ? (
                    <button
                        type="button"
                        onClick={clearSelection}
                        className="absolute right-2 top-1/2 -translate-y-1/2 rounded px-1 text-sm leading-none"
                        style={{ color: 'var(--hr-text-muted)' }}
                        aria-label="Clear selected employee"
                    >
                        ×
                    </button>
                ) : null}
            </div>

            {open && !disabled ? (
                <div
                    id={listId}
                    role="listbox"
                    className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-xl border p-1 shadow-lg"
                    style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}
                >
                    {error ? (
                        <p className="px-3 py-2 text-sm font-semibold text-red-600">{error}</p>
                    ) : null}

                    {!error && !loading && query.trim() !== '' && results.length === 0 ? (
                        <p className="px-3 py-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>No results found.</p>
                    ) : null}

                    {!error && results.map((employee, index) => (
                        <button
                            key={`employee-opt-${employee.id}`}
                            type="button"
                            role="option"
                            aria-selected={index === activeIndex}
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => commitSelect(employee)}
                            className="w-full rounded-lg px-3 py-2 text-left"
                            style={{
                                background: index === activeIndex ? 'var(--hr-accent-soft)' : 'transparent',
                            }}
                        >
                            <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-main)' }}>
                                {employee.employee_code ? (
                                    <>
                                        <span className="mr-1" style={{ color: 'var(--hr-text-muted)' }}>
                                            {renderHighlighted(employee.employee_code, query)}
                                        </span>
                                        <span aria-hidden="true">•</span>{' '}
                                    </>
                                ) : null}
                                {renderHighlighted(employee.name, query)}
                            </p>
                            <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                                {renderHighlighted(employee.email, query)}
                                {showDepartment && employee.department ? (
                                    <>
                                        {' • '}
                                        {renderHighlighted(employee.department, query)}
                                    </>
                                ) : null}
                            </p>
                        </button>
                    ))}
                </div>
            ) : null}
        </div>
    );
}

export function mountEmployeeAutocompletes() {
    const roots = document.querySelectorAll('[data-employee-autocomplete-root]');
    roots.forEach((root, index) => {
        if (!(root instanceof HTMLElement)) {
            return;
        }

        if (root.dataset.mounted === 'true') {
            return;
        }

        const selectedJson = root.dataset.selected || '';
        let selectedEmployee = null;
        if (selectedJson.trim() !== '') {
            try {
                const parsed = JSON.parse(selectedJson);
                if (parsed && typeof parsed === 'object') {
                    selectedEmployee = {
                        id: toNumber(parsed.id),
                        name: toString(parsed.name),
                        email: toString(parsed.email),
                        department: toString(parsed.department),
                        employee_code: toString(parsed.employee_code),
                    };
                }
            } catch (_error) {
                selectedEmployee = null;
            }
        }

        const reactRoot = createRoot(root);
        reactRoot.render(
            <EmployeeAutocomplete
                apiUrl={root.dataset.apiUrl || '/api/employees/search'}
                name={root.dataset.name || ''}
                inputId={root.dataset.inputId || `employee_autocomplete_${index}`}
                placeholder={root.dataset.placeholder || DEFAULT_PLACEHOLDER}
                disabled={root.dataset.disabled === 'true'}
                required={root.dataset.required === 'true'}
                initialEmployee={selectedEmployee}
                showDepartment={root.dataset.showDepartment !== 'false'}
                allowClear={root.dataset.allowClear !== 'false'}
            />,
        );
        root.dataset.mounted = 'true';
    });
}
