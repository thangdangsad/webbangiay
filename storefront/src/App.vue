<script setup>
import { onMounted } from 'vue'

import NavBar from '@/components/NavBar.vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth = useAuthStore()
const cart = useCartStore()

onMounted(async () => {
  if (!auth.ready) {
    await auth.fetchMe()
  }
  await cart.fetchCart()
})
</script>

<template>
  <div class="flex min-h-screen flex-col bg-ink-50">
    <NavBar />
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 pb-16 pt-6 sm:px-6">
      <RouterView />
    </main>
    <footer class="bg-ink-900 py-6 text-center text-sm text-ink-300">
      <div class="mx-auto max-w-7xl px-4">
        <p class="font-display tracking-wide text-white">CZUKA SHOP</p>
        <p class="mt-1">Czuka Shop — demo storefront (Vue 3 + Vite SPA on top of the Laravel API).</p>
      </div>
    </footer>
  </div>
</template>
