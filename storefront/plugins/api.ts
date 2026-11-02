// Plugin $api: bọc $fetch — gắn Bearer + X-Cart-Token, tự refresh khi TOKEN_EXPIRED (single-flight).
export class ApiError extends Error {
  constructor(public status: number, public code: string, message: string, public details: Record<string, string[]> = {}) {
    super(message)
  }
  /** Lỗi của từng trường (422) → { field: 'message' } */
  get fields(): Record<string, string> {
    return Object.fromEntries(Object.entries(this.details || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
  }
}

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const base = import.meta.server ? config.apiBaseServer : config.public.apiBase
  let refreshing: Promise<any> | null = null

  async function raw(path: string, opts: any = {}) {
    const auth = useAuthStore()
    const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(opts.headers || {}) }
    if (auth.token) headers.Authorization = `Bearer ${auth.token}`
    if (import.meta.client) {
      const ct = localStorage.getItem('cart_token')
      if (ct && !auth.token) headers['X-Cart-Token'] = ct
    }
    try {
      const res = await $fetch.raw(path, { baseURL: base, credentials: 'include', ...opts, headers })
      const t = res.headers.get('X-Cart-Token')
      if (t && import.meta.client) localStorage.setItem('cart_token', t)
      return res._data
    } catch (e: any) {
      const body = e?.data?.error
      throw new ApiError(e?.status ?? e?.response?.status ?? 0, body?.code ?? 'NETWORK', body?.message ?? 'Không kết nối được máy chủ', body?.details ?? {})
    }
  }

  async function refresh() {
    refreshing ??= raw('/auth/refresh', { method: 'POST' }).finally(() => (refreshing = null))
    return refreshing
  }

  async function api(path: string, opts: any = {}): Promise<any> {
    try {
      return await raw(path, opts)
    } catch (e) {
      if (e instanceof ApiError && import.meta.client && ['TOKEN_EXPIRED', 'SESSION_REVOKED'].includes(e.code) && !opts._retried) {
        try {
          useAuthStore().setSession(await refresh())
          return await api(path, { ...opts, _retried: true })
        } catch {
          useAuthStore().clear()
          throw e
        }
      }
      throw e
    }
  }
  return { provide: { api: Object.assign(api, { refresh }) } }
})
