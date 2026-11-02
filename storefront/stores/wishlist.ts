import { defineStore } from 'pinia'

export const useWishlistStore = defineStore('wishlist', {
  state: () => ({ ids: [] as number[] }),
  actions: {
    has(id: number) {
      return this.ids.includes(id)
    },
    async fetch() {
      const auth = useAuthStore()
      if (!auth.loggedIn) return (this.ids = [])
      const { $api } = useNuxtApp()
      this.ids = (await $api('/me/wishlist')).ids ?? []
    },
    async toggle(id: number) {
      const auth = useAuthStore()
      if (!auth.loggedIn) return navigateTo({ path: '/dang-nhap', query: { redirect: useRoute().fullPath } })
      const { $api } = useNuxtApp()
      if (this.has(id)) {
        await $api(`/me/wishlist/${id}`, { method: 'DELETE' })
        this.ids = this.ids.filter((x) => x !== id)
      } else {
        await $api(`/me/wishlist/${id}`, { method: 'PUT' })
        this.ids.push(id)
      }
    },
  },
})
