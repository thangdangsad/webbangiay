<template>
  <div class="container">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › <NuxtLink to="/gio-hang">Giỏ hàng</NuxtLink> › Thanh toán</div>
    <div class="steps"><span>1. Giỏ hàng</span> › <b>2. Thông tin & thanh toán</b> › <span>3. Hoàn tất</span></div>
    <div v-if="!cart.cart.items.length" class="empty">Giỏ hàng trống. <NuxtLink to="/san-pham">Tiếp tục mua sắm</NuxtLink></div>
    <form v-else class="cols" @submit.prevent="submit">
      <div>
        <div class="box" style="margin-bottom:18px">
          <h3>Thông tin nhận hàng</h3>
          <div v-if="auth.loggedIn && addresses.length" class="fld" style="margin-bottom:14px"><label>Địa chỉ đã lưu</label>
            <select @change="pickAddress(($event.target as HTMLSelectElement).value)"><option value="">— Nhập địa chỉ mới —</option><option v-for="a in addresses" :key="a.id" :value="a.id">{{ a.receiver_name }} · {{ a.street }}</option></select></div>
          <div class="fgrid">
            <div class="fld"><label>Họ và tên *</label><input v-model="f.name" required /><FieldError :msg="fe['receiver.name']" /></div>
            <div class="fld"><label>Số điện thoại *</label><input v-model="f.phone" required inputmode="tel" /><FieldError :msg="fe['receiver.phone']" /></div>
            <div class="fld full"><label>Email</label><input v-model="f.email" type="email" /></div>
            <div class="fld"><label>Tỉnh / Thành phố *</label><select v-model="f.province" required @change="loadDistricts"><option value="">Chọn</option><option v-for="x in provinces" :key="x.code" :value="x.code">{{ x.name }}</option></select></div>
            <div class="fld"><label>Quận / Huyện *</label><select v-model="f.district" required @change="loadWards"><option value="">Chọn</option><option v-for="x in districts" :key="x.code" :value="x.code">{{ x.name }}</option></select></div>
            <div class="fld"><label>Phường / Xã *</label><select v-model="f.ward" required><option value="">Chọn</option><option v-for="x in wards" :key="x.code" :value="x.code">{{ x.name }}</option></select></div>
            <div class="fld"><label>Số nhà, đường *</label><input v-model="f.street" required /></div>
            <div class="fld full"><label>Ghi chú</label><textarea v-model="f.note" placeholder="Gọi trước khi giao..."></textarea></div>
          </div>
        </div>
        <div class="box" style="margin-bottom:18px">
          <h3>Vận chuyển</h3>
          <div class="opt-r on"><span>🚚</span><div><b>Giao hàng tiêu chuẩn</b><small>{{ quote?.eta || '2-4 ngày' }} · {{ quote?.carrier }}</small></div><b class="r">{{ quote ? (quote.fee ? money(quote.fee) : 'Miễn phí') : '—' }}</b></div>
        </div>
        <div class="box">
          <h3>Phương thức thanh toán</h3>
          <label v-for="m in methods" :key="m.code" class="opt-r" :class="{ on: f.method === m.code }" v-show="m.enabled">
            <input v-model="f.method" type="radio" :value="m.code" :disabled="!m.enabled" />
            <div><b>{{ m.name }}</b><small>{{ m.code === 'cod' ? 'Thanh toán bằng tiền mặt khi nhận hàng' : 'Thanh toán qua ví MoMo / quét QR (giữ đơn 15 phút)' }}</small></div>
          </label>
        </div>
      </div>
      <div class="box">
        <h3>Đơn hàng ({{ cart.cart.count }} sản phẩm)</h3>
        <div v-for="it in cart.cart.items" :key="it.variant_id" class="row"><span>{{ it.name }} <small>({{ it.size }}) × {{ it.qty }}</small></span><span>{{ money(it.line_total) }}</span></div>
        <div class="row"><span>Tạm tính</span><span>{{ money(cart.cart.subtotal) }}</span></div>
        <div class="row" v-if="cart.cart.discount_total"><span>Giảm giá ({{ cart.cart.coupon?.code }})</span><span>-{{ money(cart.cart.discount_total) }}</span></div>
        <div class="row"><span>Phí vận chuyển</span><span>{{ quote ? (quote.fee ? money(quote.fee) : 'Miễn phí') : 'Chọn địa chỉ' }}</span></div>
        <div class="row total"><span>Tổng cộng</span><span>{{ money(grand) }}</span></div>
        <div v-if="err" class="errmsg">{{ err }}</div>
        <button class="btn brand block" type="submit" :disabled="busy">{{ busy ? 'Đang xử lý...' : 'Đặt hàng' }}</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
const { $api } = useNuxtApp()
const cart = useCartStore(); const auth = useAuthStore()
const f = reactive({ name: '', phone: '', email: '', province: '', district: '', ward: '', street: '', note: '', method: 'cod' })
const provinces = ref<any[]>([]); const districts = ref<any[]>([]); const wards = ref<any[]>([])
const addresses = ref<any[]>([]); const methods = ref<any[]>([]); const quote = ref<any>(null)
const err = ref(''); const busy = ref(false); const fe = ref<Record<string, string>>({})
const key = newKey()
const grand = computed(() => cart.cart.total + (quote.value?.fee ?? 0))

onMounted(async () => {
  await auth.init(); await cart.fetch()
  provinces.value = (await $api('/locations/provinces')).data
  methods.value = (await $api('/payment-methods')).data
  f.method = methods.value.find((m) => m.enabled)?.code ?? 'cod'
  if (auth.user) { f.name = auth.user.full_name; f.email = auth.user.email ?? ''; f.phone = (auth.user.phone ?? '').replace('+84', '0') }
  if (auth.loggedIn) {
    addresses.value = (await $api('/me/addresses')).data
    const d = addresses.value.find((a) => a.is_default); if (d) await pickAddress(String(d.id))
  }
})
async function loadDistricts() { f.district = f.ward = ''; wards.value = []; districts.value = f.province ? (await $api(`/locations/provinces/${f.province}`)).data : []; refreshQuote() }
async function loadWards() { f.ward = ''; wards.value = f.district ? (await $api(`/locations/provinces/${f.province}/${f.district}`)).data : [] }
async function pickAddress(id: string) {
  const a = addresses.value.find((x) => String(x.id) === id); if (!a) return
  f.name = a.receiver_name; f.phone = a.phone.replace('+84', '0'); f.street = a.street; f.province = a.province_code
  districts.value = (await $api(`/locations/provinces/${a.province_code}`)).data; f.district = a.district_code
  wards.value = (await $api(`/locations/provinces/${a.province_code}/${a.district_code}`)).data; f.ward = a.ward_code
  refreshQuote()
}
async function refreshQuote() { if (f.province) quote.value = (await $api('/shipping/quote', { method: 'POST', body: { province_code: f.province } })).data }
watch(() => cart.cart.subtotal, refreshQuote)

async function submit() {
  err.value = ''; fe.value = {}; busy.value = true
  const nm = (arr: any[], c: string) => arr.find((x) => x.code === c)?.name
  try {
    const r = await $api('/orders', { method: 'POST', headers: { 'Idempotency-Key': key }, body: {
      receiver: { name: f.name, phone: f.phone, email: f.email || null },
      address: { province_code: f.province, district_code: f.district, ward_code: f.ward, street: f.street, province_name: nm(provinces.value, f.province), district_name: nm(districts.value, f.district), ward_name: nm(wards.value, f.ward) },
      shipping_method: 'standard', payment_method: f.method, note: f.note || null } })
    const order = r.data
    cart.forgetToken(); await cart.fetch()
    if (f.method === 'momo') {
      const pay = await $api('/payments/momo/create', { method: 'POST', body: { order_code: order.code, phone: f.phone } })
      window.location.href = pay.data.pay_url
      return
    }
    await navigateTo({ path: `/dat-hang-thanh-cong/${order.code}`, query: { phone: f.phone } })
  } catch (e: any) { err.value = e.message; fe.value = e.fields ?? {}; await cart.fetch() } finally { busy.value = false }
}
useSeoMeta({ title: 'Thanh toán', robots: 'noindex' })
</script>
