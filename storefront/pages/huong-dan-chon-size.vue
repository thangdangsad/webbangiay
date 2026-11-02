<template>
  <div class="container"><div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Hướng dẫn chọn size</div>
    <h1>{{ page?.title }}</h1><div class="prose" style="margin-top:14px" v-html="page?.content"></div>
    <div class="callout">Mẹo: nếu chân bạn nằm giữa hai size, hãy chọn size lớn hơn. Đổi size miễn phí trong 7 ngày.</div></div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp()
const { data: page } = await useAsyncData('size-guide', async () => (await $api('/pages/huong-dan-chon-size')).data)
useSeoMeta({ title: 'Hướng dẫn chọn size' })
</script>
