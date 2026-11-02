<template>
  <div>
    <h2 style="margin-bottom:16px">Thông tin cá nhân</h2>
    <form class="box" @submit.prevent="save">
      <div class="fgrid">
        <div class="fld"><label>Họ và tên</label><input v-model="f.full_name" required /></div>
        <div class="fld"><label>Email</label><input :value="auth.user?.email" disabled /></div>
        <div class="fld"><label>Số điện thoại</label><input v-model="f.phone" /><FieldError :msg="fe.phone" /></div>
        <div class="fld"><label>Ngày sinh</label><input v-model="f.birthday" type="date" /></div>
      </div>
      <div v-if="msg" class="okmsg">{{ msg }}</div><div v-if="err" class="errmsg">{{ err }}</div>
      <button class="btn brand" style="margin-top:12px">Lưu thay đổi</button>
    </form>
    <h2 style="margin:28px 0 16px">{{ auth.user?.has_password ? 'Đổi mật khẩu' : 'Đặt mật khẩu' }}</h2>
    <form class="box" @submit.prevent="changePw">
      <div class="fgrid"><div v-if="auth.user?.has_password" class="fld"><label>Mật khẩu hiện tại</label><input v-model="pw.current_password" type="password" required /></div>
        <div class="fld"><label>Mật khẩu mới</label><input v-model="pw.password" type="password" minlength="8" required /></div></div>
      <div v-if="pmsg" class="okmsg">{{ pmsg }}</div><div v-if="perr" class="errmsg">{{ perr }}</div>
      <button class="btn" style="margin-top:12px">Đổi mật khẩu</button>
    </form>
    <h2 style="margin:28px 0 16px">Thiết bị đăng nhập</h2>
    <div class="box">
      <div v-for="x in sessions" :key="x.id" class="row"><span>{{ (x.user_agent || 'Không rõ').slice(0, 70) }} <small style="color:var(--muted)">· {{ x.ip }}</small> <b v-if="x.current">(thiết bị này)</b></span>
        <a v-if="!x.current" href="#" style="color:var(--brand)" @click.prevent="kick(x)">Đăng xuất</a></div>
      <button class="btn ghost sm" style="margin-top:12px" @click="logoutAll">Đăng xuất mọi thiết bị</button>
    </div>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth', layout: 'account' })
const { $api } = useNuxtApp(); const auth = useAuthStore()
const f = reactive({ full_name: auth.user?.full_name ?? '', phone: (auth.user?.phone ?? '').replace('+84', '0'), birthday: auth.user?.birthday ?? '' })
const msg = ref(''); const err = ref(''); const fe = ref<Record<string, string>>({})
async function save() { msg.value = err.value = ''; fe.value = {}
  try { const r = await $api('/me', { method: 'PATCH', body: { ...f, phone: f.phone || null, birthday: f.birthday || null } }); auth.user = r.data; msg.value = 'Đã lưu.' } catch (e: any) { err.value = e.message; fe.value = e.fields ?? {} } }
const pw = reactive({ current_password: '', password: '' }); const pmsg = ref(''); const perr = ref('')
async function changePw() { pmsg.value = perr.value = ''
  try { await $api('/me/password', { method: 'PUT', body: pw }); auth.clear(); await navigateTo('/dang-nhap') } catch (e: any) { perr.value = e.message } }
const sessions = ref<any[]>([])
const loadSessions = async () => (sessions.value = (await $api('/me/sessions')).data)
async function kick(x: any) { await $api(`/me/sessions/${x.id}`, { method: 'DELETE' }); loadSessions() }
async function logoutAll() { if (!confirm('Đăng xuất khỏi mọi thiết bị?')) return; await $api('/auth/logout-all', { method: 'POST' }); auth.clear(); await navigateTo('/dang-nhap') }
onMounted(loadSessions)
useSeoMeta({ title: 'Thông tin cá nhân', robots: 'noindex' })
</script>
