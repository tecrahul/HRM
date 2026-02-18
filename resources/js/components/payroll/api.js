import axios from 'axios';

const cleanParams = (params = {}) => {
    const entries = Object.entries(params).filter(([, value]) => {
        if (value === null || value === undefined) {
            return false;
        }

        if (typeof value === 'string') {
            return value.trim() !== '';
        }

        return true;
    });

    return Object.fromEntries(entries);
};

const get = async (url, params = {}) => {
    const { data } = await axios.get(url, {
        params: cleanParams(params),
    });

    return data;
};

const post = async (url, payload = {}, config = {}) => {
    const { data } = await axios.post(url, payload, config);

    return data;
};

export const payrollApi = {
    getBranches: (url) => get(url),
    getDepartments: (url, params) => get(url, params),
    getDashboardSummary: (url, params) => get(url, params),
    getDashboardAlerts: (url, params) => get(url, params),
    getDashboardActivity: (url, params) => get(url, params),
    getSalaryStructures: (url, params) => get(url, params),
    getHistory: (url, params) => get(url, params),
    getWorkflowOverview: (url, params) => get(url, params),
    previewWorkflow: (url, payload, csrfToken) => post(url, payload, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    }),
    generateWorkflow: (url, payload, csrfToken) => post(url, payload, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    }),
    approveWorkflow: (url, payload, csrfToken) => post(url, payload, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    }),
    payWorkflow: (url, payload, csrfToken) => post(url, payload, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    }),
    unlockWorkflow: (url, payload, csrfToken) => post(url, payload, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    }),
    upsertStructure: (url, payload, csrfToken) => axios.put(url, payload, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    }).then(({ data }) => data),
    getStructureHistory: (url) => get(url),
};

export const toQueryObject = (filters = {}) => ({
    branch_id: filters.branchId || '',
    department_id: filters.departmentId || '',
    employee_id: filters.employeeId || '',
    payroll_month: filters.payrollMonth || '',
    q: filters.search || '',
    status: filters.status || '',
    page: filters.page || 1,
    per_page: filters.perPage || undefined,
});
