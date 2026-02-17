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
export const fetchAdminDashboardSummary = async (endpointUrl, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Dashboard summary endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, { signal });
    return data;
};

