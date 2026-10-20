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
  <div class="app-shell">
    <NavBar />
    <main class="container">
      <RouterView />
    </main>
    <footer class="site-footer">
      <div class="container">
        <p>Czuka Shop — demo storefront (Vue 3 + Vite SPA on top of the Laravel API).</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
  width: 100%;
  padding-bottom: 48px;
}

.site-footer {
  background: #222;
  color: #ccc;
  padding: 18px 0;
  font-size: 13px;
  text-align: center;
}
</style>
