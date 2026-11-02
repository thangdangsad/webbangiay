<template>
  <div class="container">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Tìm kiếm</div>
    <h1 style="margin-bottom:16px">Kết quả cho “{{ route.query.q }}”</h1>
    <ProductListing mode="search" />
  </div>
</template>
<script setup lang="ts">
const route = useRoute()
useSeoMeta({ title: () => `Tìm kiếm: ${route.query.q ?? ''}`, robots: 'noindex' })
</script>
