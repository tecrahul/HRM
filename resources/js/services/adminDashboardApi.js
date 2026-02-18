import axios from 'axios';

/**
 * @param {string} endpointUrl
 * @param {AbortSignal|undefined} signal
 * @returns {Promise<{summary: {
 *   totalActiveEmployees: number,
 *   presentToday: { count: number, percentage: number },
 *   employeesOnLeave: number,
 *   pendingApprovals: { total: number, leave: number, other: number },
 *   payrollStatus: { completed: number, pending: number, state: string },
 *   newJoinersThisMonth: number,
 *   exitsThisMonth: number
 * }, generatedAt: string }>}
 */
export const fetchAdminDashboardSummary = async (endpointUrl, params = {}, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Dashboard summary endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, {
        signal,
        params,
    });
    return data;
};

export const fetchBranchOptions = async (endpointUrl, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Branch endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, { signal });
    return Array.isArray(data?.branches) ? data.branches : [];
};

export const fetchDepartmentOptions = async (endpointUrl, params = {}, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Department endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, {
        signal,
        params,
    });

    return Array.isArray(data?.departments) ? data.departments : [];
};

export const buildDashboardSummaryQuery = ({ branchId = '', departmentId = '' } = {}) => {
    const query = {};

    if (String(branchId).trim() !== '') {
        query.branch_id = String(branchId).trim();
    }

    if (String(departmentId).trim() !== '') {
        query.department_id = String(departmentId).trim();
    }

    return query;
};

export const buildDepartmentQuery = ({ branchId = '' } = {}) => {
    const query = {};

    if (String(branchId).trim() !== '') {
        query.branch_id = String(branchId).trim();
    }

    return query;
};
