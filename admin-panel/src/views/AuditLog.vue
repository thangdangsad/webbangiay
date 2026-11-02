<template>
  <div>
    <div class="ph"><div><h1>Nhật ký hoạt động</h1><p>Mọi thao tác ghi của nhân viên</p></div></div>
    <div class="card tcard">
      <div class="tbar"><input v-model="q" class="f" placeholder="Nhân viên / hành động / đối tượng" @input="debounced" /></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>Thời gian</th><th>Nhân viên</th><th>Hành động</th><th>Đối tượng</th><th>Chi tiết</th><th>IP</th></tr></thead>
        <tbody><tr v-for="a in rows" :key="a.id"><td>{{ dt(a.created_at) }}</td><td>{{ a.staff }}</td><td><span class="pill p-gray">{{ a.action }}</span></td><td>{{ a.entity }}<template v-if="a.entity_id"> #{{ a.entity_id }}</template></td>
          <td style="max-width:360px"><small style="word-break:break-all">{{ a.after?.path }}</small></td><td>{{ a.ip }}</td></tr><tr v-if="!rows.length"><td colspan="6" class="loading">Chưa có nhật ký</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api, { dt } from '../api'
import Pager from '../components/Pager.vue'
const rows = ref([]); const meta = ref(null); const loading = ref(true); const q = ref(''); const page = ref(1)
let t; const debounced = () => { clearTimeout(t); t = setTimeout(() => { page.value = 1; load() }, 300) }
async function load() { loading.value = true; try { const r = (await api.get('/admin/audit-logs', { params: { q: q.value || undefined, page: page.value } })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false } }
onMounted(load)
</script>
