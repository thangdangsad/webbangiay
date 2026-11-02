import { defineStore } from 'pinia'

export interface CartItem {
  variant_id: number; product_id: number; name: string; slug: string; sku: string; color: string; size: string
  image: { url: string; md: string; sm: string } | null; unit_price: number; compare_at_price: number | null
  qty: number; line_total: number; available: number; issue: string | null
}
export interface Cart {
  items: CartItem[]; count: number; weight_g: number; subtotal: number; discount_total: number
  coupon: { code: string; type: string; discount: number; free_ship: boolean } | null; total: number
  warnings: { code: string; message: string }[]
}

const empty = (): Cart => ({ items: [], count: 0, weight_g: 0, subtotal: 0, discount_total: 0, coupon: null, total: 0, warnings: [] })

export const useCartStore = defineStore('cart', {
  state: () => ({ cart: empty(), loading: false }),
  getters: { count: (s) => s.cart.count },
  actions: {
    token(): string | null {
      return import.meta.client ? localStorage.getItem('cart_token') : null
    },
    saveToken(t?: string | null) {
      if (import.meta.client && t) localStorage.setItem('cart_token', t)
    },
    forgetToken() {
      if (import.meta.client) localStorage.removeItem('cart_token')
    },
    apply(res: any) {
      this.cart = res.data
      this.saveToken(res.data.cart_token)
      return res.data as Cart
    },
    async fetch() {
      if (import.meta.server) return
      const { $api } = useNuxtApp()
      this.loading = true
      try {
        this.apply(await $api('/cart'))
      } finally {
        this.loading = false
      }
    },
    async add(variantId: number, qty = 1) {
      const { $api } = useNuxtApp()
      return this.apply(await $api('/cart/items', { method: 'POST', body: { variant_id: variantId, qty } }))
    },
    async setQty(variantId: number, qty: number) {
      const { $api } = useNuxtApp()
      return this.apply(await $api(`/cart/items/${variantId}`, { method: 'PATCH', body: { qty } }))
    },
    async remove(variantId: number) {
      const { $api } = useNuxtApp()
      return this.apply(await $api(`/cart/items/${variantId}`, { method: 'DELETE' }))
    },
    async applyCoupon(code: string) {
      const { $api } = useNuxtApp()
      return this.apply(await $api('/cart/coupon', { method: 'POST', body: { code } }))
    },
    async removeCoupon() {
      const { $api } = useNuxtApp()
      return this.apply(await $api('/cart/coupon', { method: 'DELETE' }))
    },
  },
})
