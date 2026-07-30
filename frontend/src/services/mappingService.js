import apiClient from './apiClient';

export const mappingService = {
  list: (params) => apiClient.get('/employee-maps', { params }).then((r) => r.data),
  create: (data) => apiClient.post('/employee-maps', data).then((r) => r.data),
  update: (id, data) => apiClient.put(`/employee-maps/${id}`, data).then((r) => r.data),
  remove: (id) => apiClient.delete(`/employee-maps/${id}`),
  bulk: (mappings) => apiClient.post('/employee-maps/bulk', { mappings }).then((r) => r.data),
  unmatched: (params) => apiClient.get('/employee-maps/unmatched', { params }).then((r) => r.data),
  suggest: (name) => apiClient.get('/employee-maps/suggest', { params: { name } }).then((r) => r.data),
};
