<template>
  <ResourcePage endpoint="banners" title="Banner trang chủ" subtitle="Ảnh, link, vị trí, thứ tự và lịch hiển thị" noun="banner" write-perm="marketing:manage"
    :columns="[{ key: 'image', label: 'Ảnh', type: 'img' }, { key: 'title', label: 'Tiêu đề' }, { key: 'position', label: 'Vị trí' }, { key: 'sort_order', label: 'Thứ tự' }, { key: 'starts_at', label: 'Bắt đầu', type: 'date' }, { key: 'ends_at', label: 'Kết thúc', type: 'date' }, { key: 'is_active', label: 'Hiển thị', type: 'bool' }]"
    :fields="[{ key: 'title', label: 'Tiêu đề *', full: true }, { key: 'position', label: 'Vị trí', type: 'select', options: [['hero', 'Hero (đầu trang)'], ['promo', 'Khối khuyến mãi'], ['popup', 'Popup']] }, { key: 'sort_order', label: 'Thứ tự', type: 'number' },
      { key: 'link', label: 'Link đích', full: true, placeholder: '/khuyen-mai' }, { key: 'image_path', label: 'Ảnh desktop', type: 'image', upload: 'banner' }, { key: 'mobile_image_path', label: 'Ảnh mobile', type: 'image', upload: 'banner' },
      { key: 'starts_at', label: 'Bắt đầu', type: 'datetime' }, { key: 'ends_at', label: 'Kết thúc', type: 'datetime' }, { key: 'is_active', label: 'Hiển thị', type: 'bool' }]"
    :defaults="{ position: 'hero', sort_order: 0, is_active: true }" :to-form="(b, r) => ({ ...b, image_path_url: r?.image, mobile_image_path_url: r?.mobile_image })" />
</template>
<script setup>
import ResourcePage from '../components/ResourcePage.vue'
</script>
