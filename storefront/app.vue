<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
  <div v-if="ui.toast" class="toast">{{ ui.toast }}</div>
</template>

<script setup lang="ts">
const ui = useUiStore()
useHead({ titleTemplate: (t) => (t ? `${t} · SoleHouse` : 'SoleHouse – Giày chính hãng') })
</script>
