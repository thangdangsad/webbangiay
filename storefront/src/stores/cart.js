import { defineStore } from 'pinia'

import { http } from '@/api/client'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    loading: false,
  }),

  getters: {
    itemCount: (state) => state.items.reduce((sum, item) => sum + item.product_quantity, 0),
    total: (state) =>
      state.items.reduce((sum, item) => sum + Number(item.product_price) * item.product_quantity, 0),
  },

  actions: {
    async fetchCart() {
      this.loading = true
      try {
        const { data } = await http.get('/api/cart')
        this.items = data.data
      } finally {
        this.loading = false
      }
    },

    async addItem(productId, quantity = 1) {
      const { data } = await http.post('/api/cart/items', {
        product_id: productId,
        quantity,
      })
      await this.fetchCart()
      return data.data
    },

    async updateItem(cartId, quantity) {
      await http.patch(`/api/cart/items/${cartId}`, { quantity })
      await this.fetchCart()
    },

    async removeItem(cartId) {
      await http.delete(`/api/cart/items/${cartId}`)
      await this.fetchCart()
    },

    /** Called after a successful checkout — the API already emptied the cart. */
    clearLocally() {
      this.items = []
    },
  },
})
