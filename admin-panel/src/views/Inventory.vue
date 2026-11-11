<template>
  <div>
    <div class="tabs mode"><a v-for="t in modes" :key="t[0]" href="#" :class="{ on: mode === t[0] }" @click.prevent="mode = t[0]">{{ t[1] }}</a></div>
    <template v-if="mode === 'stock'">
    <div class="ph"><div><h1>Tồn kho</h1><p>Theo từng SKU · Có thể bán = Trong kho − Đang giữ cho đơn</p></div><div class="acts"><button v-if="auth.can('inventory:write')" class="btn pri" @click="mode = 'receipts'">+ Nhập kho</button></div></div>
    <div class="hint">Trong kho: số đôi thực có. Đang giữ: đã có đơn đặt nhưng chưa giao. Có thể bán: số khách còn đặt được. Nhập hàng mới dùng <b>Nhập kho</b>, kiểm kê lệch dùng <b>Điều chỉnh</b>.</div>
    <div class="card tcard">
      <div class="tbar"><input v-model="q" class="f" placeholder="Tìm SKU / tên sản phẩm" @input="debounced" /><label class="chk"><input v-model="low" type="checkbox" @change="reload" /> Chỉ hàng sắp hết</label></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>SKU</th><th>Sản phẩm</th><th>Màu / Size</th><th class="r">Trong kho</th><th class="r">Đang giữ cho đơn</th><th class="r">Có thể bán</th><th>Mức cảnh báo</th><th></th></tr></thead>
        <tbody><tr v-for="r in rows" :key="r.variant_id"><td><b>{{ r.sku }}</b></td><td>{{ r.product_name }}</td><td>{{ r.color }} / {{ r.size }}</td><td class="r">{{ r.on_hand }}</td><td class="r">{{ r.reserved }}</td>
          <td class="r"><span class="pill" :class="r.available <= 0 ? 'p-red' : r.low ? 'p-yellow' : 'p-green'">{{ r.available }}</span></td><td>{{ r.low_stock_threshold }}</td>
          <td class="r" style="white-space:nowrap"><template v-if="auth.can('inventory:write')"><button class="btn sm" @click="adj = { ...r, delta: 0, reason: '' }">Điều chỉnh</button> </template><router-link class="btn sm" :to="`/inventory/movements?variant_id=${r.variant_id}`">Lịch sử</router-link></td></tr>
          <tr v-if="!rows.length"><td colspan="8" class="loading">Không có dữ liệu</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
    <Modal v-if="adj" title="Điều chỉnh kho" @close="adj = null">
      <p style="margin-bottom:12px"><b>{{ adj.sku }}</b> · {{ adj.product_name }} · trong kho hiện tại {{ adj.on_hand }}</p>
      <form @submit.prevent="doAdjust"><div class="fld"><label>Số lượng thay đổi (+/−)</label><input v-model.number="adj.delta" class="f" type="number" required /></div>
        <div class="fld"><label>Lý do *</label><input v-model="adj.reason" class="f" required placeholder="Kiểm kê lệch, hàng lỗi..." /></div>
        <div v-if="err" class="errmsg">{{ err }}</div><div class="acts" style="justify-content:flex-end"><button type="button" class="btn" @click="adj = null">Hủy</button><button class="btn pri">Xác nhận</button></div></form>
    </Modal>
    </template>
    <StockReceipt v-else />
  </div>
</template>

<script setup>
import StockReceipt from './StockReceipt.vue'
import { useRoute } from 'vue-router'
import { onMounted, ref } from 'vue'
import api from '../api'
import { useAuthStore } from '../stores/auth'
import { useUi } from '../stores/ui'
import Pager from '../components/Pager.vue'
import Modal from '../components/Modal.vue'
const auth = useAuthStore(); const ui = useUi()
const rows = ref([]); const meta = ref(null); const loading = ref(true); const q = ref(''); const low = ref(false); const page = ref(1); const adj = ref(null); const err = ref('')
let t; const debounced = () => { clearTimeout(t); t = setTimeout(reload, 300) }
const reload = () => { page.value = 1; load() }
async function load() { loading.value = true; try { const r = (await api.get('/admin/inventory', { params: { q: q.value || undefined, low: low.value ? 1 : undefined, page: page.value } })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false } }
async function doAdjust() { err.value = ''; try { await api.post('/admin/inventory/adjust', { variant_id: adj.value.variant_id, delta: adj.value.delta, reason: adj.value.reason }); adj.value = null; ui.notify('Đã điều chỉnh'); load() } catch (e) { err.value = e.message } }
const route = useRoute()
const modes = [['stock', 'Tồn kho'], ['receipts', 'Nhập kho']]
const mode = ref(route.query.tab === 'receipts' ? 'receipts' : 'stock')
onMounted(load)
</script>
