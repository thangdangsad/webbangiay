<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import Sidebar from '@/components/Sidebar.vue'

const route = useRoute()

// Login page renders full-screen, without the sidebar shell. The router's
// navigation guard (see router/index.js) already awaits auth.fetchMe()
// before the first route resolves, so by the time any view here mounts the
// auth store is known-good — no separate loading gate needed.
const showShell = computed(() => route.name !== 'login')
</script>

<template>
  <div v-if="showShell" class="app-shell">
    <Sidebar />
    <main class="content">
      <div class="container">
        <RouterView />
      </div>
    </main>
  </div>
  <RouterView v-else />
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
}

.content {
  flex: 1;
  min-width: 0;
  padding: 28px 0 48px;
}
</style>
