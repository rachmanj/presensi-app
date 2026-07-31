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

export async function changePassword({ current_password, password, password_confirmation }) {
  const { data } = await apiClient.put('/auth/password', {
    current_password,
    password,
    password_confirmation,
  });
  return data;
}
