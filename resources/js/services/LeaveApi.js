import axios from 'axios';

export class LeaveApi {
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

    buildLeaveUrl(template, leaveId) {
        if (!template) {
            return '';
        }

        return template.replace('__LEAVE__', String(leaveId));
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

    getLeaves(params = {}) {
        return this.request(this.client.get(this.routes.list, { params }));
    }

    createLeave(data) {
        return this.request(this.client.post(this.routes.create, data, {
            headers: data instanceof FormData
                ? { 'Content-Type': 'multipart/form-data' }
                : undefined,
        }));
    }

    updateLeave(leaveId, data) {
        const url = this.buildLeaveUrl(this.routes.updateTemplate, leaveId);
        if (!url) {
            throw {
                status: 400,
                message: 'Update route is not configured.',
                errors: {},
            };
        }

        if (data instanceof FormData) {
            data.append('_method', 'PUT');

            return this.request(this.client.post(url, data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            }));
        }

        return this.request(this.client.put(url, data));
    }

    deleteLeave(leaveId) {
        const url = this.buildLeaveUrl(this.routes.cancelTemplate, leaveId);
        if (!url) {
            throw {
                status: 400,
                message: 'Delete route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.delete(url));
    }

    approveLeave(leaveId, reviewNote = '') {
        const url = this.buildLeaveUrl(this.routes.reviewTemplate, leaveId);
        if (!url) {
            throw {
                status: 400,
                message: 'Review route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.put(url, {
            status: 'approved',
            review_note: reviewNote || null,
        }));
    }

    rejectLeave(leaveId, reviewNote) {
        const url = this.buildLeaveUrl(this.routes.reviewTemplate, leaveId);
        if (!url) {
            throw {
                status: 400,
                message: 'Review route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.put(url, {
            status: 'rejected',
            review_note: reviewNote || '',
        }));
    }

    searchEmployees(query) {
        if (!this.routes.employeeSearch) {
            return Promise.resolve([]);
        }

        return this.request(this.client.get(this.routes.employeeSearch, {
            params: {
                q: query,
            },
        }));
    }
}
