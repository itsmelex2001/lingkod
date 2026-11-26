import { api, request } from './api.js';

export function createUserManagementService(apiBase = '/sdn/api') {
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
    listStaff: (params = {}) => api.get(url('/admin/users/staff', params), { credentials: 'include' }),
    createStaff: (payload) => api.post(url('/admin/users/staff'), payload, { credentials: 'include' }),
    updateStaff: (id, payload) => api.put(url(`/admin/users/staff/${id}`), payload, { credentials: 'include' }),
    resetStaffPassword: (id) => api.post(url(`/admin/users/staff/${id}/reset-password`), {}, { credentials: 'include' }),

    listPatients: (params = {}) => api.get(url('/admin/users/patients', params), { credentials: 'include' }),
    generatePatientTempPassword: (id) =>
      api.post(url(`/admin/users/patients/${id}/temp-password`), {}, { credentials: 'include' }),
    updatePatientEmail: (id, email) =>
      request(url(`/admin/users/patients/${id}/email`), {
        method: 'PATCH',
        body: JSON.stringify({ email }),
        credentials: 'include',
      }),
    togglePatientStatus: (id, account_status) =>
      request(url(`/admin/users/patients/${id}/status`), {
        method: 'PATCH',
        body: JSON.stringify({ account_status }),
        credentials: 'include',
      }),
  };
}
