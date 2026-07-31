import apiClient from './apiClient';

export const exportService = {
  preview: (sheetId) => apiClient.get(`/sheets/${sheetId}/export/preview`).then((r) => r.data),
  downloadUrl: (sheetId) => `/api/sheets/${sheetId}/export`,
  downloadPdfUrl: (sheetId) => `/api/sheets/${sheetId}/export-pdf`,
};
