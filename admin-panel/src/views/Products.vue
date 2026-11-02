<template>
  <div>
    <div class="ph"><div><h1>Sản phẩm</h1><p>{{ meta?.total ?? 0 }} sản phẩm</p></div>
      <div class="acts"><button class="btn" @click="exportCsv">⬇ Xuất CSV</button><label v-if="auth.can('product:write')" class="btn">⬆ Nhập CSV<input type="file" accept=".csv" hidden @change="importCsv" /></label>
        <router-link v-if="auth.can('product:write')" class="btn pri" to="/products/new">+ Thêm sản phẩm</router-link></div></div>
    <div v-if="importMsg" class="okmsg">{{ importMsg }}</div>
    <div class="card tcard">
      <div class="tbar"><input v-model="f.q" class="f" placeholder="Tìm tên / SKU" @input="debounced" />
        <select v-model="f.status" class="f" @change="reload"><option value="">Mọi trạng thái</option><option value="active">Đang bán</option><option value="hidden">Ẩn</option><option value="draft">Nháp</option></select>
        <select v-model="f.brand_id" class="f" @change="reload"><option value="">Thương hiệu</option><option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option></select>
        <label class="chk"><input v-model="f.low_stock" type="checkbox" @change="reload" /> Tồn thấp</label>
        <span v-if="sel.length && auth.can('product:write')" class="acts" style="margin-left:auto"><button class="btn sm" @click="bulk('show')">Hiện</button><button class="btn sm" @click="bulk('hide')">Ẩn</button><button class="btn sm danger" @click="bulk('delete')">Xóa</button></span></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th><input type="checkbox" :checked="sel.length === rows.length && rows.length" @change="toggleAll" /></th><th>Sản phẩm</th><th>Danh mục</th><th>Thương hiệu</th><th class="r">Giá từ</th><th class="r">Tồn kho</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody><tr v-for="p in rows" :key="p.id">
          <td><input v-model="sel" type="checkbox" :value="p.id" /></td>
          <td><div class="pimg"><div class="th"><img v-if="p.image" :src="p.image.sm" /><span v-else>👟</span></div><div><b>{{ p.name }}</b><small>{{ p.sku }} · {{ p.variant_count }} biến thể</small></div></div></td>
          <td>{{ p.category || '—' }}</td><td>{{ p.brand || '—' }}</td><td class="r">{{ money(p.base_price) }}</td>
          <td class="r"><span :class="{ 'pill p-red': p.available <= 0 }">{{ p.available }}</span><small>{{ p.on_hand }} thực tế</small></td>
          <td><Pill :map="STATUS" :value="p.status" /></td>
          <td class="r"><router-link v-if="auth.can('product:write')" class="btn sm" :to="`/products/${p.id}`">Sửa</router-link></td></tr>
          <tr v-if="!rows.length"><td colspan="8" class="loading">Không có sản phẩm</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api, { money } from '../api'
import { useAuthStore } from '../stores/auth'
import { useUi } from '../stores/ui'
import Pill from '../components/Pill.vue'
import Pager from '../components/Pager.vue'
const auth = useAuthStore(); const ui = useUi()
const STATUS = { active: ['Đang bán', 'p-green'], hidden: ['Ẩn', 'p-gray'], draft: ['Nháp', 'p-yellow'] }
const rows = ref([]); const meta = ref(null); const loading = ref(true); const page = ref(1); const sel = ref([]); const brands = ref([]); const importMsg = ref('')
const f = reactive({ q: '', status: '', brand_id: '', low_stock: false })
let t
const debounced = () => { clearTimeout(t); t = setTimeout(reload, 300) }
function reload() { page.value = 1; load() }
async function load() {
  loading.value = true; sel.value = []
  try { const r = (await api.get('/admin/products', { params: { ...f, low_stock: f.low_stock ? 1 : undefined, page: page.value } })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false }
}
const toggleAll = (e) => (sel.value = e.target.checked ? rows.value.map((r) => r.id) : [])
async function bulk(action) { if (action === 'delete' && !confirm('Xóa các sản phẩm đã chọn? (sản phẩm đã có đơn chỉ bị ẩn)')) return
  await api.post('/admin/products/bulk', { ids: sel.value, action }); ui.notify('Đã cập nhật'); load() }
async function exportCsv() { const r = await api.get('/admin/products/export', { responseType: 'blob' }); const a = document.createElement('a'); a.href = URL.createObjectURL(r.data); a.download = 'products.csv'; a.click() }
async function importCsv(e) { const fd = new FormData(); fd.append('file', e.target.files[0]); e.target.value = ''
  try { const r = (await api.post('/admin/products/import', fd)).data.data; importMsg.value = `Đã cập nhật ${r.updated} SKU.` + (r.errors.length ? ` Lỗi: ${r.errors.join('; ')}` : ''); load() } catch (er) { importMsg.value = er.message } }
onMounted(async () => { brands.value = (await api.get('/admin/brands', { params: { all: 1 } })).data.data; load() })
</script>
