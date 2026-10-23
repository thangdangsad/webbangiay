<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

import AppLayout from '@/layout/AppLayout.vue'

const route = useRoute()

// Login page renders full-screen, without the sidebar/topbar shell. The
// router's navigation guard (see router/index.js) already awaits
// auth.fetchMe() before the first route resolves, so by the time any view
// here mounts the auth store is known-good — no separate loading gate needed.
const showShell = computed(() => route.name !== 'login')
</script>

<template>
  <Toast />
  <ConfirmDialog />

  <AppLayout v-if="showShell" />
  <RouterView v-else />
</template>
