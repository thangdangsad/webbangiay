<template>
  <ResourcePage endpoint="promotions" title="Khuyến mãi / Flash sale" subtitle="Giảm giá theo sản phẩm, danh mục hoặc thương hiệu trong khoảng thời gian" noun="chương trình" write-perm="marketing:manage"
    :columns="[{ key: 'name', label: 'Chương trình' }, { key: 'discount_type', label: 'Giảm', format: (v, r) => (v === 'percent' ? r.value + '%' : money(r.value)) }, { key: 'targets', label: 'Áp dụng', format: (v) => (v?.length ? v.length + ' đối tượng' : 'Chưa chọn') }, { key: 'starts_at', label: 'Bắt đầu', type: 'date' }, { key: 'ends_at', label: 'Kết thúc', type: 'date' }, { key: 'is_active', label: 'Trạng thái', type: 'bool' }]"
    :fields="[{ key: 'name', label: 'Tên chương trình *', full: true }, { key: 'discount_type', label: 'Loại giảm', type: 'select', options: [['percent', 'Phần trăm'], ['fixed', 'Số tiền']] }, { key: 'value', label: 'Giá trị', type: 'number' },
      { key: 'starts_at', label: 'Bắt đầu', type: 'datetime' }, { key: 'ends_at', label: 'Kết thúc', type: 'datetime' }, { key: 'is_active', label: 'Kích hoạt', type: 'bool' }]"
    :defaults="{ discount_type: 'percent', value: 10, is_active: true, targets: [] }"
    :to-form="(b) => ({ ...b, targets: (b.targets || []).map((t) => (t.brand_id ? { kind: 'brand_id', id: t.brand_id } : t.category_id ? { kind: 'category_id', id: t.category_id } : { kind: 'product_id', id: t.product_id })) })"
    :before-save="(b, f) => ({ ...b, targets: f.targets.filter((t) => t.id).map((t) => ({ [t.kind]: t.id })) })">
    <template #form-extra="{ form }">
      <div class="fld"><label>Đối tượng áp dụng</label>
        <div v-for="(t, i) in form.targets" :key="i" class="acts" style="margin-bottom:6px">
          <select v-model="t.kind" class="f" style="width:130px" @change="t.id = null"><option value="brand_id">Thương hiệu</option><option value="category_id">Danh mục</option><option value="product_id">Sản phẩm</option></select>
          <select v-model="t.id" class="f"><option :value="null">Chọn...</option><option v-for="o in opts[t.kind]" :key="o.id" :value="o.id">{{ o.name }}</option></select>
          <button type="button" class="btn sm danger" @click="form.targets.splice(i, 1)">✕</button></div>
        <button type="button" class="btn sm" @click="form.targets.push({ kind: 'brand_id', id: null })">+ Thêm đối tượng</button></div>
    </template>
  </ResourcePage>
</template>
<script setup>
import { onMounted, reactive } from 'vue'
import api, { money } from '../api'
import ResourcePage from '../components/ResourcePage.vue'
const opts = reactive({ brand_id: [], category_id: [], product_id: [] })
onMounted(async () => {
  opts.brand_id = (await api.get('/admin/brands', { params: { all: 1 } })).data.data
  opts.category_id = (await api.get('/admin/categories', { params: { all: 1 } })).data.data
  opts.product_id = (await api.get('/admin/products', { params: { limit: 100 } })).data.data
})
</script>
