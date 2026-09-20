import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.PUBLIC_API_URL || 'http://localhost:8000',
  withCredentials: false,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json',
  },
});

const TOKEN_KEY = 'kinema_token';

export function getToken() {
  if (typeof localStorage === 'undefined') return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
  if (typeof localStorage === 'undefined') return;
  if (token) localStorage.setItem(TOKEN_KEY, token);
  else localStorage.removeItem(TOKEN_KEY);
}

// Inyecta el Bearer token en cada petición si existe
api.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Si el backend devuelve 401, limpiamos el token local
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      setToken(null);
    }
    return Promise.reject(error);
  }
);

export default api;
