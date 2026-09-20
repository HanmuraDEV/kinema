import api, { setToken, getToken } from './api';

/**
 * Auth con tokens Bearer de Sanctum (AuthController).
 * Endpoints reales: POST /api/register, POST /api/login,
 * GET /api/user, POST /api/logout
 */

export async function register(payload) {
  const response = await api.post('/api/register', payload);
  const token = response.data?.access_token;
  if (token) setToken(token);
  return response.data;
}

export async function login(credentials) {
  const response = await api.post('/api/login', credentials);
  const token = response.data?.access_token;
  if (token) setToken(token);
  return response.data;
}

export async function fetchUser() {
  if (!getToken()) throw new Error('Sin token');
  const response = await api.get('/api/user');
  return response.data;
}

export async function logout() {
  try {
    if (getToken()) await api.post('/api/logout');
  } finally {
    setToken(null);
  }
}
