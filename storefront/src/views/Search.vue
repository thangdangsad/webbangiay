<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { FwbAlert, FwbSpinner } from 'flowbite-vue'

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
    <h2 class="mb-5 font-display text-2xl text-ink-900">Kết quả tìm kiếm: "{{ query }}"</h2>

    <div v-if="loading" class="flex justify-center py-16">
      <FwbSpinner size="8" />
    </div>
    <FwbAlert v-else-if="error" type="danger">{{ error }}</FwbAlert>
    <div v-else-if="products.length === 0" class="py-16 text-center text-ink-400">
      <p>Không tìm thấy sản phẩm phù hợp.</p>
      <RouterLink to="/" class="mt-2 inline-block font-semibold text-accent-600 hover:text-accent-700">
        Về trang chủ
      </RouterLink>
    </div>
    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
      <ProductCard v-for="p in products" :key="p.product_id" :product="p" />
    </div>
  </div>
</template>
