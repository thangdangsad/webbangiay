<template>
  <div v-if="pages > 1" class="pager">
    <button :disabled="page <= 1" @click="$emit('go', page - 1)">‹</button>
    <button v-for="n in nums" :key="n" :class="{ on: n === page }" @click="$emit('go', n)">{{ n }}</button>
    <button :disabled="page >= pages" @click="$emit('go', page + 1)">›</button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ page: number; total: number; limit: number }>()
defineEmits<{ go: [n: number] }>()
const pages = computed(() => Math.ceil(props.total / props.limit))
const nums = computed(() => {
  const s = Math.max(1, Math.min(props.page - 2, pages.value - 4))
  return Array.from({ length: Math.min(5, pages.value) }, (_, i) => s + i)
})
</script>
