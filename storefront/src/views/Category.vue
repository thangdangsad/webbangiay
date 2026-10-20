<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { http } from '@/api/client'
import ProductCard from '@/components/ProductCard.vue'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const route = useRoute()
const products = ref([])
const categoryName = ref('')
const loading = ref(true)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)

async function load(categoryId, currentPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const [productsRes, categoriesRes] = await Promise.all([
      http.get('/api/products', { params: { category_id: categoryId, per_page: 9, page: currentPage } }),
      http.get('/api/categories'),
    ])
    products.value = productsRes.data.data
    page.value = productsRes.data.meta.current_page
    lastPage.value = productsRes.data.meta.last_page

    const match = categoriesRes.data.data.find(
      (c) => String(c.category_id) === String(categoryId),
    )
    categoryName.value = match?.category_name || ''
  } catch {
    error.value = 'Không thể tải danh mục sản phẩm.'
  } finally {
    loading.value = false
  }
}

watch(
  () => props.id,
  (id) => load(id, 1),
  { immediate: true },
)

watch(
  () => route.query.page,
  (p) => {
    if (p) load(props.id, Number(p))
  },
)

function goToPage(p) {
  if (p < 1 || p > lastPage.value) return
  load(props.id, p)
}
</script>

<template>
  <div>
    <h2 class="page-title">{{ categoryName || 'Danh mục sản phẩm' }}</h2>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="products.length === 0" class="empty-state">Chưa có sản phẩm trong danh mục này.</p>
    <template v-else>
      <div class="product-grid">
        <ProductCard v-for="p in products" :key="p.product_id" :product="p" />
      </div>

      <div v-if="lastPage > 1" class="pagination">
        <button class="btn btn-outline" :disabled="page <= 1" @click="goToPage(page - 1)">Trước</button>
        <span>Trang {{ page }} / {{ lastPage }}</span>
        <button class="btn btn-outline" :disabled="page >= lastPage" @click="goToPage(page + 1)">Sau</button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 28px;
}
</style>
