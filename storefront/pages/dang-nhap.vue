<template>
  <div class="container"><div class="auth">
    <div class="tabs"><NuxtLink class="on" to="/dang-nhap">Đăng nhập</NuxtLink><NuxtLink to="/dang-ky">Đăng ký</NuxtLink></div>
    <form @submit.prevent="submit">
      <div class="fld" style="margin-bottom:14px"><label>Email hoặc số điện thoại</label><input v-model="id" required autocomplete="username" /></div>
      <div class="fld" style="margin-bottom:6px"><label>Mật khẩu</label><input v-model="pw" type="password" required autocomplete="current-password" /></div>
      <div style="text-align:right;margin-bottom:14px"><NuxtLink to="/quen-mat-khau" style="color:var(--brand);font-size:13px">Quên mật khẩu?</NuxtLink></div>
      <div v-if="err" class="errmsg">{{ err }}</div>
      <button class="btn brand block" :disabled="busy">Đăng nhập</button>
    </form>
    <GoogleButton />
  </div></div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'guest' })
const auth = useAuthStore(); const route = useRoute()
const id = ref(''); const pw = ref(''); const err = ref(''); const busy = ref(false)
async function submit() {
  err.value = ''; busy.value = true
  try { await auth.login(id.value, pw.value); await navigateTo(String(route.query.redirect || '/tai-khoan')) } catch (e: any) { err.value = e.message } finally { busy.value = false }
}
useSeoMeta({ title: 'Đăng nhập', robots: 'noindex' })
</script>
