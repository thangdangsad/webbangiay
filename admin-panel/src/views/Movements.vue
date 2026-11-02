<template>
  <div>
    <div class="ph"><div><h1>Lịch sử biến động kho</h1><p>Ai · khi nào · lý do · tham chiếu đơn</p></div></div>
    <div class="card tcard">
      <div class="tbar"><input v-model="q" class="f" placeholder="SKU / tên sản phẩm" @input="debounced" />
        <select v-model="type" class="f" @change="reload"><option value="">Mọi loại</option><option v-for="(l, k) in TYPES" :key="k" :value="k">{{ l }}</option></select></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>Thời gian</th><th>SKU</th><th>Sản phẩm</th><th>Loại</th><th class="r">Thay đổi</th><th>Lý do</th><th>Người thực hiện</th></tr></thead>
        <tbody><tr v-for="m in rows" :key="m.id"><td>{{ dt(m.created_at) }}</td><td><b>{{ m.sku }}</b></td><td>{{ m.product_name }}<small>{{ m.color }} / {{ m.size }}</small></td><td><span class="pill p-gray">{{ TYPES[m.type] || m.type }}</span></td>
          <td class="r"><b :class="m.qty_delta > 0 ? 'up' : 'down'">{{ m.qty_delta > 0 ? '+' : '' }}{{ m.qty_delta }}</b></td><td>{{ m.reason || '—' }}<small v-if="m.ref_type === 'order'">Đơn #{{ m.ref_id }}</small></td><td>{{ m.staff }}</td></tr>
          <tr v-if="!rows.length"><td colspan="7" class="loading">Không có dữ liệu</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { dt } from '../api'
import Pager from '../components/Pager.vue'
const route = useRoute()
const TYPES = { receipt: 'Nhập kho', sale: 'Xuất bán', reserve: 'Giữ hàng', release: 'Nhả giữ', return: 'Hoàn kho', adjust: 'Điều chỉnh' }
const rows = ref([]); const meta = ref(null); const loading = ref(true); const q = ref(''); const type = ref(''); const page = ref(1)
let t; const debounced = () => { clearTimeout(t); t = setTimeout(reload, 300) }
const reload = () => { page.value = 1; load() }
async function load() { loading.value = true; try { const r = (await api.get('/admin/inventory/movements', { params: { q: q.value || undefined, type: type.value || undefined, variant_id: route.query.variant_id, page: page.value } })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false } }
onMounted(load)
</script>
