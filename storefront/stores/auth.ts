import { defineStore } from 'pinia'

let initPromise: Promise<void> | null = null

export interface Customer { id: number; full_name: string; email: string | null; phone: string | null; gender?: string | null; birthday?: string | null; email_verified?: boolean; has_password?: boolean }

// Access token chỉ giữ trong bộ nhớ (Pinia) — refresh token nằm trong cookie HttpOnly.
export const useAuthStore = defineStore('auth', {
  state: () => ({ token: null as string | null, user: null as Customer | null, ready: false }),
  getters: { loggedIn: (s) => !!s.user },
  actions: {
    setSession(res: { access_token: string; user: Customer }) {
      this.token = res.access_token
      this.user = res.user
      useCookie('has_session', { maxAge: 60 * 60 * 24 * 30, sameSite: 'lax' }).value = '1'
    },
    clear() {
      this.token = null
      this.user = null
      useCookie('has_session').value = null
    },
    /** Khôi phục phiên sau F5: đổi refresh cookie lấy access token mới. */
    init(): Promise<void> {
      if (import.meta.server) return Promise.resolve()
      initPromise ??= (async () => {
        if (useCookie('has_session').value) {
          try {
            this.setSession(await useNuxtApp().$api.refresh())
          } catch {
            this.clear()
          }
        }
        this.ready = true
      })()
      return initPromise
    },
    async login(identifier: string, password: string) {
      const { $api } = useNuxtApp()
      const cart = useCartStore()
      const res = await $api('/auth/login', { method: 'POST', body: { identifier, password } })
      this.setSession(res)
      cart.forgetToken()
      await cart.fetch()
      await useWishlistStore().fetch()
    },
    async loginGoogle(credential: string) {
      const { $api } = useNuxtApp()
      const res = await $api('/auth/google', { method: 'POST', body: { credential } })
      this.setSession(res)
      useCartStore().forgetToken()
      await Promise.all([useCartStore().fetch(), useWishlistStore().fetch()])
    },
    async register(payload: Record<string, any>) {
      const { $api } = useNuxtApp()
      const res = await $api('/auth/register', { method: 'POST', body: payload })
      this.setSession(res)
      useCartStore().forgetToken()
      await useCartStore().fetch()
    },
    async logout() {
      const { $api } = useNuxtApp()
      try {
        await $api('/auth/logout', { method: 'POST' })
      } catch {}
      this.clear()
      useWishlistStore().ids = []
      await useCartStore().fetch()
      await navigateTo('/')
    },
  },
})
