<template>
  <div v-if="s">
    <div class="ph"><div><h1>Cài đặt cửa hàng & thanh toán</h1></div><div class="acts"><button class="btn pri" :disabled="busy" @click="save">Lưu thay đổi</button></div></div>
    <div v-if="err" class="errmsg">{{ err }}</div>
    <div class="cols eq">
      <div class="card"><h3>Thông tin cửa hàng</h3>
        <div class="fld"><label>Tên cửa hàng</label><input v-model="s.store.name" class="f" /></div><div class="g2"><div class="fld"><label>Hotline</label><input v-model="s.store.phone" class="f" /></div><div class="fld"><label>Email</label><input v-model="s.store.email" class="f" /></div></div>
        <div class="fld"><label>Địa chỉ</label><input v-model="s.store.address" class="f" /></div>
        <div class="g2"><div class="fld"><label>Logo</label><img v-if="logoUrl" :src="logoUrl" style="height:50px;display:block;margin-bottom:6px" /><ImageUpload type="logo" label="Chọn logo" @uploaded="(u) => { s.store.logo_path = u.path; logoUrl = u.url }" /></div>
          <div class="fld"><label>Favicon</label><img v-if="favUrl" :src="favUrl" style="height:32px;display:block;margin-bottom:6px" /><ImageUpload type="logo" label="Chọn favicon" @uploaded="(u) => { s.store.favicon_path = u.path; favUrl = u.url }" /></div></div></div>
      <div class="card"><h3>Thanh toán</h3>
        <label class="chk" style="margin-bottom:12px"><input v-model="s.payment.cod_enabled" type="checkbox" /> <b>COD</b> – thanh toán khi nhận hàng</label>
        <label class="chk" style="margin-bottom:12px"><input v-model="s.payment.momo_enabled" type="checkbox" /> <b>Ví MoMo</b></label>
        <div class="fld"><label>Hạn mức đơn MoMo (đ) – vượt mức chỉ cho COD</label><input v-model.number="s.payment.momo_max_amount" class="f" type="number" /></div>
        <div class="warnb">MoMo: partnerCode <b>{{ s.momo.partner_code || '—' }}</b> · endpoint {{ s.momo.endpoint }} · chế độ <b>{{ s.momo.fake ? 'GIẢ LẬP (MOMO_FAKE=true)' : 'thật' }}</b>. Khóa bí mật cấu hình trong .env của API, không lưu ở trang này.</div></div>
    </div>
    <div class="card"><h3>Đơn hàng & đổi trả</h3><div class="g3"><div class="fld"><label>Số ngày cho phép đổi/trả</label><input v-model.number="s.order.return_days" class="f" type="number" /></div><div class="fld"><label>Giữ đơn MoMo chưa thanh toán (phút)</label><input v-model.number="s.order.momo_expire_minutes" class="f" type="number" /></div></div></div>
    <div class="card" v-if="auth.user"><h3>Bảo mật tài khoản của tôi</h3>
      <h4 style="margin-bottom:8px">Phiên đăng nhập</h4>
      <table class="tb"><thead><tr><th>Thiết bị</th><th>IP</th><th>Lần cuối</th><th></th></tr></thead><tbody>
        <tr v-for="x in sessions" :key="x.id"><td>{{ (x.user_agent || '').slice(0, 60) }} <span v-if="x.current" class="pill p-green">Hiện tại</span></td><td>{{ x.ip }}</td><td>{{ dt(x.last_used_at * 1000) }}</td><td class="r"><button v-if="!x.current" class="btn sm danger" @click="kick(x)">Đăng xuất</button></td></tr></tbody></table>
      <button class="btn danger" style="margin:10px 0 18px" @click="logoutAll">Đăng xuất mọi thiết bị</button>
      <p>Xác thực 2 lớp (TOTP): <b>{{ auth.user.two_factor_enabled ? 'đang bật' : 'chưa bật' }}</b></p>
      <div v-if="!auth.user.two_factor_enabled"><button class="btn" @click="setup2fa">Thiết lập 2FA</button>
        <div v-if="tfa" style="margin-top:12px"><p>Nhập khóa này vào ứng dụng xác thực: <b style="font-family:monospace">{{ tfa.secret }}</b></p><p class="muted" style="word-break:break-all">{{ tfa.otpauth_uri }}</p>
          <div class="acts"><input v-model="code" class="f" style="width:140px" placeholder="Mã 6 số" maxlength="6" /><button class="btn pri" @click="enable2fa">Bật 2FA</button></div></div></div></div>
  </div>
  <div v-else class="loading">Đang tải...</div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api, { dt } from '../api'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useUi } from '../stores/ui'
import ImageUpload from '../components/ImageUpload.vue'
const auth = useAuthStore(); const router = useRouter(); const sessions = ref([]); const ui = useUi(); const s = ref(null); const err = ref(''); const busy = ref(false); const logoUrl = ref(''); const favUrl = ref(''); const tfa = ref(null); const code = ref('')
async function load() { s.value = (await api.get('/admin/settings')).data.data; logoUrl.value = s.value.store.logo_url; favUrl.value = s.value.store.favicon_url }
async function save() { err.value = ''; busy.value = true
  try { const { store, payment, shipping, order } = s.value; const st = Object.fromEntries(Object.entries(store).filter(([k]) => !['logo_url', 'favicon_url'].includes(k))); await api.put('/admin/settings', { store: st, payment, shipping, order }); ui.notify('Đã lưu cài đặt'); load() } catch (e) { err.value = e.message } finally { busy.value = false } }
async function setup2fa() { tfa.value = (await api.post('/admin/auth/2fa/setup')).data.data }
async function enable2fa() { try { await api.post('/admin/auth/2fa/enable', { code: code.value }); auth.user.two_factor_enabled = true; tfa.value = null; ui.notify('Đã bật 2FA') } catch (e) { err.value = e.message } }
const loadSessions = async () => (sessions.value = (await api.get('/admin/auth/sessions')).data.data)
async function kick(x) { await api.delete(`/admin/auth/sessions/${x.id}`); loadSessions() }
async function logoutAll() { if (!confirm('Đăng xuất khỏi mọi thiết bị (kể cả thiết bị này)?')) return; await api.post('/admin/auth/logout-all'); auth.clear(); router.push('/login') }
onMounted(() => { load(); loadSessions() })
</script>
