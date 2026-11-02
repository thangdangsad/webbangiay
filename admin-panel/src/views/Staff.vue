<template>
  <div>
    <div class="ph"><div><h1>Nhân viên & phân quyền</h1><p>RBAC theo resource:action</p></div><div class="acts"><button class="btn pri" @click="edit()">+ Thêm nhân viên</button></div></div>
    <div class="card tcard"><table class="tb"><thead><tr><th>Nhân viên</th><th>Vai trò</th><th>2FA</th><th>Đăng nhập gần nhất</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody><tr v-for="u in staff" :key="u.id"><td><b>{{ u.full_name }}</b><small>{{ u.email }}</small></td><td><span class="pill p-blue">{{ u.role }}</span></td><td>{{ u.two_factor_enabled ? '✔' : '—' }}</td><td>{{ dt(u.last_login_at) }}</td>
        <td><span class="pill" :class="u.status === 'active' ? 'p-green' : 'p-red'">{{ u.status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span></td><td class="r"><button class="btn sm" @click="edit(u)">Sửa</button></td></tr></tbody></table></div>

    <div class="card tcard"><h3 style="padding:16px 16px 0">Ma trận phân quyền</h3>
      <table class="tb matrix"><thead><tr><th>Quyền</th><th v-for="r in roles" :key="r.id">{{ r.name }}</th></tr></thead>
        <tbody><tr v-for="p in perms" :key="p"><td>{{ p }}</td><td v-for="r in roles" :key="r.id" class="perm" @click="toggle(r, p)"><span :class="has(r, p) ? 'yes' : 'no'">{{ has(r, p) ? '✔' : '–' }}</span></td></tr></tbody></table>
      <div style="padding:12px 16px" class="muted">Bấm vào ô để bật/tắt quyền (vai trò Admin luôn đủ quyền). Thay đổi có hiệu lực sau tối đa 5 phút hoặc ngay khi lưu.</div></div>

    <Modal v-if="form" :title="form.id ? 'Sửa nhân viên' : 'Thêm nhân viên'" @close="form = null">
      <form @submit.prevent="save">
        <div class="fld"><label>Họ tên</label><input v-model="form.full_name" class="f" required /></div>
        <div class="fld"><label>Email</label><input v-model="form.email" class="f" type="email" :disabled="!!form.id" required /></div>
        <div class="g2"><div class="fld"><label>Vai trò</label><select v-model="form.role_id" class="f"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option></select></div>
          <div v-if="form.id" class="fld"><label>Trạng thái</label><select v-model="form.status" class="f"><option value="active">Hoạt động</option><option value="blocked">Khóa</option></select></div></div>
        <div class="fld"><label>{{ form.id ? 'Đặt lại mật khẩu (tùy chọn)' : 'Mật khẩu' }}</label><input v-model="form.password" class="f" type="password" minlength="8" :required="!form.id" /></div>
        <label v-if="form.id" class="chk"><input v-model="form.reset_2fa" type="checkbox" /> Đặt lại 2FA</label>
        <div v-if="err" class="errmsg">{{ err }}</div><div class="acts" style="justify-content:flex-end"><button type="button" class="btn" @click="form = null">Hủy</button><button class="btn pri">Lưu</button></div>
      </form></Modal>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api, { dt } from '../api'
import { useUi } from '../stores/ui'
import Modal from '../components/Modal.vue'
const ui = useUi(); const staff = ref([]); const roles = ref([]); const perms = ref([]); const form = ref(null); const err = ref('')
const has = (r, p) => r.permissions.includes(p)
async function load() { staff.value = (await api.get('/admin/staff')).data.data; const r = (await api.get('/admin/roles')).data; roles.value = r.data; perms.value = r.permissions }
function edit(u) { err.value = ''; form.value = u ? { ...u, password: '' } : { full_name: '', email: '', role_id: roles.value[1]?.id, password: '' } }
async function save() { err.value = ''; const f = form.value
  try { if (f.id) await api.put(`/admin/staff/${f.id}`, { full_name: f.full_name, role_id: f.role_id, status: f.status, password: f.password || undefined, reset_2fa: f.reset_2fa || undefined }); else await api.post('/admin/staff', f); form.value = null; ui.notify('Đã lưu'); load() } catch (e) { err.value = e.message } }
async function toggle(r, p) { if (r.code === 'admin') return; const next = has(r, p) ? r.permissions.filter((x) => x !== p) : [...r.permissions, p]
  try { const res = (await api.put(`/admin/roles/${r.id}`, { permissions: next })).data; roles.value = res.data } catch (e) { ui.notify(e.message) } }
onMounted(load)
</script>
