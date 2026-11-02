<template>
  <div class="container">
    <div v-if="!o" class="center"><p v-if="notFound" class="errmsg">Không tìm thấy đơn hàng.</p><p v-else>Đang tải...</p></div>
    <div v-else class="center">
      <template v-if="o.payment_method === 'momo' && o.payment_status !== 'paid' && o.status !== 'cancelled'">
        <div class="okc" style="background:#fdf2f8;color:#a50064">⏳</div>
        <h1>Chờ thanh toán MoMo</h1>
        <p style="color:var(--muted)">Đơn <b>{{ o.code }}</b> sẽ tự hủy sau {{ left }}. Trang này tự cập nhật khi thanh toán xong.</p>
        <div class="qrbox"><b>{{ money(o.grand_total) }}</b><br /><button class="btn brand" style="margin-top:10px" :disabled="paying" @click="pay">Thanh toán bằng MoMo</button>
          <div style="margin-top:8px"><a href="#" @click.prevent="switchCod">Đổi sang COD</a></div></div>
      </template>
      <template v-else-if="o.status === 'cancelled'"><div class="okc" style="background:#fee2e2;color:#991b1b">✕</div><h1>Đơn hàng đã bị hủy</h1></template>
      <template v-else><div class="okc">✓</div><h1>Đặt hàng thành công!</h1><p style="color:var(--muted)">Cảm ơn bạn. Chúng tôi sẽ sớm xác nhận đơn hàng.</p></template>

      <div class="box" style="margin:22px 0">
        <dl class="kv"><dt>Mã đơn hàng</dt><dd>{{ o.code }}</dd><dt>Tổng thanh toán</dt><dd>{{ money(o.grand_total) }}</dd>
          <dt>Phương thức</dt><dd>{{ payLabel(o.payment_method) }} · {{ o.payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</dd>
          <dt>Giao đến</dt><dd>{{ o.customer_name }}, {{ addr }}</dd></dl>
        <hr style="border:0;border-top:1px solid var(--line);margin:14px 0" />
        <div v-for="i in o.items" :key="i.id" class="row"><span>{{ i.name }} ({{ i.size }}) × {{ i.qty }}</span><span>{{ money(i.line_total) }}</span></div>
      </div>
      <NuxtLink class="btn brand" :to="auth.loggedIn ? `/tai-khoan/don-hang/${o.code}` : `/tra-cuu-don-hang?code=${o.code}`">Theo dõi đơn hàng</NuxtLink>
      <NuxtLink class="btn ghost" style="margin-left:8px" to="/san-pham">Tiếp tục mua sắm</NuxtLink>
      <div v-if="!auth.loggedIn" class="callout">Tạo tài khoản để theo dõi đơn dễ hơn: <NuxtLink to="/dang-ky">Đăng ký ngay</NuxtLink></div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute(); const { $api } = useNuxtApp(); const auth = useAuthStore()
const code = String(route.params.code); const phone = String(route.query.phone ?? '')
const o = ref<any>(null); const notFound = ref(false); const paying = ref(false); const now = ref(Date.now())
const addr = computed(() => { const a = o.value?.ship_address ?? {}; return [a.street, a.ward_name, a.district_name, a.province_name].filter(Boolean).join(', ') })
const left = computed(() => { const e = o.value?.payment?.expires_at; if (!e) return '15 phút'; const s = Math.max(0, Math.floor((new Date(e).getTime() - now.value) / 1000)); return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}` })
let timer: any
async function load() {
  try { o.value = (await $api(`/orders/${code}`, { query: { phone } })).data } catch { notFound.value = true }
  if (o.value && !(o.value.payment_status === 'paid' || o.value.status === 'cancelled' || o.value.payment_method === 'cod')) return
  clearInterval(timer)
}
async function pay() { paying.value = true; try { window.location.href = (await $api('/payments/momo/create', { method: 'POST', body: { order_code: code, phone } })).data.pay_url } finally { paying.value = false } }
async function switchCod() { await $api(`/orders/${code}/switch-cod`, { method: 'POST', body: { phone } }); await load() }
onMounted(async () => {
  await auth.init(); await load()
  timer = setInterval(() => { now.value = Date.now(); if (Math.floor(now.value / 1000) % 3 === 0) load() }, 1000)
})
onBeforeUnmount(() => clearInterval(timer))
useSeoMeta({ title: 'Đặt hàng thành công', robots: 'noindex' })
</script>
