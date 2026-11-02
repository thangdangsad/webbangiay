<template>
  <div v-if="pending" class="grid"><div v-for="i in 8" :key="i" class="skel" style="aspect-ratio:1/1.3"></div></div>
  <div v-else-if="!items.length" class="empty">Không tìm thấy sản phẩm phù hợp.</div>
  <div v-else class="grid" :class="Number(cols) === 3 ? 'g3' : ''"><ProductCard v-for="p in items" :key="p.id" :p="p" /></div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ items: any[]; pending?: boolean; cols?: number | string }>(), { pending: false, cols: 4 })
</script>
