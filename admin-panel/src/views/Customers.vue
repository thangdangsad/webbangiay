<template>
  <div>
    <div class="ph"><div><h1>Khách hàng</h1><p>{{ meta?.total ?? 0 }} khách</p></div></div>
    <div class="card tcard">
      <div class="tbar"><input v-model="q" class="f" placeholder="Tên / email / SĐT" @input="debounced" /><select v-model="status" class="f" @change="reload"><option value="">Mọi trạng thái</option><option value="active">Hoạt động</option><option value="blocked">Đã khóa</option></select></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>Khách hàng</th><th>Liên hệ</th><th class="r">Số đơn</th><th class="r">Tổng chi tiêu</th><th>Tham gia</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody><tr v-for="c in rows" :key="c.id"><td><b>{{ c.full_name }}</b></td><td>{{ c.email || '—' }}<small>{{ c.phone }}</small></td><td class="r">{{ c.orders_count }}</td><td class="r"><b>{{ money(c.total_spent) }}</b></td><td>{{ dOnly(c.created_at) }}</td>
          <td><span class="pill" :class="c.status === 'active' ? 'p-green' : 'p-red'">{{ c.status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span></td><td class="r"><router-link class="btn sm" :to="`/customers/${c.id}`">Xem</router-link></td></tr>
          <tr v-if="!rows.length"><td colspan="7" class="loading">Không có khách hàng</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api, { money, dOnly } from '../api'
import Pager from '../components/Pager.vue'
const rows = ref([]); const meta = ref(null); const loading = ref(true); const q = ref(''); const status = ref(''); const page = ref(1)
let t; const debounced = () => { clearTimeout(t); t = setTimeout(reload, 300) }
const reload = () => { page.value = 1; load() }
async function load() { loading.value = true; try { const r = (await api.get('/admin/customers', { params: { q: q.value || undefined, status: status.value || undefined, page: page.value } })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false } }
onMounted(load)
</script>
