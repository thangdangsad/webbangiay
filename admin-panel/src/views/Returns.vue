<template>
  <div>
    <div class="ph"><div><h1>Đổi trả / hoàn tiền</h1><p>requested → approved/rejected → received → refunded</p></div></div>
    <div class="card tcard">
      <div class="tabs"><a v-for="t in tabs" :key="t[0]" href="#" :class="{ on: status === t[0] }" @click.prevent="status = t[0]; load()">{{ t[1] }}</a></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>Mã</th><th>Đơn hàng</th><th>Sản phẩm</th><th>Lý do</th><th class="r">Hoàn tiền</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody><tr v-for="r in rows" :key="r.id"><td><b>{{ r.code }}</b><small>{{ dt(r.created_at) }}</small></td><td><router-link class="lnk" :to="`/orders/${r.order.id}`">{{ r.order.code }}</router-link><small>{{ r.order.customer_name }} · {{ payMethod(r.order.payment_method) }}</small></td>
          <td><div v-for="i in r.items" :key="i.id">{{ i.name }} <small>size {{ i.size }} × {{ i.qty }}<template v-if="r.status === 'approved'"> · <label><input v-model="restock[i.id]" type="checkbox" :true-value="true" /> nhập lại kho</label></template></small></div></td>
          <td style="max-width:240px">{{ r.reason }}<small v-if="r.staff_note">Ghi chú: {{ r.staff_note }}</small></td><td class="r"><b>{{ money(r.refund_amount) }}</b></td><td><Pill :map="ST" :value="r.status" /></td>
          <td class="r" style="white-space:nowrap"><template v-if="r.status === 'requested'"><button class="btn sm pri" @click="act(r, 'approve')">Duyệt</button> <button class="btn sm danger" @click="act(r, 'reject')">Từ chối</button></template>
            <button v-else-if="r.status === 'approved'" class="btn sm pri" @click="act(r, 'receive', { restock: restockMap(r) })">Đã nhận hàng</button>
            <button v-else-if="r.status === 'received'" class="btn sm pri" @click="act(r, 'refund')">Hoàn tiền{{ r.order.payment_method === 'momo' && r.order.payment_status === 'paid' ? ' (MoMo)' : '' }}</button></td></tr>
          <tr v-if="!rows.length"><td colspan="7" class="loading">Không có yêu cầu</td></tr></tbody></table>
    </div>
    <div v-if="err" class="errmsg">{{ err }}</div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api, { money, dt, payMethod } from '../api'
import { useUi } from '../stores/ui'
import Pill from '../components/Pill.vue'
const ui = useUi()
const ST = { requested: ['Chờ duyệt', 'p-yellow'], approved: ['Đã duyệt', 'p-blue'], rejected: ['Từ chối', 'p-red'], received: ['Đã nhận hàng', 'p-purple'], refunded: ['Đã hoàn tiền', 'p-green'] }
const tabs = [['', 'Tất cả'], ['requested', 'Chờ duyệt'], ['approved', 'Đã duyệt'], ['received', 'Đã nhận hàng'], ['refunded', 'Đã hoàn tiền'], ['rejected', 'Từ chối']]
const rows = ref([]); const status = ref(''); const loading = ref(true); const err = ref(''); const restock = reactive({})
const restockMap = (r) => Object.fromEntries(r.items.map((i) => [i.id, restock[i.id] ?? true]))
async function load() { loading.value = true; try { rows.value = (await api.get('/admin/returns', { params: { status: status.value || undefined } })).data.data; rows.value.forEach((r) => r.items.forEach((i) => (restock[i.id] ??= i.restock))) } finally { loading.value = false } }
async function act(r, a, body = {}) {
  err.value = ''; let note = null
  if (a === 'reject') { note = prompt('Lý do từ chối?'); if (!note) return }
  if (a === 'refund' && !confirm(`Xác nhận hoàn ${money(r.refund_amount)}?`)) return
  try { await api.post(`/admin/returns/${r.id}/${a}`, { note, ...body }); ui.notify('Đã cập nhật'); load() } catch (e) { err.value = e.message }
}
onMounted(load)
</script>
