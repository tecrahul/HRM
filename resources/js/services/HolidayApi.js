import axios from 'axios';

export class HolidayApi {
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

    async request(promise) {
        try {
            const response = await promise;
            return response.data;
        } catch (error) {
            throw this.parseError(error);
        }
    }

    buildHolidayUrl(template, holidayId) {
        if (!template) {
            return '';
        }

        return template.replace('__HOLIDAY__', String(holidayId));
    }

    getHolidays(params = {}) {
        return this.request(this.client.get(this.routes.list, { params }));
    }

    createHoliday(payload) {
        return this.request(this.client.post(this.routes.create, payload));
    }

    updateHoliday(holidayId, payload) {
        const url = this.buildHolidayUrl(this.routes.updateTemplate, holidayId);
        if (!url) {
            throw {
                status: 400,
                message: 'Update route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.put(url, payload));
    }

    deleteHoliday(holidayId) {
        const url = this.buildHolidayUrl(this.routes.deleteTemplate, holidayId);
        if (!url) {
            throw {
                status: 400,
                message: 'Delete route is not configured.',
                errors: {},
            };
        }

        return this.request(this.client.delete(url));
    }
}
