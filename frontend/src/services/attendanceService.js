import apiClient from './apiClient';

export const attendanceService = {
  periods: {
    list: () => apiClient.get('/periods').then((r) => r.data),
    create: (data) => apiClient.post('/periods', data).then((r) => r.data),
    show: (id) => apiClient.get(`/periods/${id}`).then((r) => r.data),
    sheets: (periodId) => apiClient.get(`/periods/${periodId}/sheets`).then((r) => r.data),
    createSheet: (periodId, data) => apiClient.post(`/periods/${periodId}/sheets`, data).then((r) => r.data),
  },
  sheets: {
    show: (id) => apiClient.get(`/sheets/${id}`).then((r) => r.data),
    generate: (id) => apiClient.post(`/sheets/${id}/generate`).then((r) => r.data),
    generateStatus: (id) => apiClient.get(`/sheets/${id}/generate-status`).then((r) => r.data),
    finalize: (id) => apiClient.post(`/sheets/${id}/finalize`).then((r) => r.data),
    reopen: (id) => apiClient.post(`/sheets/${id}/reopen`).then((r) => r.data),
    grid: (id) => apiClient.get(`/sheets/${id}/grid`).then((r) => r.data),
  },
  cells: {
    show: (id) => apiClient.get(`/cells/${id}`).then((r) => r.data),
    update: (id, data) => apiClient.put(`/cells/${id}`, data).then((r) => r.data),
    trace: (id) => apiClient.get(`/cells/${id}/trace`).then((r) => r.data),
    bulkUpdate: (sheetId, updates) => apiClient.post(`/sheets/${sheetId}/cells/bulk-update`, { updates }).then((r) => r.data),
  },
};
