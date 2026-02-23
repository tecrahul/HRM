export const ALLOWED_ATTACHMENT_MIME = [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

export const ALLOWED_ATTACHMENT_EXT = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx'];

const toDate = (value) => {
    if (!value) {
        return null;
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const fileExtension = (filename = '') => {
    const parts = filename.toLowerCase().split('.');
    return parts.length > 1 ? parts.pop() : '';
};

export function calculateTotalDays({ startDate, endDate, dayType }) {
    const start = toDate(startDate);
    const end = toDate(endDate);
    if (!start || !end) {
        return 0;
    }

    if (dayType === 'half_day') {
        return start.toDateString() === end.toDateString() ? 0.5 : 0;
    }

    if (start > end) {
        return 0;
    }

    const msPerDay = 1000 * 60 * 60 * 24;
    const diff = Math.round((end.getTime() - start.getTime()) / msPerDay) + 1;
    return diff > 0 ? diff : 0;
}

export function validateLeaveForm(values, context = {}) {
    const errors = {};
    const isManagement = Boolean(context.isManagement);
    const remainingDays = Number(context.remainingDays || 0);

    if (isManagement && !values.employeeId) {
        errors.employeeId = 'Employee is required.';
    }

    if (!values.leaveType) {
        errors.leaveType = 'Leave type is required.';
    }

    if (!values.dayType) {
        errors.dayType = 'Day type is required.';
    }

    if (!values.startDate) {
        errors.startDate = 'From date is required.';
    }

    if (!values.endDate) {
        errors.endDate = 'To date is required.';
    }

    const start = toDate(values.startDate);
    const end = toDate(values.endDate);

    if (start && end && start > end) {
        errors.endDate = 'To date must be on or after from date.';
    }

    if (values.dayType === 'half_day') {
        if (!values.halfDaySession) {
            errors.halfDaySession = 'Please choose first half or second half.';
        }

        if (start && end && start.toDateString() !== end.toDateString()) {
            errors.endDate = 'Half day leave must be for a single date.';
        }
    }

    const totalDays = calculateTotalDays(values);
    if (totalDays <= 0) {
        errors.totalDays = 'Leave duration must be greater than zero.';
    }

    if (!isManagement && values.leaveType !== 'unpaid' && totalDays > remainingDays) {
        errors.totalDays = `Requested days exceed available balance (${remainingDays.toFixed(1)} days).`;
    }

    if (!values.reason || values.reason.trim() === '') {
        errors.reason = 'Reason is required.';
    }

    if (values.attachment) {
        const mime = values.attachment.type || '';
        const ext = fileExtension(values.attachment.name || '');
        const mimeAllowed = mime === '' || ALLOWED_ATTACHMENT_MIME.includes(mime);
        const extAllowed = ext === '' || ALLOWED_ATTACHMENT_EXT.includes(ext);

        if (!mimeAllowed || !extAllowed) {
            errors.attachment = 'Attachment must be PDF, PNG, JPG, DOC, or DOCX.';
        } else if (values.attachment.size > 5 * 1024 * 1024) {
            errors.attachment = 'Attachment size must be 5MB or less.';
        }
    }

    if (isManagement && values.status === 'rejected' && (!values.assignNote || values.assignNote.trim() === '')) {
        errors.assignNote = 'Note is required when creating rejected leave.';
    }

    return errors;
}
