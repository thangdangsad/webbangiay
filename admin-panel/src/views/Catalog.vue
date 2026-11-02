<template>
  <div>
    <div class="ph"><div><h1>Danh mục · Thương hiệu · Thuộc tính</h1><p>Quản lý dữ liệu phân loại sản phẩm</p></div></div>
    <div class="card tcard"><div class="tabs"><a v-for="t in tabs" :key="t[0]" :class="{ on: tab === t[0] }" href="#" @click.prevent="tab = t[0]">{{ t[1] }}</a></div>
      <div style="padding:16px">
        <ResourcePage v-if="tab === 'categories'" key="c" embedded endpoint="categories" noun="danh mục" write-perm="product:write" :searchable="false" :params="{ all: 1 }" :columns="[{ key: 'name', label: 'Tên' }, { key: 'parent_id', label: 'Cấp', format: (v) => (v ? 'Con' : 'Gốc') }, { key: 'slug', label: 'Slug' }, { key: 'sort_order', label: 'Thứ tự' }, { key: 'is_active', label: 'Hiển thị', type: 'bool' }]"
          :fields="[{ key: 'name', label: 'Tên *' }, { key: 'slug', label: 'Slug', help: 'Để trống để tự sinh' }, { key: 'parent_id', label: 'Danh mục cha', type: 'select', options: catOptions }, { key: 'sort_order', label: 'Thứ tự', type: 'number' }, { key: 'is_active', label: 'Hiển thị', type: 'bool' }]" :defaults="{ is_active: true, sort_order: 0, parent_id: null }" />
        <ResourcePage v-else-if="tab === 'brands'" key="b" embedded endpoint="brands" noun="thương hiệu" write-perm="product:write" :params="{ all: 1 }" :columns="[{ key: 'logo', label: 'Logo', type: 'img' }, { key: 'name', label: 'Tên' }, { key: 'slug', label: 'Slug' }, { key: 'is_active', label: 'Hiển thị', type: 'bool' }]"
          :fields="[{ key: 'name', label: 'Tên *' }, { key: 'slug', label: 'Slug' }, { key: 'logo_path', label: 'Logo', type: 'image', upload: 'brand', full: true }, { key: 'is_active', label: 'Hiển thị', type: 'bool' }]" :defaults="{ is_active: true }"
          :to-form="(b, r) => ({ ...b, logo_path_url: r?.logo })" />
        <ResourcePage v-else-if="tab === 'colors'" key="co" embedded endpoint="colors" noun="màu" write-perm="product:write" :params="{ all: 1 }" :columns="[{ key: 'name', label: 'Tên màu' }, { key: 'hex', label: 'Mã màu', type: 'color' }]"
          :fields="[{ key: 'name', label: 'Tên màu *' }, { key: 'hex', label: 'Mã màu', type: 'color' }]" :defaults="{ hex: '#111111' }" />
        <ResourcePage v-else key="s" embedded endpoint="sizes" noun="size" write-perm="product:write" :params="{ all: 1 }" :columns="[{ key: 'system', label: 'Hệ' }, { key: 'label', label: 'Size' }, { key: 'sort_order', label: 'Thứ tự' }]"
          :fields="[{ key: 'system', label: 'Hệ size', type: 'select', options: [['EU', 'EU'], ['US', 'US'], ['UK', 'UK'], ['CM', 'CM']] }, { key: 'label', label: 'Size *' }, { key: 'sort_order', label: 'Thứ tự', type: 'number' }]" :defaults="{ system: 'EU', sort_order: 0 }" />
      </div></div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '../api'
import ResourcePage from '../components/ResourcePage.vue'
const tab = ref('categories'); const tabs = [['categories', 'Danh mục'], ['brands', 'Thương hiệu'], ['colors', 'Màu sắc'], ['sizes', 'Size']]
const cats = ref([])
const catOptions = computed(() => [[null, '— Gốc —'], ...cats.value.filter((c) => !c.parent_id).map((c) => [c.id, c.name])])
onMounted(async () => (cats.value = (await api.get('/admin/categories', { params: { all: 1 } })).data.data))
</script>
