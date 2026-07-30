import apiClient, { getCsrfCookie } from '../services/apiClient';

export async function login(email, password) {
  await getCsrfCookie();
  const { data } = await apiClient.post('/auth/login', { email, password });
  return data.user;
}

export async function logout() {
  await apiClient.post('/auth/logout');
}

export async function getCurrentUser() {
  const { data } = await apiClient.get('/auth/me');
  return data.user;
}
