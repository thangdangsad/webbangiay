<template>
  <div class="container">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Sản phẩm</div>
    <ProductListing mode="all" />
  </div>
</template>
<script setup lang="ts">
useSeoMeta({ title: 'Tất cả sản phẩm', description: 'Giày sneaker, chạy bộ, giày da, sandal chính hãng.' })
</script>
