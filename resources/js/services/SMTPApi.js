import axios from 'axios';

export class SMTPApi {
    constructor({ routes = {}, csrfToken = '' } = {}) {
        this.routes = routes;
        this.csrfHeader = csrfToken ? { headers: { 'X-CSRF-TOKEN': csrfToken } } : undefined;
    }

    async activateSystem() {
        const url = this.routes.useSystem;
        if (!url) throw new Error('Missing route: useSystem');
        const { data } = await axios.post(url, {}, this.csrfHeader);
        return data;
    }

    async saveCustom(payload) {
        const url = this.routes.saveCustom;
        if (!url) throw new Error('Missing route: saveCustom');
        const { data } = await axios.post(url, payload, this.csrfHeader);
        return data;
    }

    async sendTestEmail(recipient) {
        const url = this.routes.testEmail;
        if (!url) throw new Error('Missing route: testEmail');
        const { data } = await axios.post(url, { recipient }, this.csrfHeader);
        return data;
    }
}

