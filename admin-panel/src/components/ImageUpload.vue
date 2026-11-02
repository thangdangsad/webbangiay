<template>
  <div>
    <div class="drop" @dragover.prevent @drop.prevent="onDrop" @click="pick.click()" style="cursor:pointer">
      {{ busy ? 'Đang tải lên...' : label }}<br /><small>JPG/PNG/WebP ≤ 5MB</small>
      <input ref="pick" type="file" accept="image/jpeg,image/png,image/webp" :multiple="multiple" hidden @change="onPick" />
    </div>
    <div v-if="err" class="errmsg" style="margin-top:8px">{{ err }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api'
const props = defineProps({ type: { type: String, default: 'product' }, multiple: Boolean, label: { type: String, default: 'Kéo thả hoặc bấm để chọn ảnh' } })
const emit = defineEmits(['uploaded'])
const pick = ref(null); const busy = ref(false); const err = ref('')
async function upload(files) {
  err.value = ''; busy.value = true
  for (const file of files) {
    const fd = new FormData(); fd.append('file', file); fd.append('type', props.type)
    try { emit('uploaded', (await api.post('/admin/uploads', fd)).data.data) } catch (e) { err.value = e.message }
  }
  busy.value = false
}
const onPick = (e) => { upload([...e.target.files]); e.target.value = '' }
const onDrop = (e) => upload([...e.dataTransfer.files])
</script>
