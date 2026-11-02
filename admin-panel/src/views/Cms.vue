<template>
  <ResourcePage endpoint="pages" title="Trang nội dung" subtitle="Chính sách, giới thiệu, hướng dẫn chọn size (HTML cơ bản)" noun="trang" write-perm="content:manage"
    :columns="[{ key: 'title', label: 'Tiêu đề' }, { key: 'slug', label: 'Đường dẫn', format: (v) => '/chinh-sach/' + v }, { key: 'updated_at', label: 'Cập nhật', type: 'date' }, { key: 'is_published', label: 'Xuất bản', type: 'bool' }]"
    :fields="[{ key: 'title', label: 'Tiêu đề *', full: true }, { key: 'slug', label: 'Slug *', full: true }, { key: 'content', label: 'Nội dung (HTML)', type: 'textarea', rows: 10, full: true }, { key: 'is_published', label: 'Xuất bản', type: 'bool' }]" :defaults="{ is_published: true }" />
</template>
<script setup>
import ResourcePage from '../components/ResourcePage.vue'
</script>
