import apiClient from './apiClient';

export const comparisonService = {
  employee: (nik, periodIds, siteCode) =>
    apiClient.get(`/comparison/employee/${nik}`, {
      params: {
        periods: periodIds.join(','),
        site_code: siteCode,
      },
    }).then((r) => r.data),

  site: (siteCode, periodIds) =>
    apiClient.get(`/comparison/site/${siteCode}`, {
      params: { periods: periodIds.join(',') },
    }).then((r) => r.data),
};
