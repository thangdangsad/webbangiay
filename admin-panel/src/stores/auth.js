import { defineStore } from 'pinia'
import api, { refreshToken } from '../api'

let initPromise = null

export const useAuthStore = defineStore('auth', {
  state: () => ({ token: null, user: null, ready: false }),
  getters: { loggedIn: (s) => !!s.user },
  actions: {
    setSession(res) {
      this.token = res.access_token
      if (res.user) this.user = res.user
    },
    clear() { this.token = null; this.user = null },
    can(perm) { return !!this.user?.permissions?.includes(perm) },
    canAny(perms) { return !perms || perms.some((p) => this.can(p)) },
    init() {
      initPromise ??= refreshToken().catch(() => this.clear()).finally(() => (this.ready = true))
      return initPromise
    },
    async login(email, password) {
      const { data } = await api.post('/admin/auth/login', { email, password })
      if (data.data?.requires_2fa) return data.data
      this.setSession(data)
      return null
    },
    async verify2fa(challenge_token, code) {
      const { data } = await api.post('/admin/auth/2fa/verify', { challenge_token, code })
      this.setSession(data)
    },
    async logout() {
      try { await api.post('/admin/auth/logout') } catch {}
      this.clear()
    },
  },
})
