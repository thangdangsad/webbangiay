<script setup>
import { onMounted, ref } from 'vue'
import { FwbAlert, FwbSpinner } from 'flowbite-vue'

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
    <!-- Hero -->
    <section
      class="mb-10 flex flex-col items-start justify-between gap-6 overflow-hidden rounded-2xl bg-ink-900 px-6 py-12 text-white sm:px-10 sm:py-16 md:flex-row md:items-center"
    >
      <div class="max-w-xl">
        <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-accent-400">
          Bộ sưu tập mới
        </p>
        <h1 class="font-display text-4xl leading-tight sm:text-5xl">
          BƯỚC ĐI
          <span class="text-accent-500">CHẤT RIÊNG</span>
        </h1>
        <p class="mt-4 text-ink-200">
          Czuka Shop — giày sneaker chính hãng, cập nhật trend streetwear mới nhất. Săn ngay những mẫu hot
          trước khi hết size.
        </p>
        <RouterLink
          to="#catalog"
          class="mt-6 inline-flex items-center rounded-lg bg-accent-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-accent-700"
        >
          Khám phá ngay
        </RouterLink>
      </div>
      <div class="w-full max-w-sm shrink-0 md:w-80">
        <img src="/placeholder.svg" alt="Czuka Shop" class="w-full rounded-xl" />
      </div>
    </section>

    <h2 id="catalog" class="page-title mb-5 font-display text-2xl text-ink-900">Sản phẩm mới</h2>

    <div v-if="loading" class="flex justify-center py-16">
      <FwbSpinner size="8" />
    </div>
    <FwbAlert v-else-if="error" type="danger">{{ error }}</FwbAlert>
    <p v-else-if="products.length === 0" class="py-16 text-center text-ink-400">Chưa có sản phẩm nào.</p>
    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
      <ProductCard v-for="p in products" :key="p.product_id" :product="p" />
    </div>
  </div>
</template>
