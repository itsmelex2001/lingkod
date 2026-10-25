/**
 * Core HTTP Request Wrapper for SDN API
 */

export class ApiError extends Error {
  constructor(message, status = 500, data = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

export async function request(endpoint, options = {}) {
  const defaultHeaders = {
    'Accept': 'application/json',
  };

  if (!(options.body instanceof FormData)) {
    defaultHeaders['Content-Type'] = 'application/json';
  }

  const config = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  };

  try {
    const res = await fetch(endpoint, config);
    const contentType = res.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    const data = isJson ? await res.json() : await res.text();

    if (!res.ok) {
      const errorMsg = (typeof data === 'object' && data?.message) ? data.message : `HTTP error: ${res.status}`;
      throw new ApiError(errorMsg, res.status, data);
    }

    return data;
  } catch (err) {
    if (err instanceof ApiError) throw err;
    throw new ApiError(err.message || 'Network request failed', 0, null);
  }
}

export const api = {
  get: (url, opts) => request(url, { ...opts, method: 'GET' }),
  post: (url, body, opts) => request(url, { ...opts, method: 'POST', body: body instanceof FormData ? body : JSON.stringify(body) }),
  put: (url, body, opts) => request(url, { ...opts, method: 'PUT', body: body instanceof FormData ? body : JSON.stringify(body) }),
  delete: (url, opts) => request(url, { ...opts, method: 'DELETE' }),
};
