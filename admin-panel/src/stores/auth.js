import { defineStore } from 'pinia'

import { http } from '@/api/client'

export const ROLE_ADMIN = 1
export const ROLE_STAFF = 2

export const useAuthStore = defineStore('auth', {
  state: () => ({
    staff: null,
    // true until the initial GET /api/admin/me check (on app load) resolves,
    // so the UI doesn't flash "logged out" before we know for sure.
    ready: false,
  }),

  getters: {
    isLoggedIn: (state) => state.staff !== null,
    isAdmin: (state) => Number(state.staff?.role) === ROLE_ADMIN,
  },

  actions: {
    /**
     * Restore session on app load by asking the API who we are. A 401 just
     * means "not logged in" — not an error worth surfacing.
     */
    async fetchMe() {
      try {
        const { data } = await http.get('/api/admin/me')
        this.staff = data.data
      } catch {
        this.staff = null
      } finally {
        this.ready = true
      }
    },

    async login(payload) {
      const { data } = await http.post('/api/admin/login', payload)
      this.staff = data.data
      return this.staff
    },

    async logout() {
      await http.post('/api/admin/logout')
      this.staff = null
    },
  },
})
