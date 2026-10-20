<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()

const categories = ref([])
const searchQuery = ref('')

onMounted(async () => {
  try {
    const { data } = await http.get('/api/categories')
    categories.value = data.data
  } catch {
    categories.value = []
  }
})

function submitSearch() {
  const q = searchQuery.value.trim()
  if (!q) return
  router.push({ name: 'search', query: { q } })
}

async function doLogout() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <header class="navbar">
    <div class="navbar-top">
      <div class="container navbar-top-inner">
        <span class="brand-tagline">Czuka Shop | Giày chính hãng</span>
        <nav class="top-links">
          <template v-if="auth.isLoggedIn">
            <span class="hello">Chào, {{ auth.customer?.user_name }}</span>
            <RouterLink to="/account">Tài khoản</RouterLink>
            <a href="#" @click.prevent="doLogout">Đăng xuất</a>
          </template>
          <template v-else>
            <RouterLink to="/login">Đăng nhập</RouterLink>
            <RouterLink to="/register">Đăng ký</RouterLink>
          </template>
        </nav>
      </div>
    </div>

    <div class="navbar-main">
      <div class="container navbar-main-inner">
        <RouterLink to="/" class="logo">Czuka Shop</RouterLink>

        <form class="search-form" @submit.prevent="submitSearch">
          <input v-model="searchQuery" type="search" placeholder="Tìm kiếm sản phẩm" />
          <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        </form>

        <RouterLink to="/cart" class="cart-link">
          Giỏ hàng
          <span class="cart-count">{{ cart.itemCount }}</span>
        </RouterLink>
      </div>
    </div>

    <nav class="navbar-categories">
      <div class="container categories-inner">
        <RouterLink v-for="c in categories" :key="c.category_id" :to="`/category/${c.category_id}`">
          {{ c.category_name }}
        </RouterLink>
      </div>
    </nav>
  </header>
</template>

<style scoped>
.navbar-top {
  background: #3b3b3b;
  color: #fff;
  font-size: 13px;
}

.navbar-top-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
}

.top-links {
  display: flex;
  gap: 14px;
  align-items: center;
}

.top-links a {
  color: #fff;
}

.hello {
  color: #ffc107;
  font-weight: 600;
}

.navbar-main {
  background: linear-gradient(to right, #f8ffff, #e9f8ff);
  border-bottom: 1px solid var(--color-border);
}

.navbar-main-inner {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 14px 0;
}

.logo {
  font-size: 22px;
  font-weight: 800;
  color: var(--color-primary);
  white-space: nowrap;
}

.search-form {
  flex: 1;
  display: flex;
  gap: 8px;
}

.search-form input {
  flex: 1;
  border-radius: 50px;
  border: 2px solid #0dcaf0;
  padding: 8px 18px;
}

.search-form button {
  border-radius: 50px;
}

.cart-link {
  position: relative;
  font-weight: 600;
  color: var(--color-text);
  white-space: nowrap;
}

.cart-count {
  display: inline-block;
  min-width: 18px;
  text-align: center;
  background: var(--color-accent);
  color: #fff;
  border-radius: 50%;
  font-size: 12px;
  padding: 1px 5px;
  margin-left: 4px;
}

.navbar-categories {
  background: #fff;
  border-bottom: 1px solid var(--color-border);
}

.categories-inner {
  display: flex;
  gap: 18px;
  padding: 10px 0;
  overflow-x: auto;
}

.categories-inner a {
  color: var(--color-text);
  font-weight: 500;
  white-space: nowrap;
}
</style>
