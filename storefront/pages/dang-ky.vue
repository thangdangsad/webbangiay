<template>
  <div class="container"><div class="auth">
    <div class="tabs"><NuxtLink to="/dang-nhap">Đăng nhập</NuxtLink><NuxtLink class="on" to="/dang-ky">Đăng ký</NuxtLink></div>
    <form @submit.prevent="submit">
      <div class="fld" style="margin-bottom:14px"><label>Họ và tên</label><input v-model="f.full_name" required /><FieldError :msg="fe.full_name" /></div>
      <div class="fld" style="margin-bottom:14px"><label>Email</label><input v-model="f.email" type="email" required /><FieldError :msg="fe.email" /></div>
      <div class="fld" style="margin-bottom:14px"><label>Số điện thoại</label><input v-model="f.phone" inputmode="tel" /><FieldError :msg="fe.phone" /></div>
      <div class="fld" style="margin-bottom:14px"><label>Mật khẩu (tối thiểu 8 ký tự)</label><input v-model="f.password" type="password" required minlength="8" /><div class="pwd-s"><i :style="{ width: strength + '%' }"></i></div><FieldError :msg="fe.password" /></div>
      <div v-if="err" class="errmsg">{{ err }}</div>
      <button class="btn brand block" :disabled="busy">Tạo tài khoản</button>
    </form>
    <GoogleButton />
  </div></div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'guest' })
const auth = useAuthStore()
const f = reactive({ full_name: '', email: '', phone: '', password: '' }); const err = ref(''); const fe = ref<Record<string, string>>({}); const busy = ref(false)
const strength = computed(() => Math.min(100, f.password.length * 8 + (/[A-Z]/.test(f.password) ? 15 : 0) + (/\d/.test(f.password) ? 15 : 0)))
async function submit() {
  err.value = ''; fe.value = {}; busy.value = true
  try { await auth.register({ ...f, phone: f.phone || null }); await navigateTo('/tai-khoan') } catch (e: any) { err.value = e.message; fe.value = e.fields ?? {} } finally { busy.value = false }
}
useSeoMeta({ title: 'Đăng ký', robots: 'noindex' })
</script>
