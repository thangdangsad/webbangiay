<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function doLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <aside class="sidebar">
    <div class="brand">Czuka Admin</div>

    <nav class="nav">
      <RouterLink to="/" exact-active-class="active">Tổng quan</RouterLink>
      <RouterLink to="/products" active-class="active">Sản phẩm</RouterLink>
      <RouterLink to="/categories" active-class="active">Danh mục</RouterLink>
      <RouterLink to="/orders" active-class="active">Đơn hàng</RouterLink>
      <RouterLink to="/customers" active-class="active">Khách hàng</RouterLink>
      <RouterLink to="/staff" active-class="active">Nhân viên</RouterLink>
    </nav>

    <div class="user-box">
      <div class="user-name">{{ auth.staff?.user_name }}</div>
      <div class="user-role">{{ auth.isAdmin ? 'Quản trị viên' : 'Nhân viên' }}</div>
      <a href="#" class="logout-link" @click.prevent="doLogout">Đăng xuất</a>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 220px;
  min-height: 100vh;
  background: var(--sidebar-bg);
  color: var(--sidebar-text);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.brand {
  padding: 20px 20px;
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.nav {
  display: flex;
  flex-direction: column;
  padding: 12px 0;
  flex: 1;
}

.nav a {
  color: var(--sidebar-text);
  padding: 11px 20px;
  font-size: 14px;
  font-weight: 500;
}

.nav a:hover {
  background: var(--sidebar-active);
  text-decoration: none;
  color: #fff;
}

.nav a.active {
  background: var(--sidebar-active);
  color: #fff;
  border-left: 3px solid var(--color-primary);
}

.user-box {
  padding: 16px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.user-name {
  color: #fff;
  font-weight: 600;
  font-size: 14px;
}

.user-role {
  font-size: 12px;
  color: var(--sidebar-text);
  margin-top: 2px;
}

.logout-link {
  display: inline-block;
  margin-top: 10px;
  font-size: 13px;
  color: #ff8a80;
}
</style>
