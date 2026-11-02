<template>
  <div class="tfoot">
    <span>Hiển thị {{ from }}–{{ to }} / {{ meta.total }}</span>
    <div class="pg">
      <button :disabled="meta.page <= 1" @click="$emit('go', meta.page - 1)">‹</button>
      <button v-for="n in nums" :key="n" :class="{ on: n === meta.page }" @click="$emit('go', n)">{{ n }}</button>
      <button :disabled="meta.page >= pages" @click="$emit('go', meta.page + 1)">›</button>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ meta: { type: Object, required: true } })
defineEmits(['go'])
const pages = computed(() => Math.max(1, Math.ceil(props.meta.total / props.meta.limit)))
const from = computed(() => (props.meta.total ? (props.meta.page - 1) * props.meta.limit + 1 : 0))
const to = computed(() => Math.min(props.meta.total, props.meta.page * props.meta.limit))
const nums = computed(() => { const s = Math.max(1, Math.min(props.meta.page - 2, pages.value - 4)); return Array.from({ length: Math.min(5, pages.value) }, (_, i) => s + i) })
</script>
