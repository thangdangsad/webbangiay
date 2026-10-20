<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { http } from '@/api/client'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const products = ref([])
const loading = ref(true)
const error = ref('')
const query = ref('')

async function load(q) {
  query.value = q
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/products', { params: { q, per_page: 12 } })
    products.value = data.data
  } catch {
    error.value = 'Không thể tìm kiếm sản phẩm.'
  } finally {
    loading.value = false
  }
}

watch(
  () => route.query.q,
  (q) => load(q || ''),
  { immediate: true },
)
</script>

<template>
  <div>
    <h2 class="page-title">Kết quả tìm kiếm: "{{ query }}"</h2>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="products.length === 0" class="empty-state">
      Không tìm thấy sản phẩm phù hợp.
    </p>
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
