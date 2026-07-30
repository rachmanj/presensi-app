import apiClient from './apiClient';

export const importService = {
  list: (sheetId) => apiClient.get(`/sheets/${sheetId}/imports`).then((r) => r.data),
  upload: (sheetId, file, onProgress) => {
    const formData = new FormData();
    formData.append('file', file);
    return apiClient.post(`/sheets/${sheetId}/imports`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress,
    }).then((r) => r.data);
  },
  show: (importId) => apiClient.get(`/imports/${importId}`).then((r) => r.data),
  preview: (importId) => apiClient.get(`/imports/${importId}/preview`).then((r) => r.data),
  errors: (importId) => apiClient.get(`/imports/${importId}/errors`).then((r) => r.data),
  status: (importId) => apiClient.get(`/imports/${importId}/status`).then((r) => r.data),
  reparse: (importId) => apiClient.post(`/imports/${importId}/reparse`).then((r) => r.data),
  remove: (importId) => apiClient.delete(`/imports/${importId}`),
};
