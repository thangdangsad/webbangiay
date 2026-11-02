<template>
  <div>
    <div class="ph"><div><h1>Đánh giá sản phẩm</h1><p>Duyệt trước khi hiển thị trên storefront</p></div></div>
    <div class="card tcard">
      <div class="tabs"><a v-for="t in tabs" :key="t[0]" href="#" :class="{ on: status === t[0] }" @click.prevent="status = t[0]; load()">{{ t[1] }}</a></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>Sản phẩm</th><th>Khách</th><th>Điểm</th><th>Nội dung</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody><tr v-for="r in rows" :key="r.id"><td>{{ r.product }}<small>{{ dt(r.created_at) }}</small></td><td>{{ r.author }}<small>{{ r.email }}</small></td><td>{{ '★'.repeat(r.rating) }}</td><td style="max-width:320px">{{ r.content || '—' }}</td>
          <td><span class="pill" :class="r.status === 'approved' ? 'p-green' : 'p-yellow'">{{ r.status === 'approved' ? 'Đã duyệt' : 'Chờ duyệt' }}</span></td>
          <td class="r" style="white-space:nowrap"><button v-if="r.status === 'pending'" class="btn sm pri" @click="set(r, 'approved')">Duyệt</button><button v-else class="btn sm" @click="set(r, 'pending')">Ẩn</button> <button class="btn sm danger" @click="del(r)">Xóa</button></td></tr>
          <tr v-if="!rows.length"><td colspan="6" class="loading">Không có đánh giá</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api, { dt } from '../api'
import { useUi } from '../stores/ui'
import Pager from '../components/Pager.vue'
const ui = useUi(); const tabs = [['pending', 'Chờ duyệt'], ['approved', 'Đã duyệt'], ['', 'Tất cả']]
const rows = ref([]); const meta = ref(null); const loading = ref(true); const status = ref('pending'); const page = ref(1)
async function load() { loading.value = true; try { const r = (await api.get('/admin/reviews', { params: { status: status.value || undefined, page: page.value } })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false } }
async function set(r, st) { await api.patch(`/admin/reviews/${r.id}`, { status: st }); ui.notify('Đã cập nhật'); load() }
async function del(r) { if (confirm('Xóa đánh giá này?')) { await api.delete(`/admin/reviews/${r.id}`); load() } }
onMounted(load)
</script>
