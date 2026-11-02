<template>
  <div class="modal-bg" @mousedown.self="$emit('close')">
    <div class="modal" :style="{ width: width }"><h3>{{ title }}</h3><slot /></div>
  </div>
</template>
<script setup>
defineProps({ title: String, width: { type: String, default: '560px' } })
defineEmits(['close'])
</script>
