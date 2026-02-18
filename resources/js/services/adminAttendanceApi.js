import axios from 'axios';

const fetchOverview = async (endpointUrl, params = {}, signal) => {
    if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
        throw new Error('Attendance overview endpoint is missing.');
    }

    const { data } = await axios.get(endpointUrl, {
        signal,
        params,
    });
    return data;
};

/**
 * Convenience wrapper used by the UI.
 *
 * @param {string} endpointUrl
 * @param {Record<string, string>} params
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
 * },
 * departments: Array<{name: string, present: number, total: number, percentage: number}>
 * }>}
 */
export const fetchAdminAttendanceOverview = async (endpointUrl, params = {}, signal) => {
    return fetchOverview(endpointUrl, params, signal);
};
