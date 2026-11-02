<template>
  <div v-if="o">
    <div class="ph"><div class="crumb"><router-link to="/orders">Đơn hàng</router-link> › {{ o.code }}<h1>{{ o.code }} <Pill :map="ORDER_STATUS" :value="o.status" /></h1><p>Đặt lúc {{ dt(o.placed_at) }}</p></div>
      <div class="acts"><button class="btn" @click="print">🖨 In phiếu</button>
        <button v-for="n in nextActions" :key="n.to" class="btn" :class="n.cls" :disabled="busy" @click="go(n)">{{ n.label }}</button></div></div>
    <div v-if="err" class="errmsg">{{ err }}</div>
    <div class="card"><div class="flow"><span v-for="s in FLOW" :key="s" :class="{ done: idx(s) < idx(o.status), cur: s === o.status }">{{ ORDER_STATUS[s][0] }}</span></div>
      <p v-if="o.status === 'cancelled'" class="muted">Đơn đã hủy.</p></div>
    <div class="cols side">
      <div>
        <div class="card tcard"><h3 style="padding:16px 16px 0">Sản phẩm</h3>
          <table class="tb"><thead><tr><th>Sản phẩm</th><th class="r">Đơn giá</th><th class="r">SL</th><th class="r">Thành tiền</th></tr></thead>
            <tbody><tr v-for="i in o.items" :key="i.id"><td><div class="pimg"><div class="th"><img v-if="i.image" :src="i.image.sm" /><span v-else>👟</span></div><div><b>{{ i.name }}</b><small>{{ i.sku }} · {{ i.color }} · size {{ i.size }}</small></div></div></td><td class="r">{{ money(i.unit_price) }}</td><td class="r">{{ i.qty }}</td><td class="r"><b>{{ money(i.line_total) }}</b></td></tr></tbody></table>
          <div class="sum" style="padding:16px"><div class="row"><span>Tạm tính</span><span>{{ money(o.subtotal) }}</span></div><div class="row"><span>Giảm giá {{ o.coupon_code ? `(${o.coupon_code})` : '' }}</span><span>-{{ money(o.discount_total) }}</span></div><div class="row"><span>Phí vận chuyển</span><span>{{ money(o.shipping_fee) }}</span></div><div class="row t"><span>Tổng cộng</span><span>{{ money(o.grand_total) }}</span></div></div></div>
        <div class="card"><h3>Timeline & ghi chú nội bộ</h3>
          <div class="tl"><div v-for="(t, i) in o.timeline" :key="i"><b>{{ t.label }}</b><small>{{ dt(t.at) }} · {{ actor(t.actor_type) }}<template v-if="t.note"> · {{ t.note }}</template></small></div></div>
          <div class="fld"><label>Ghi chú nội bộ</label><textarea v-model="note" class="f" style="height:80px"></textarea></div><button class="btn sm" @click="saveNote">Lưu ghi chú</button></div>
      </div>
      <div>
        <div class="card"><h3>Khách hàng</h3><b>{{ o.customer_name }}</b><br />{{ o.phone }}<br /><span class="muted">{{ o.email }}</span>
          <p style="margin-top:8px">{{ addr }}</p><p v-if="o.note" class="muted">Ghi chú: {{ o.note }}</p><router-link v-if="o.user_id && auth.can('customer:read')" class="lnk" :to="`/customers/${o.user_id}`">Xem hồ sơ khách →</router-link></div>
        <div class="card"><h3>Thanh toán</h3><p><span class="pill p-gray">{{ payMethod(o.payment_method) }}</span> <Pill :map="PAY_STATUS" :value="o.payment_status" /></p>
          <div v-for="p in o.payments" :key="p.id" class="muted" style="font-size:12px;margin-top:6px">{{ p.method.toUpperCase() }} · {{ p.status }} · {{ money(p.amount) }}<template v-if="p.provider_txn_id"> · txn {{ p.provider_txn_id }}</template></div>
          <div v-if="auth.can('payment:manage')" class="acts" style="margin-top:12px;flex-wrap:wrap">
            <button v-if="o.payment_method === 'momo' && o.payment_status !== 'paid'" class="btn sm" @click="momo('query')">Truy vấn MoMo</button>
            <button v-if="o.payment_method === 'momo' && o.payment_status === 'paid'" class="btn sm danger" @click="momo('refund')">Hoàn tiền MoMo</button>
            <button v-if="o.payment_status !== 'paid' && o.status !== 'cancelled'" class="btn sm" @click="record">Ghi nhận đã thu</button></div></div>
        <div class="card"><h3>Vận chuyển</h3><template v-if="o.shipment"><p>{{ o.shipment.carrier || '—' }} · <b>{{ o.shipment.tracking_code || 'chưa có mã vận đơn' }}</b></p></template><p v-else class="muted">Chưa tạo vận đơn.</p>
          <div v-if="auth.can('order:fulfil') && ['packing', 'shipping'].includes(o.status)" class="fld" style="margin-top:10px"><input v-model="ship.carrier" class="f" placeholder="Đơn vị vận chuyển" style="margin-bottom:6px" /><input v-model="ship.tracking_code" class="f" placeholder="Mã vận đơn" /><button class="btn sm" style="margin-top:8px" @click="saveShip">Lưu vận đơn</button></div></div>
      </div>
    </div>
  </div>
  <div v-else class="loading">Đang tải...</div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { money, dt, ORDER_STATUS, PAY_STATUS, payMethod } from '../api'
import { useAuthStore } from '../stores/auth'
import { useUi } from '../stores/ui'
import Pill from '../components/Pill.vue'
const route = useRoute(); const auth = useAuthStore(); const ui = useUi()
const o = ref(null); const err = ref(''); const busy = ref(false); const note = ref(''); const ship = ref({ carrier: '', tracking_code: '' })
const FLOW = ['pending', 'confirmed', 'packing', 'shipping', 'completed']
const idx = (s) => FLOW.indexOf(s)
const actor = (t) => ({ customer: 'Khách', staff: 'Nhân viên', system: 'Hệ thống' })[t] || t
const addr = computed(() => { const a = o.value?.ship_address || {}; return [a.street, a.ward_name, a.district_name, a.province_name].filter(Boolean).join(', ') })
const PERM = { confirmed: 'order:confirm', cancelled: 'order:cancel', packing: 'order:fulfil', shipping: 'order:fulfil', completed: 'order:complete' }
const LABEL = { confirmed: ['✓ Xác nhận đơn', 'pri'], packing: ['📦 Bắt đầu đóng gói', 'pri'], shipping: ['🚚 Giao cho vận chuyển', 'pri'], completed: ['✔ Hoàn thành', 'pri'], cancelled: ['✕ Hủy đơn', 'danger'] }
const nextActions = computed(() => (o.value?.allowed_next || []).filter((n) => LABEL[n] && auth.can(PERM[n])).map((n) => ({ to: n, label: LABEL[n][0], cls: LABEL[n][1] })))
function set(d) { o.value = d; note.value = d.internal_note || ''; ship.value = { carrier: d.shipment?.carrier || '', tracking_code: d.shipment?.tracking_code || '' } }
async function load() { set((await api.get(`/admin/orders/${route.params.id}`)).data.data) }
async function go(n) {
  if (n.to === 'cancelled' && !confirm('Hủy đơn hàng này? Hàng giữ sẽ được hoàn lại kho.')) return
  err.value = ''; busy.value = true
  try { const body = { to: n.to }; if (n.to === 'shipping') Object.assign(body, ship.value); set((await api.post(`/admin/orders/${o.value.id}/transition`, body)).data.data); ui.notify('Đã cập nhật trạng thái') } catch (e) { err.value = e.message } finally { busy.value = false }
}
async function saveNote() { await api.patch(`/admin/orders/${o.value.id}/internal-note`, { internal_note: note.value }); ui.notify('Đã lưu ghi chú') }
async function saveShip() { try { await api.post(`/admin/orders/${o.value.id}/shipment`, ship.value); ui.notify('Đã lưu vận đơn'); load() } catch (e) { err.value = e.message } }
async function momo(a) { err.value = ''; try { const r = (await api.post(`/admin/orders/${o.value.id}/payments/momo/${a}`, {})).data.data; set(r.order || r); ui.notify(a === 'query' ? (r.paid ? 'MoMo: đã thanh toán' : 'MoMo: chưa thanh toán') : 'Đã hoàn tiền') } catch (e) { err.value = e.message } }
async function record() { if (!confirm('Ghi nhận đã thu đủ tiền cho đơn này?')) return; try { set((await api.post(`/admin/orders/${o.value.id}/payments`, { amount: o.value.grand_total })).data.data) } catch (e) { err.value = e.message } }
async function print() { const r = await api.get(`/admin/orders/${o.value.id}/print`, { responseType: 'blob' }); const w = window.open(URL.createObjectURL(r.data), '_blank'); w && setTimeout(() => w.print(), 600) }
onMounted(load)
</script>
