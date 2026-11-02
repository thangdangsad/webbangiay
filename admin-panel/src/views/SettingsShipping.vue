<template>
  <div>
    <ResourcePage endpoint="shipping-rates" title="Vận chuyển" subtitle="Phí theo vùng, khối lượng và ngưỡng miễn phí" noun="mức phí" write-perm="settings:manage" :searchable="false"
      :columns="[{ key: 'zone', label: 'Vùng' }, { key: 'province_codes', label: 'Tỉnh/TP', format: (v) => (v?.length ? v.length + ' tỉnh' : 'Mặc định (còn lại)') }, { key: 'fee', label: 'Phí', type: 'money' }, { key: 'free_threshold', label: 'Miễn phí từ', type: 'money' }]"
      :fields="[{ key: 'zone', label: 'Tên vùng *', full: true }, { key: 'province_codes', label: 'Mã tỉnh (cách nhau bởi dấu phẩy)', full: true, help: 'VD: 01,79 — để trống = áp dụng cho các tỉnh còn lại' }, { key: 'fee', label: 'Phí (đ)', type: 'number' }, { key: 'free_threshold', label: 'Miễn phí từ (đ)', type: 'number', nullable: true }]"
      :defaults="{ fee: 30000 }" :to-form="(b) => ({ ...b, province_codes: (b.province_codes || []).join(',') })" :before-save="(b) => ({ ...b, province_codes: b.province_codes ? String(b.province_codes).split(',').map((x) => x.trim()).filter(Boolean) : null })" />
    <div class="card" style="margin-top:18px"><h3>Cấu hình chung</h3>
      <div v-if="s" class="g3"><div class="fld"><label>Ngưỡng miễn phí mặc định (đ)</label><input v-model.number="s.shipping.free_threshold" class="f" type="number" /></div><div class="fld"><label>Phí mặc định (đ)</label><input v-model.number="s.shipping.default_fee" class="f" type="number" /></div><div class="fld"><label>Đơn vị vận chuyển</label><input v-model="s.shipping.carrier" class="f" /></div></div>
      <button class="btn pri" @click="save">Lưu cấu hình</button></div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'
import { useUi } from '../stores/ui'
import ResourcePage from '../components/ResourcePage.vue'
const ui = useUi(); const s = ref(null)
onMounted(async () => (s.value = (await api.get('/admin/settings')).data.data))
async function save() { await api.put('/admin/settings', { shipping: s.value.shipping }); ui.notify('Đã lưu') }
</script>
