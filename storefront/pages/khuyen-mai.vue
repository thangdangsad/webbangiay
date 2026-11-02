<template>
  <div class="container">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Khuyến mãi</div>
    <div class="salebar">
      <div><h2>{{ home?.flash_sale?.name || 'Săn sale – giá tốt mỗi ngày' }}</h2><p>Số lượng có hạn · giá đã bao gồm ưu đãi</p></div>
      <div v-if="cd" class="cd"><div>{{ cd.d }}<small>Ngày</small></div><div>{{ cd.h }}<small>Giờ</small></div><div>{{ cd.m }}<small>Phút</small></div><div>{{ cd.s }}<small>Giây</small></div></div>
    </div>
    <ProductListing mode="sale" />
  </div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp()
const { data: home } = await useAsyncData('home-sale', async () => (await $api('/home')).data)
const now = ref(Date.now())
onMounted(() => { const t = setInterval(() => (now.value = Date.now()), 1000); onBeforeUnmount(() => clearInterval(t)) })
const cd = computed(() => {
  const end = home.value?.flash_sale?.ends_at
  if (!end) return null
  const left = Math.max(0, Math.floor((new Date(end).getTime() - now.value) / 1000))
  const p = (n: number) => String(n).padStart(2, '0')
  return { d: p(Math.floor(left / 86400)), h: p(Math.floor((left % 86400) / 3600)), m: p(Math.floor((left % 3600) / 60)), s: p(left % 60) }
})
useSeoMeta({ title: 'Khuyến mãi' })
</script>
