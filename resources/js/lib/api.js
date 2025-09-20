/**
 * API utility for consistent API calls with error handling
 */
export async function api(path, options = {}) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  
  const config = {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(token && { 'X-CSRF-TOKEN': token }),
      ...options.headers
    },
    credentials: 'same-origin',
    method: options.method || 'GET',
    ...options
  };

  if (options.body && typeof options.body === 'object') {
    config.body = JSON.stringify(options.body);
  } else if (options.body) {
    config.body = options.body;
  }

  try {
    const response = await fetch(path, config);
    
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData?.message || `Request failed with status ${response.status}`);
    }

    const json = await response.json();
    
    // Check for API envelope format with ok field
    if (json?.ok === false) {
      throw new Error(json?.message || 'Request failed');
    }

    return json;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

/**
 * Convenience methods for common HTTP verbs
 */
export const apiGet = (path, options = {}) => api(path, { method: 'GET', ...options });
export const apiPost = (path, body, options = {}) => api(path, { method: 'POST', body, ...options });
export const apiPatch = (path, body, options = {}) => api(path, { method: 'PATCH', body, ...options });
export const apiPut = (path, body, options = {}) => api(path, { method: 'PUT', body, ...options });
export const apiDelete = (path, options = {}) => api(path, { method: 'DELETE', ...options });
