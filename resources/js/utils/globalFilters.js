/**
 * Global Filter Utility
 *
 * Provides React components with access to the app-wide filter state
 * (Financial Year, Branch, Department) that is managed by the header
 * filter panel and persisted in localStorage.
 *
 * Usage in a React component:
 *
 *   import { getGlobalFilters, onFiltersChange } from '../../utils/globalFilters';
 *
 *   // Read once
 *   const filters = getGlobalFilters();
 *
 *   // Subscribe to changes (e.g. inside useEffect)
 *   useEffect(() => {
 *     const unsubscribe = onFiltersChange((newFilters) => {
 *       setFilters(newFilters);
 *     });
 *     return unsubscribe;
 *   }, []);
 */

const FILTER_KEY = 'hrm-global-filters';

/** @returns {{ financial_year: string, branch: string, department: string }} */
function getDefaultFilters() {
    const today = new Date();
    const fyStart = today.getMonth() >= 3 ? today.getFullYear() : today.getFullYear() - 1;
    return {
        financial_year: `${fyStart}-${fyStart + 1}`,
        branch: '',
        department: '',
    };
}

/**
 * Read the currently active global filters from localStorage.
 * Falls back to sensible defaults (current financial year, no branch/dept).
 *
 * @returns {{ financial_year: string, branch: string, department: string }}
 */
export function getGlobalFilters() {
    try {
        const raw = localStorage.getItem(FILTER_KEY);
        if (!raw) return getDefaultFilters();
        return { ...getDefaultFilters(), ...JSON.parse(raw) };
    } catch {
        return getDefaultFilters();
    }
}

/**
 * Programmatically set the global filters (also updates the header UI via
 * the exposed window.hrmGlobalFilters.set helper when available).
 *
 * @param {{ financial_year?: string, branch?: string, department?: string }} filters
 */
export function setGlobalFilters(filters) {
    const next = { ...getDefaultFilters(), ...filters };
    if (window.hrmGlobalFilters?.set) {
        window.hrmGlobalFilters.set(next);
    } else {
        localStorage.setItem(FILTER_KEY, JSON.stringify(next));
        window.dispatchEvent(new CustomEvent('globalFiltersChanged', { detail: next, bubbles: true }));
    }
}

/**
 * Subscribe to global filter changes.
 * The callback receives the new filter object every time Apply is clicked
 * or a filter tag is removed.
 *
 * @param {(filters: { financial_year: string, branch: string, department: string }) => void} callback
 * @returns {() => void} Unsubscribe function — call it in your useEffect cleanup.
 */
export function onFiltersChange(callback) {
    const handler = (e) => callback(e.detail);
    window.addEventListener('globalFiltersChanged', handler);
    return () => window.removeEventListener('globalFiltersChanged', handler);
}

/**
 * Count how many filters are currently active (non-empty).
 *
 * @param {{ financial_year: string, branch: string, department: string }} filters
 * @returns {number}
 */
export function getActiveFilterCount(filters) {
    return [filters.financial_year, filters.branch, filters.department].filter(Boolean).length;
}

/**
 * Build a query-parameter object from the current global filters,
 * suitable for appending to API requests.
 *
 * @param {{ financial_year: string, branch: string, department: string }} [filters]
 * @returns {Record<string, string>}
 */
export function filtersToQueryParams(filters) {
    const f = filters ?? getGlobalFilters();
    const params = {};
    if (f.financial_year) params.financial_year = f.financial_year;
    if (f.branch)         params.branch         = f.branch;
    if (f.department)     params.department     = f.department;
    return params;
}
