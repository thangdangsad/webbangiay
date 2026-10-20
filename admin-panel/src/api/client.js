import axios from 'axios'

// Laravel API base URL (api/), e.g. http://localhost:8000
export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

// Axios instance talking to the Laravel API. Sanctum's SPA auth is
// cookie-based: every request must carry the session cookie, and axios must
// read the `XSRF-TOKEN` cookie Laravel sets (via GET /sanctum/csrf-cookie)
// and send it back as the `X-XSRF-TOKEN` header on state-changing requests.
const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  // axios' actual config key is `withXSRFToken` (not `withXSRF`) — it forces
  // the X-XSRF-TOKEN header to be sent even for cross-origin requests (the
  // admin-panel's Vite dev origin vs. the API's origin are always different
  // hosts/ports). Without this, axios defaults to only sending the header
  // for same-origin requests, so every unsafe request gets a 419 CSRF
  // token mismatch from Laravel.
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: {
    Accept: 'application/json',
  },
})

let csrfReady = null

/**
 * Hit GET /sanctum/csrf-cookie once (per page load) before the first
 * POST/PATCH/PUT/DELETE, per Laravel Sanctum's documented SPA auth flow.
 * Cached so repeated calls don't re-fetch the cookie unnecessarily.
 */
export function ensureCsrfCookie() {
  if (!csrfReady) {
    csrfReady = api.get('/sanctum/csrf-cookie')
  }
  return csrfReady
}

/**
 * Wrap an unsafe (state-changing) request so the CSRF cookie is always
 * present first. Safe to call for every POST/PATCH/PUT/DELETE.
 */
async function withCsrf(request) {
  await ensureCsrfCookie()
  return request()
}

export const http = {
  get: (url, config) => api.get(url, config),
  post: (url, data, config) => withCsrf(() => api.post(url, data, config)),
  put: (url, data, config) => withCsrf(() => api.put(url, data, config)),
  patch: (url, data, config) => withCsrf(() => api.patch(url, data, config)),
  delete: (url, config) => withCsrf(() => api.delete(url, config)),
}

export default api
