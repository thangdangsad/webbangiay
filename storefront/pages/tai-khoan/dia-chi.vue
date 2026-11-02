<template>
  <div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px"><h2>Sổ địa chỉ</h2><button class="btn sm brand" @click="edit()">+ Thêm địa chỉ</button></div>
    <div class="agrid"><div v-for="a in list" :key="a.id" class="addr" :class="{ def: a.is_default }"><span v-if="a.is_default" class="df">Mặc định</span>
      <b>{{ a.receiver_name }}</b> · {{ a.phone }}<p style="color:var(--muted);margin:6px 0">{{ a.street }}</p>
      <a href="#" @click.prevent="edit(a)">Sửa</a> · <a href="#" @click.prevent="del(a)">Xóa</a><template v-if="!a.is_default"> · <a href="#" @click.prevent="setDefault(a)">Đặt mặc định</a></template></div></div>
    <p v-if="!list.length" class="empty">Chưa có địa chỉ nào.</p>
    <form v-if="form" class="box" style="margin-top:18px" @submit.prevent="save">
      <h3>{{ form.id ? 'Sửa địa chỉ' : 'Địa chỉ mới' }}</h3>
      <div class="fgrid">
        <div class="fld"><label>Người nhận</label><input v-model="form.receiver_name" required /></div><div class="fld"><label>SĐT</label><input v-model="form.phone" required /><FieldError :msg="fe.phone" /></div>
        <div class="fld"><label>Tỉnh/TP</label><select v-model="form.province_code" required @change="prov"><option value="">Chọn</option><option v-for="x in provinces" :key="x.code" :value="x.code">{{ x.name }}</option></select></div>
        <div class="fld"><label>Quận/Huyện</label><select v-model="form.district_code" required @change="dist"><option value="">Chọn</option><option v-for="x in districts" :key="x.code" :value="x.code">{{ x.name }}</option></select></div>
        <div class="fld"><label>Phường/Xã</label><select v-model="form.ward_code" required><option value="">Chọn</option><option v-for="x in wards" :key="x.code" :value="x.code">{{ x.name }}</option></select></div>
        <div class="fld"><label>Số nhà, đường</label><input v-model="form.street" required /></div>
      </div>
      <label><input v-model="form.is_default" type="checkbox" /> Đặt làm mặc định</label>
      <div v-if="err" class="errmsg">{{ err }}</div>
      <div style="margin-top:12px"><button class="btn brand">Lưu</button> <button type="button" class="btn ghost" @click="form = null">Hủy</button></div>
    </form>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth', layout: 'account' })
const { $api } = useNuxtApp()
const list = ref<any[]>([]); const form = ref<any>(null); const err = ref(''); const fe = ref<Record<string, string>>({})
const provinces = ref<any[]>([]); const districts = ref<any[]>([]); const wards = ref<any[]>([])
async function load() { list.value = (await $api('/me/addresses')).data }
onMounted(async () => { await load(); provinces.value = (await $api('/locations/provinces')).data })
async function edit(a?: any) { err.value = ''; fe.value = {}; form.value = a ? { ...a, phone: a.phone.replace('+84', '0') } : { receiver_name: '', phone: '', province_code: '', district_code: '', ward_code: '', street: '', is_default: false }
  districts.value = wards.value = []; if (a) { districts.value = (await $api(`/locations/provinces/${a.province_code}`)).data; wards.value = (await $api(`/locations/provinces/${a.province_code}/${a.district_code}`)).data } }
async function prov() { form.value.district_code = form.value.ward_code = ''; wards.value = []; districts.value = (await $api(`/locations/provinces/${form.value.province_code}`)).data }
async function dist() { form.value.ward_code = ''; wards.value = (await $api(`/locations/provinces/${form.value.province_code}/${form.value.district_code}`)).data }
async function save() { err.value = ''; try { const f = form.value; await $api(f.id ? `/me/addresses/${f.id}` : '/me/addresses', { method: f.id ? 'PUT' : 'POST', body: f }); form.value = null; await load() } catch (e: any) { err.value = e.message; fe.value = e.fields ?? {} } }
async function del(a: any) { if (confirm('Xóa địa chỉ này?')) { await $api(`/me/addresses/${a.id}`, { method: 'DELETE' }); await load() } }
async function setDefault(a: any) { await $api(`/me/addresses/${a.id}`, { method: 'PUT', body: { ...a, is_default: true } }); await load() }
useSeoMeta({ title: 'Sổ địa chỉ', robots: 'noindex' })
</script>
