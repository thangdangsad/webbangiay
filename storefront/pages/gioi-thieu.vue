<template>
  <div class="container"><h1 style="margin-top:24px">{{ page?.title }}</h1><div class="prose" style="margin-top:14px" v-html="page?.content"></div></div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp()
const { data: page } = await useAsyncData('about', async () => (await $api('/pages/gioi-thieu')).data)
useSeoMeta({ title: 'Giới thiệu' })
</script>
