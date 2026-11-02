<template><span class="pill" :class="cls">{{ text }}</span></template>
<script setup>
const props = defineProps({ map: Object, value: String })
const t = props.map?.[props.value] || [props.value, 'p-gray']
const text = t[0]
const cls = t[1]
</script>
