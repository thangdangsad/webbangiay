<template>
  <div class="lg">
    <div class="l"><div style="font-size:22px;font-weight:800">Sole<span style="color:var(--brand)">House</span> Admin</div><div><h2>Quản lý cửa hàng giày trên một hệ thống.</h2><p style="color:#94a3b8;margin-top:12px">Sản phẩm · Tồn kho · Đơn hàng · Khuyến mãi · Báo cáo</p></div><small style="color:#64748b">© 2026 SoleHouse</small></div>
    <div class="r">
      <form v-if="!challenge" @submit.prevent="submit">
        <h1>Đăng nhập quản trị</h1><p>Dành cho Admin, Quản lý, Kho và CSKH.</p>
        <div class="fld"><label>Email</label><input v-model="email" class="f" type="email" required autocomplete="username" /></div>
        <div class="fld"><label>Mật khẩu</label><input v-model="password" class="f" type="password" required autocomplete="current-password" /></div>
        <div v-if="err" class="errmsg">{{ err }}</div>
        <button class="btn pri block" style="height:42px" :disabled="busy">Đăng nhập</button>
      </form>
      <form v-else @submit.prevent="verify">
        <h1>Xác thực 2 lớp</h1><p>Nhập mã 6 số từ ứng dụng xác thực (Google Authenticator…).</p>
        <div class="fld"><label>Mã xác thực</label><input v-model="code" class="f" inputmode="numeric" maxlength="6" required autofocus /></div>
        <div v-if="err" class="errmsg">{{ err }}</div>
        <button class="btn pri block" style="height:42px" :disabled="busy">Xác nhận</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const auth = useAuthStore(); const router = useRouter(); const route = useRoute()
const email = ref(''); const password = ref(''); const code = ref(''); const err = ref(''); const busy = ref(false); const challenge = ref(null)
const done = () => router.replace(String(route.query.redirect || '/'))
async function submit() { err.value = ''; busy.value = true
  try { const r = await auth.login(email.value, password.value); if (r) challenge.value = r.challenge_token; else done() } catch (e) { err.value = e.message } finally { busy.value = false } }
async function verify() { err.value = ''; busy.value = true
  try { await auth.verify2fa(challenge.value, code.value); done() } catch (e) { err.value = e.message } finally { busy.value = false } }
</script>
