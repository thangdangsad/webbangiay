<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { FwbAlert, FwbButton, FwbSpinner } from 'flowbite-vue'

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
    <h2 class="mb-5 font-display text-2xl text-ink-900">{{ categoryName || 'Danh mục sản phẩm' }}</h2>

    <div v-if="loading" class="flex justify-center py-16">
      <FwbSpinner size="8" />
    </div>
    <FwbAlert v-else-if="error" type="danger">{{ error }}</FwbAlert>
    <p v-else-if="products.length === 0" class="py-16 text-center text-ink-400">
      Chưa có sản phẩm trong danh mục này.
    </p>
    <template v-else>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        <ProductCard v-for="p in products" :key="p.product_id" :product="p" />
      </div>

      <div v-if="lastPage > 1" class="mt-8 flex items-center justify-center gap-4">
        <FwbButton outline color="dark" :disabled="page <= 1" @click="goToPage(page - 1)">Trước</FwbButton>
        <span class="text-sm text-ink-500">Trang {{ page }} / {{ lastPage }}</span>
        <FwbButton outline color="dark" :disabled="page >= lastPage" @click="goToPage(page + 1)">Sau</FwbButton>
      </div>
    </template>
  </div>
</template>
