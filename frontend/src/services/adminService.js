import apiClient from './apiClient';

export const adminService = {
  sites: {
    list: () => apiClient.get('/sites').then((r) => r.data),
    create: (data) => apiClient.post('/sites', data).then((r) => r.data),
    update: (id, data) => apiClient.put(`/sites/${id}`, data).then((r) => r.data),
    remove: (id) => apiClient.delete(`/sites/${id}`),
  },
  matrixRules: {
    list: () => apiClient.get('/matrix-rules').then((r) => r.data),
    grid: () => apiClient.get('/matrix-rules/grid').then((r) => r.data),
    create: (data) => apiClient.post('/matrix-rules', data).then((r) => r.data),
    update: (id, data) => apiClient.put(`/matrix-rules/${id}`, data).then((r) => r.data),
    remove: (id) => apiClient.delete(`/matrix-rules/${id}`),
  },
  siteDaytypeCodes: {
    list: () => apiClient.get('/site-daytype-codes').then((r) => r.data),
    create: (data) => apiClient.post('/site-daytype-codes', data).then((r) => r.data),
    update: (id, data) => apiClient.put(`/site-daytype-codes/${id}`, data).then((r) => r.data),
    remove: (id) => apiClient.delete(`/site-daytype-codes/${id}`),
  },
  holidays: {
    list: (year) => apiClient.get('/holiday-calendars', { params: { year } }).then((r) => r.data),
    create: (data) => apiClient.post('/holiday-calendars', data).then((r) => r.data),
    update: (id, data) => apiClient.put(`/holiday-calendars/${id}`, data).then((r) => r.data),
    remove: (id) => apiClient.delete(`/holiday-calendars/${id}`),
  },
  reportTemplates: {
    list: () => apiClient.get('/report-templates').then((r) => r.data),
    create: (data) => apiClient.post('/report-templates', data).then((r) => r.data),
    update: (id, data) => apiClient.put(`/report-templates/${id}`, data).then((r) => r.data),
    remove: (id) => apiClient.delete(`/report-templates/${id}`),
  },
};

export async function getDashboardSummary() {
  const { data } = await apiClient.get('/dashboard/summary');
  return data;
}
