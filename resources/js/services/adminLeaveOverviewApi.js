import axios from 'axios';

/**
 * @param {string} endpointUrl
 * @param {AbortSignal|undefined} signal
 * @returns {Promise<{
 * generatedAt: string,
 * period: { monthStart: string, monthEnd: string, today: string },
 * metrics: { pendingApprovals: number, approvedLeaves: number, employeesOnLeaveToday: number },
 * leaveTypeBreakdown: { sick: number, casual: number, paid: number },
 * actions: {
 *  pendingApprovalsUrl: string,
 *  approvedLeavesUrl: string,
 *  sickLeavesUrl: string,
 *  casualLeavesUrl: string,
 *  paidLeavesUrl: string,
 *  employeesOnLeaveTodayUrl: string
 * }
 * }>}
 */
export const fetchAdminLeaveOverview = async (endpointUrl, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Leave overview endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, { signal });
    return data;
};

