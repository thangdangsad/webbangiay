<template>
  <div>
    <div class="ph"><div><h1>Phiếu nhập hàng</h1><p>Nhập kho theo SKU, mỗi lần nhập sinh lịch sử biến động</p></div></div>
    <div class="cols side">
      <div>
        <div v-if="auth.can('inventory:write')" class="card"><h3>Tạo phiếu nhập</h3>
          <div class="g2"><div class="fld"><label>Nhà cung cấp</label><input v-model="f.supplier" class="f" /></div><div class="fld"><label>Ghi chú</label><input v-model="f.note" class="f" /></div></div>
          <div class="fld"><label>Thêm SKU</label><input v-model="search" class="f" placeholder="Gõ SKU hoặc tên sản phẩm..." @input="find" />
            <div v-if="results.length" class="card" style="padding:6px;margin-top:4px"><div v-for="r in results" :key="r.variant_id" style="padding:6px 8px;cursor:pointer" @click="add(r)">{{ r.sku }} · {{ r.product_name }} <small class="muted">({{ r.color }}/{{ r.size }})</small></div></div></div>
          <table v-if="f.items.length" class="tb"><thead><tr><th>SKU</th><th>Sản phẩm</th><th>SL</th><th>Giá vốn</th><th></th></tr></thead>
            <tbody><tr v-for="(i, n) in f.items" :key="i.variant_id"><td>{{ i.sku }}</td><td>{{ i.name }}</td><td><input v-model.number="i.qty" class="f" type="number" min="1" style="width:80px" /></td><td><input v-model.number="i.unit_cost" class="f" type="number" min="0" style="width:120px" /></td><td><button class="btn sm danger" @click="f.items.splice(n, 1)">✕</button></td></tr></tbody></table>
          <div v-if="err" class="errmsg" style="margin-top:10px">{{ err }}</div>
          <button class="btn pri" style="margin-top:12px" :disabled="!f.items.length" @click="submit">Xác nhận nhập kho</button></div>
      </div>
      <div class="card tcard"><h3 style="padding:16px 16px 0">Phiếu gần đây</h3>
        <table class="tb"><thead><tr><th>Mã</th><th>NCC</th><th class="r">SL</th></tr></thead><tbody><tr v-for="r in receipts" :key="r.id"><td><b>{{ r.code }}</b><small>{{ dt(r.created_at) }}</small></td><td>{{ r.supplier || '—' }}</td><td class="r">{{ r.total_qty }}</td></tr><tr v-if="!receipts.length"><td colspan="3" class="loading">Chưa có phiếu</td></tr></tbody></table></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api, { dt } from '../api'
import { useAuthStore } from '../stores/auth'
import { useUi } from '../stores/ui'
const auth = useAuthStore(); const ui = useUi()
const f = reactive({ supplier: '', note: '', items: [] }); const search = ref(''); const results = ref([]); const receipts = ref([]); const err = ref('')
let t
function find() { clearTimeout(t); t = setTimeout(async () => { results.value = search.value.length < 2 ? [] : (await api.get('/admin/inventory', { params: { q: search.value, limit: 8 } })).data.data }, 250) }
function add(r) { if (!f.items.some((i) => i.variant_id === r.variant_id)) f.items.push({ variant_id: r.variant_id, sku: r.sku, name: `${r.product_name} (${r.color}/${r.size})`, qty: 1, unit_cost: 0 }); results.value = []; search.value = '' }
async function loadReceipts() { receipts.value = (await api.get('/admin/inventory/receipts')).data.data }
async function submit() { err.value = ''; try { const r = (await api.post('/admin/inventory/receipts', { supplier: f.supplier || null, note: f.note || null, items: f.items.map(({ variant_id, qty, unit_cost }) => ({ variant_id, qty, unit_cost })) })).data.data; ui.notify(`Đã nhập kho ${r.code}`); f.items = []; f.supplier = f.note = ''; loadReceipts() } catch (e) { err.value = e.message } }
onMounted(loadReceipts)
</script>
