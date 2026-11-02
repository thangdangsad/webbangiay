<template>
  <div><h2 style="margin-bottom:16px">Sản phẩm yêu thích</h2>
    <ProductGrid :items="items" cols="3" /></div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth', layout: 'account' })
const { $api } = useNuxtApp(); const wl = useWishlistStore()
const all = ref<any[]>([])
onMounted(async () => { all.value = (await $api('/me/wishlist')).data })
const items = computed(() => all.value.filter((p) => wl.ids.includes(p.id)))
useSeoMeta({ title: 'Yêu thích', robots: 'noindex' })
</script>
