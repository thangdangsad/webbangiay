<template>
  <div class="container"><div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › {{ page?.title }}</div>
    <h1>{{ page?.title }}</h1><div class="prose" style="margin-top:14px" v-html="page?.content"></div></div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp(); const slug = String(useRoute().params.slug)
const { data: page, error } = await useAsyncData(`page-${slug}`, async () => (await $api(`/pages/${slug}`)).data)
if (error.value) throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy trang', fatal: true })
useSeoMeta({ title: () => page.value?.title })
</script>
