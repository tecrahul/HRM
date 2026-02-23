import axios from 'axios';

export class AttendanceApi {
    constructor({ routes = {}, csrfToken = '' } = {}) {
        this.routes = routes;
        this.client = axios.create({
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
        });
    }

    parseError(error) {
        if (error?.response) {
            return {
                status: error.response.status,
                message: error.response.data?.message || 'Request failed.',
                errors: error.response.data?.errors || {},
            };
        }

        if (error?.request) {
            return {
                status: 0,
                message: 'Network error. Please check your connection and try again.',
                errors: {},
            };
        }

        return {
            status: 0,
            message: error?.message || 'Unexpected error occurred.',
            errors: {},
        };
    }

    async request(requestPromise) {
        try {
            const response = await requestPromise;
            return response.data;
        } catch (error) {
            throw this.parseError(error);
        }
    }

    buildAttendanceUrl(template, attendanceId) {
        if (!template) {
            return '';
        }

        return template.replace('__ATTENDANCE__', String(attendanceId));
    }

    getAttendance(params = {}) {
        return this.request(this.client.get(this.routes.list, { params }));
    }

    createAttendance(payload) {
        return this.request(this.client.post(this.routes.store, payload));
    }

    updateAttendance(attendanceId, payload) {
        const url = this.buildAttendanceUrl(this.routes.updateTemplate, attendanceId);

        if (!url) {
            throw {
                status: 400,
                message: 'Update route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.put(url, payload));
    }

    deleteAttendance(attendanceId) {
        const url = this.buildAttendanceUrl(this.routes.deleteTemplate, attendanceId);

        if (!url) {
            throw {
                status: 400,
                message: 'Delete route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.delete(url));
    }

    approveAttendance(attendanceId, note = '') {
        const url = this.buildAttendanceUrl(this.routes.approveTemplate, attendanceId);

        if (!url) {
            throw {
                status: 400,
                message: 'Approve route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.put(url, { note }));
    }

    rejectAttendance(attendanceId, reason) {
        const url = this.buildAttendanceUrl(this.routes.rejectTemplate, attendanceId);

        if (!url) {
            throw {
                status: 400,
                message: 'Reject route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.put(url, { reason }));
    }

    requestCorrection(attendanceId, payload) {
        const url = this.buildAttendanceUrl(this.routes.correctionTemplate, attendanceId);

        if (!url) {
            throw {
                status: 400,
                message: 'Correction route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.post(url, payload));
    }

    lockMonth(month) {
        return this.request(this.client.post(this.routes.lockMonth, { month }));
    }

    unlockMonth(month, reason) {
        return this.request(this.client.post(this.routes.unlockMonth, { month, reason }));
    }

    checkIn(notes = '') {
        if (!this.routes.checkIn) {
            throw {
                status: 400,
                message: 'Check-in route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.post(this.routes.checkIn, { notes }));
    }

    checkOut(notes = '') {
        if (!this.routes.checkOut) {
            throw {
                status: 400,
                message: 'Check-out route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.post(this.routes.checkOut, { notes }));
    }

    searchEmployees(query, filters = {}) {
        if (!this.routes.employeeSearch || query.trim().length < 2) {
            return Promise.resolve([]);
        }

        const params = {
            q: query,
        };

        if (filters.department && String(filters.department).trim() !== '') {
            params.department = String(filters.department).trim();
        }

        if (filters.branch && String(filters.branch).trim() !== '') {
            params.branch = String(filters.branch).trim();
        }

        return this.request(this.client.get(this.routes.employeeSearch, {
            params,
        }));
    }
}
