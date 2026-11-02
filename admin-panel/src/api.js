import axios from 'axios'
import { useAuthStore } from './stores/auth'
import router from './router'

// Access token chỉ ở bộ nhớ (Pinia); refresh token là cookie HttpOnly `rt_staff` (Path=/api/v1/admin/auth).
export const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api/v1'

const api = axios.create({ baseURL: API_BASE, withCredentials: true, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })

api.interceptors.request.use((cfg) => {
  const auth = useAuthStore()
  if (auth.token) cfg.headers.Authorization = `Bearer ${auth.token}`
  return cfg
})

let refreshing = null
export function refreshToken() {
  refreshing ??= axios
    .post(`${API_BASE}/admin/auth/refresh`, null, { withCredentials: true, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then((r) => { useAuthStore().setSession(r.data); return r.data })
    .finally(() => { refreshing = null })
  return refreshing
}

api.interceptors.response.use(
  (r) => r,
  async (err) => {
    const code = err.response?.data?.error?.code
    const cfg = err.config
    if (err.response?.status === 401 && ['TOKEN_EXPIRED', 'SESSION_REVOKED'].includes(code) && !cfg._retry && !cfg.url.includes('/auth/')) {
      cfg._retry = true
      try {
        await refreshToken()
        return api(cfg)
      } catch {
        useAuthStore().clear()
        router.push({ name: 'login' })
      }
    }
    const e = err.response?.data?.error
    const out = new Error(e?.message || 'Không kết nối được máy chủ')
    out.code = e?.code
    out.status = err.response?.status
    out.fields = Object.fromEntries(Object.entries(e?.details || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
    return Promise.reject(out)
  },
)

export default api

export * from './format'
