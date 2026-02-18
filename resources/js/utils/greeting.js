const FALLBACK_NAME = 'there';

export function extractFirstName(name) {
    const normalized = String(name ?? '').trim();
    if (normalized.length === 0) {
        return FALLBACK_NAME;
    }

    const [firstName] = normalized.split(/\s+/);
    return firstName || FALLBACK_NAME;
}

export function getGreetingByHour(hour) {
    if (hour >= 5 && hour <= 11) {
        return 'Good morning';
    }

    if (hour >= 12 && hour <= 16) {
        return 'Good afternoon';
    }

    return 'Good evening';
}

export function getTimeBasedGreeting(date = new Date()) {
    return getGreetingByHour(date.getHours());
}

export function buildGreetingText(name, date = new Date()) {
    return `${getTimeBasedGreeting(date)}, ${extractFirstName(name)}`;
}
