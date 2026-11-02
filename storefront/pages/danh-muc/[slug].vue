<template>
  <div class="container">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › {{ name }}</div>
    <h1 style="margin-bottom:16px">{{ name }}</h1>
    <ProductListing mode="category" :category="slug" />
  </div>
</template>
<script setup lang="ts">
const slug = String(useRoute().params.slug)
const { $api } = useNuxtApp()
const { data: cats } = await useAsyncData('cats-flat', async () => (await $api('/categories')).data)
const name = computed(() => {
  for (const c of cats.value ?? []) {
    if (c.slug === slug) return c.name
    const ch = c.children?.find((x: any) => x.slug === slug)
    if (ch) return ch.name
  }
  return 'Danh mục'
})
useSeoMeta({ title: () => name.value })
</script>
