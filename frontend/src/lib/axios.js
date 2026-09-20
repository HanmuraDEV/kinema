import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.PUBLIC_API_URL,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    // Auth por Bearer token (ver services/api.js). Sin cookies/CSRF.
    withCredentials: false,
});

const TOKEN_KEY = 'kinema_token';

api.interceptors.request.use((config) => {
    if (typeof localStorage !== 'undefined') {
        const token = localStorage.getItem(TOKEN_KEY);
        if (token) config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
