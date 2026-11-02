<template>
  <div class="container"><div class="auth">
    <h2 style="margin-bottom:6px">{{ token ? 'Đặt lại mật khẩu' : 'Quên mật khẩu' }}</h2>
    <p style="color:var(--muted);margin-bottom:18px">{{ token ? 'Nhập mật khẩu mới cho tài khoản của bạn.' : 'Nhập email, chúng tôi sẽ gửi liên kết đặt lại mật khẩu.' }}</p>
    <form @submit.prevent="submit">
      <div v-if="!token" class="fld" style="margin-bottom:14px"><label>Email</label><input v-model="email" type="email" required /></div>
      <div v-else class="fld" style="margin-bottom:14px"><label>Mật khẩu mới</label><input v-model="pw" type="password" minlength="8" required /></div>
      <div v-if="err" class="errmsg">{{ err }}</div><div v-if="ok" class="okmsg">{{ ok }}</div>
      <button class="btn brand block" :disabled="busy">{{ token ? 'Đổi mật khẩu' : 'Gửi liên kết' }}</button>
    </form>
    <p style="text-align:center;margin-top:16px"><NuxtLink to="/dang-nhap" style="color:var(--brand)">← Quay lại đăng nhập</NuxtLink></p>
  </div></div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp(); const route = useRoute()
const token = computed(() => String(route.query.token ?? ''))
const email = ref(''); const pw = ref(''); const err = ref(''); const ok = ref(''); const busy = ref(false)
async function submit() {
  err.value = ok.value = ''; busy.value = true
  try {
    if (token.value) { await $api('/auth/reset-password', { method: 'POST', body: { token: token.value, password: pw.value } }); ok.value = 'Đã đổi mật khẩu. Hãy đăng nhập lại.'; setTimeout(() => navigateTo('/dang-nhap'), 1500) }
    else { await $api('/auth/forgot-password', { method: 'POST', body: { email: email.value } }); ok.value = 'Nếu email tồn tại, liên kết đặt lại đã được gửi.' }
  } catch (e: any) { err.value = e.message } finally { busy.value = false }
}
useSeoMeta({ title: 'Quên mật khẩu', robots: 'noindex' })
</script>
