import { api, request } from './api.js';

export function createSettingsService(apiBase = '/sdn/api') {
  const base = apiBase.replace(/\/$/, '');
  const isPhpRouter = base.endsWith('.php');

  const url = (path, query = {}) => {
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    const qs = new URLSearchParams(
      Object.entries(query).filter(([, v]) => v !== '' && v != null)
    ).toString();

    if (isPhpRouter) {
      return `${base}?route=${encodeURIComponent(cleanPath)}${qs ? `&${qs}` : ''}`;
    }
    return `${base}${cleanPath}${qs ? `?${qs}` : ''}`;
  };

  return {
    fetchAll: () => api.get(url('/admin/settings'), { credentials: 'include' }),
    saveGeneral: (payload) => api.put(url('/admin/settings/general'), payload, { credentials: 'include' }),
    saveScheduling: (payload) => api.put(url('/admin/settings/scheduling'), payload, { credentials: 'include' }),

    fetchFaqs: (params = {}) => api.get(url('/admin/faqs', params), { credentials: 'include' }),
    createFaq: (payload) => api.post(url('/admin/faqs'), payload, { credentials: 'include' }),
    updateFaq: (id, payload) => api.put(url(`/admin/faqs/${id}`), payload, { credentials: 'include' }),
    deleteFaq: (id) => api.delete(url(`/admin/faqs/${id}`), { credentials: 'include' }),
    reorderFaqs: (items) =>
      request(url('/admin/faqs/reorder'), {
        method: 'PATCH',
        body: JSON.stringify({ items }),
        credentials: 'include',
      }),
  };
}
