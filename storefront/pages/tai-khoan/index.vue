<template><div /></template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
await navigateTo('/tai-khoan/thong-tin', { replace: true })
</script>
