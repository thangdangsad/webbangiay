<template>
  <div v-if="o">
    <NuxtLink to="/tai-khoan/don-hang" style="color:var(--muted)">← Đơn hàng</NuxtLink>
    <div style="display:flex;justify-content:space-between;align-items:center;margin:10px 0 16px"><h2>Đơn {{ o.code }}</h2><span class="status" :class="statusClass(o.status)">{{ statusLabel(o.status) }}</span></div>
    <div class="box" style="margin-bottom:16px"><h3>Tiến trình</h3><div class="tl-v"><div v-for="(t, i) in o.timeline" :key="i" class="on"><b>{{ t.label }}</b><small>{{ dateTime(t.at) }}<template v-if="t.note"> · {{ t.note }}</template></small></div></div>
      <div v-if="o.shipment?.tracking_code" class="callout" style="color:#1e40af;background:#eff6ff;border-color:#bfdbfe">Vận đơn {{ o.shipment.carrier }}: <b>{{ o.shipment.tracking_code }}</b></div></div>
    <div class="box" style="margin-bottom:16px"><h3>Sản phẩm</h3>
      <div v-for="i in o.items" :key="i.id" class="ord"><div class="b"><div class="img"><img v-if="i.image" :src="i.image.sm" /><span v-else>👟</span></div><div style="flex:1"><b>{{ i.name }}</b><div style="color:var(--muted);font-size:13px">Màu {{ i.color }} · Size {{ i.size }} × {{ i.qty }}</div></div><b>{{ money(i.line_total) }}</b></div></div>
      <div class="row"><span>Tạm tính</span><span>{{ money(o.subtotal) }}</span></div><div class="row"><span>Giảm giá</span><span>-{{ money(o.discount_total) }}</span></div>
      <div class="row"><span>Phí vận chuyển</span><span>{{ money(o.shipping_fee) }}</span></div><div class="row total"><span>Tổng</span><span>{{ money(o.grand_total) }}</span></div></div>
    <div class="box" style="margin-bottom:16px"><h3>Giao hàng & thanh toán</h3><dl class="kv"><dt>Người nhận</dt><dd>{{ o.customer_name }} · {{ o.phone }}</dd><dt>Địa chỉ</dt><dd>{{ addr }}</dd>
      <dt>Thanh toán</dt><dd>{{ payLabel(o.payment_method) }} · {{ o.payment_status === 'paid' ? 'Đã thanh toán' : o.payment_status === 'refunded' ? 'Đã hoàn tiền' : 'Chưa thanh toán' }}</dd></dl></div>
    <div v-if="err" class="errmsg">{{ err }}</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button v-if="o.can_cancel" class="btn ghost" @click="cancel">Hủy đơn</button>
      <button v-if="o.can_return" class="btn" @click="ret.open = !ret.open">Yêu cầu đổi/trả</button>
      <button class="btn brand" @click="rebuy">Mua lại</button>
    </div>
    <form v-if="ret.open" class="box" style="margin-top:16px" @submit.prevent="sendReturn">
      <h3>Yêu cầu đổi / trả</h3>
      <label v-for="i in o.items" :key="i.id" class="opt-r"><input v-model="ret.sel" type="checkbox" :value="i.id" /><div>{{ i.name }} (size {{ i.size }})<small>SL {{ i.qty }}</small></div></label>
      <div class="fld"><label>Lý do</label><textarea v-model="ret.reason" required></textarea></div>
      <div class="fld"><label>Ảnh minh chứng (tối đa 5)</label><input type="file" accept="image/*" multiple @change="pickImages" />
        <div style="display:flex;gap:8px;margin-top:8px"><img v-for="im in ret.images" :key="im.path" :src="im.url" style="height:56px;border-radius:6px" /></div></div>
      <button class="btn brand">Gửi yêu cầu</button>
    </form>
    <div v-if="o.returns.length" class="box" style="margin-top:16px"><h3>Yêu cầu đổi/trả</h3><div v-for="r in o.returns" :key="r.code" class="row"><span>{{ r.code }}</span><b>{{ retLabel[r.status] || r.status }}</b></div></div>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth', layout: 'account' })
const { $api } = useNuxtApp(); const route = useRoute(); const cart = useCartStore(); const ui = useUiStore()
const code = String(route.params.code)
const { data: o, refresh } = await useAsyncData(`order-${code}`, async () => (await $api(`/me/orders/${code}`)).data)
const addr = computed(() => { const a = o.value?.ship_address ?? {}; return [a.street, a.ward_name, a.district_name, a.province_name].filter(Boolean).join(', ') })
const err = ref(''); const ret = reactive({ open: false, sel: [] as number[], reason: '', images: [] as any[] })
async function pickImages(e: Event) {
  for (const f of Array.from((e.target as HTMLInputElement).files ?? []).slice(0, 5 - ret.images.length)) {
    const fd = new FormData(); fd.append('file', f)
    try { ret.images.push((await $api('/me/uploads', { method: 'POST', body: fd })).data) } catch (er: any) { err.value = er.message }
  }
}
const retLabel: Record<string, string> = { requested: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối', received: 'Đã nhận hàng', refunded: 'Đã hoàn tiền' }
async function cancel() { if (!confirm('Bạn chắc chắn muốn hủy đơn này?')) return; err.value = ''; try { await $api(`/me/orders/${code}/cancel`, { method: 'POST', body: {} }); await refresh() } catch (e: any) { err.value = e.message } }
async function sendReturn() { err.value = ''
  try { await $api(`/me/orders/${code}/returns`, { method: 'POST', body: { reason: ret.reason, images: ret.images.map((i: any) => i.path), items: o.value.items.filter((i: any) => ret.sel.includes(i.id)).map((i: any) => ({ order_item_id: i.id, qty: i.qty })) } }); ret.open = false; ui.notify('Đã gửi yêu cầu đổi/trả'); await refresh() } catch (e: any) { err.value = e.message } }
async function rebuy() { for (const i of o.value.items) { try { await cart.add(i.variant_id, i.qty) } catch {} } await navigateTo('/gio-hang') }
useSeoMeta({ title: `Đơn ${code}`, robots: 'noindex' })
</script>
