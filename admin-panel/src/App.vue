<template>
  <router-view v-if="$route.meta.public || !auth.loggedIn" />
  <AdminLayout v-else><router-view :key="$route.fullPath" /></AdminLayout>
  <div v-if="ui.toast" class="toast">{{ ui.toast }}</div>
</template>

<script setup>
import { useAuthStore } from './stores/auth'
import { useUi } from './stores/ui'
import AdminLayout from './layouts/AdminLayout.vue'
const auth = useAuthStore()
const ui = useUi()
</script>
