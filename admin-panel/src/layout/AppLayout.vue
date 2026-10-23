<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Menu from 'primevue/menu'

import Sidebar from '@/components/Sidebar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const sidebarVisible = ref(true)
function toggleSidebar() {
  sidebarVisible.value = !sidebarVisible.value
}

const userMenu = ref(null)
const userMenuItems = [
  {
    label: 'Đăng xuất',
    icon: 'pi pi-sign-out',
    command: doLogout,
  },
]

function toggleUserMenu(event) {
  userMenu.value?.toggle(event)
}

async function doLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell">
    <Sidebar :visible="sidebarVisible" />

    <div class="app-main">
      <header class="app-topbar">
        <Button
          text
          rounded
          severity="secondary"
          icon="pi pi-bars"
          aria-label="Thu gọn menu"
          @click="toggleSidebar"
        />
        <div class="app-topbar-title">Czuka Shop Admin</div>

        <div class="app-topbar-user" @click="toggleUserMenu">
          <div class="user-meta">
            <div class="user-name">{{ auth.staff?.user_name }}</div>
            <div class="user-role">{{ auth.isAdmin ? 'Quản trị viên' : 'Nhân viên' }}</div>
          </div>
          <i class="pi pi-user-edit user-avatar"></i>
          <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
        </div>
      </header>

      <main class="app-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
}

.app-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.app-topbar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 24px;
  height: 64px;
  flex-shrink: 0;
  background: var(--p-content-background);
  border-bottom: 1px solid var(--p-content-border-color);
}

.app-topbar-title {
  font-size: 18px;
  font-weight: 700;
  flex: 1;
}

.app-topbar-user {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 8px;
}

.app-topbar-user:hover {
  background: var(--p-content-hover-background);
}

.user-meta {
  text-align: right;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
}

.user-role {
  font-size: 12px;
  color: var(--p-text-muted-color);
}

.user-avatar {
  font-size: 26px;
  color: var(--p-primary-color);
}

.app-content {
  flex: 1;
  padding: 24px 28px 48px;
  max-width: 1280px;
  margin: 0 auto;
  width: 100%;
}
</style>
