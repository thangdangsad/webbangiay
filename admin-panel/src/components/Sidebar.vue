<script setup>
import { useAuthStore } from '@/stores/auth'

defineProps({
  visible: { type: Boolean, default: true },
})

const auth = useAuthStore()

const items = [
  { label: 'Tổng quan', icon: 'pi pi-home', to: { name: 'dashboard' } },
  { label: 'Sản phẩm', icon: 'pi pi-shopping-bag', to: { name: 'products' } },
  { label: 'Danh mục', icon: 'pi pi-tags', to: { name: 'categories' } },
  { label: 'Đơn hàng', icon: 'pi pi-shopping-cart', to: { name: 'orders' } },
  { label: 'Khách hàng', icon: 'pi pi-users', to: { name: 'customers' } },
  { label: 'Nhân viên', icon: 'pi pi-id-card', to: { name: 'staff' }, adminOnly: true },
]
</script>

<template>
  <aside class="app-sidebar" :class="{ 'app-sidebar-hidden': !visible }">
    <div class="sidebar-brand">
      <i class="pi pi-box"></i>
      <span>Czuka Shop</span>
    </div>

    <nav class="sidebar-nav">
      <template v-for="item in items" :key="item.label">
        <RouterLink
          v-if="!item.adminOnly || auth.isAdmin"
          :to="item.to"
          class="sidebar-link"
          active-class="sidebar-link-active"
          exact-active-class="sidebar-link-active"
        >
          <i :class="item.icon"></i>
          <span>{{ item.label }}</span>
        </RouterLink>
      </template>
    </nav>
  </aside>
</template>

<style scoped>
.app-sidebar {
  width: 260px;
  flex-shrink: 0;
  background: #1e293b;
  color: #cbd5e1;
  display: flex;
  flex-direction: column;
  transition: margin-left 0.2s ease;
}

.app-sidebar-hidden {
  margin-left: -260px;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 22px 24px;
  font-size: 19px;
  font-weight: 700;
  color: #fff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-brand i {
  font-size: 22px;
  color: var(--p-primary-color);
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  padding: 16px 12px;
  gap: 2px;
  flex: 1;
  overflow-y: auto;
}

.sidebar-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  border-radius: 8px;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.15s, color 0.15s;
}

.sidebar-link i {
  font-size: 16px;
  width: 18px;
  text-align: center;
}

.sidebar-link:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  text-decoration: none;
}

.sidebar-link-active {
  background: var(--p-primary-color);
  color: var(--p-primary-contrast-color);
}

@media (max-width: 960px) {
  .app-sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 30;
    height: 100vh;
  }
}
</style>
