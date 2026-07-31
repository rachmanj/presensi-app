import apiClient from './apiClient';

export const auditService = {
  list: (params) => apiClient.get('/audit-logs', { params }).then((r) => r.data),
  show: (id) => apiClient.get(`/audit-logs/${id}`).then((r) => r.data),
};
