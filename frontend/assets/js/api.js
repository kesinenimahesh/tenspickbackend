const TENSPICK_API_BASE = window.TENSPICK_API_BASE || 'http://localhost/tenspickk/backend/public';

async function apiRequest(path, options = {}) {
    const cleanPath = `/${String(path).replace(/^\/+/, '')}`;
    const url = `${TENSPICK_API_BASE}${cleanPath}`;

    const config = {
        method: 'GET',
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.body ? {'Content-Type': 'application/json'} : {}),
            ...(options.headers || {})
        }
    };

    const response = await fetch(url, config);
    const data = await response.json().catch(() => ({
        success: false,
        message: 'The server returned an invalid response.'
    }));

    if (!response.ok || !data.success) {
        throw new Error(data.message || `Request failed (${response.status}).`);
    }

    return data;
}
