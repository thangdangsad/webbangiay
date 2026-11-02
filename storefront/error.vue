<template>
  <NuxtLayout>
    <div class="container"><div class="err404"><h1>{{ error?.statusCode }}</h1><h2>{{ error?.statusCode === 404 ? 'Không tìm thấy trang' : 'Đã có lỗi xảy ra' }}</h2>
      <p style="color:var(--muted);margin:10px 0 20px">{{ error?.statusMessage }}</p><button class="btn brand" @click="clearError({ redirect: '/' })">Về trang chủ</button></div></div>
  </NuxtLayout>
</template>
<script setup lang="ts">
defineProps<{ error: any }>()
</script>
