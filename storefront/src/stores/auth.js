import { defineStore } from 'pinia'

import { http } from '@/api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    customer: null,
    // true until the initial GET /api/me check (on app load) resolves, so
    // the UI doesn't flash "logged out" before we know for sure.
    ready: false,
  }),

  getters: {
    isLoggedIn: (state) => state.customer !== null,
  },

  actions: {
    /**
     * Restore session on app load by asking the API who we are. A 401 just
     * means "not logged in" — not an error worth surfacing.
     */
    async fetchMe() {
      try {
        const { data } = await http.get('/api/me')
        this.customer = data.data
      } catch {
        this.customer = null
      } finally {
        this.ready = true
      }
    },

    async register(payload) {
      const { data } = await http.post('/api/register', payload)
      return data.data
    },

    async login(payload) {
      const { data } = await http.post('/api/login', payload)
      this.customer = data.data
      return this.customer
    },

    async logout() {
      await http.post('/api/logout')
      this.customer = null
    },
  },
})
