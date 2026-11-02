<template><div class="container"><div class="err404"><h1>404</h1><h2>Không tìm thấy trang</h2><p style="color:var(--muted);margin:10px 0 20px">Trang bạn tìm không tồn tại hoặc đã được di chuyển.</p><NuxtLink class="btn brand" to="/">Về trang chủ</NuxtLink></div></div></template>
<script setup lang="ts">
setResponseStatus(useRequestEvent()!, 404)
useSeoMeta({ title: 'Không tìm thấy trang' })
</script>
