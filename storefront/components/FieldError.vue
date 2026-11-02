<template><div v-if="msg" class="e">{{ msg }}</div></template>
<script setup lang="ts">defineProps<{ msg?: string }>()</script>
