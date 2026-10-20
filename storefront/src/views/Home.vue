<script setup>
import { onMounted, ref } from 'vue'

import { http } from '@/api/client'
import ProductCard from '@/components/ProductCard.vue'

const products = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    // No "new"/"hot" filter endpoint exists yet — show the latest products
    // the catalog API returns (ordered by product_id, per ProductController).
    const { data } = await http.get('/api/products', { params: { per_page: 12 } })
    products.value = data.data
  } catch {
    error.value = 'Không thể tải danh sách sản phẩm.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h2 class="page-title">Sản phẩm mới</h2>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="products.length === 0" class="empty-state">Chưa có sản phẩm nào.</p>
    <div v-else class="product-grid">
      <ProductCard v-for="p in products" :key="p.product_id" :product="p" />
    </div>
  </div>
</template>

<style scoped>
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}
</style>
