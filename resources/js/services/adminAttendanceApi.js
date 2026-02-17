import axios from 'axios';

const fetchOverview = async (endpointUrl, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Attendance overview endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, { signal });
    return data;
};

/**
 * API function for attendance status summary.
 *
 * @param {string} endpointUrl
 * @param {AbortSignal|undefined} signal
 * @returns {Promise<{
 * generatedAt: string,
 * totals: {
 *  present: number,
 *  absent: number,
 *  late: number,
 *  onLeave: number,
 *  workFromHome: number,
 *  notMarked: number,
 *  totalEmployees: number
 * }
 * }>}
 */
export const fetchAdminAttendanceStatusSummary = async (endpointUrl, signal) => {
    const payload = await fetchOverview(endpointUrl, signal);
    return {
        generatedAt: payload.generatedAt,
        totals: payload.totals,
    };
};

/**
 * API function for department attendance percentages.
 *
 * @param {string} endpointUrl
 * @param {AbortSignal|undefined} signal
 * @returns {Promise<{
 * generatedAt: string,
 * departments: Array<{name: string, present: number, total: number, percentage: number}>
 * }>}
 */
export const fetchAdminDepartmentAttendance = async (endpointUrl, signal) => {
    const payload = await fetchOverview(endpointUrl, signal);
    return {
        generatedAt: payload.generatedAt,
        departments: payload.departments,
    };
};

/**
 * Convenience wrapper used by the UI.
 *
 * @returns {Promise<{
 * generatedAt: string,
 * totals: {
 *  present: number,
 *  absent: number,
 *  late: number,
 *  onLeave: number,
 *  workFromHome: number,
 *  notMarked: number,
 *  totalEmployees: number
 * },
 * departments: Array<{name: string, present: number, total: number, percentage: number}>
 * }>}
 */
export const fetchAdminAttendanceOverview = async (endpointUrl, signal) => {
    const [summary, departmentBreakdown] = await Promise.all([
        fetchAdminAttendanceStatusSummary(endpointUrl, signal),
        fetchAdminDepartmentAttendance(endpointUrl, signal),
    ]);

    return {
        generatedAt: summary.generatedAt || departmentBreakdown.generatedAt,
        totals: summary.totals,
        departments: departmentBreakdown.departments,
    };
};
