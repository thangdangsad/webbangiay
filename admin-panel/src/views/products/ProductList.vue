<script setup>
import { onMounted, ref, watch } from 'vue'

import { http } from '@/api/client'
import { formatPrice, productImageUrl } from '@/utils/format'

const products = ref([])
const categories = ref([])
const loading = ref(true)
const error = ref('')
const deletingId = ref(null)

const search = ref('')
const categoryId = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

let searchDebounce = null

async function load(currentPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/products', {
      params: {
        q: search.value || undefined,
        category_id: categoryId.value || undefined,
        per_page: 15,
        page: currentPage,
      },
    })
    products.value = data.data
    page.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    total.value = data.meta.total
  } catch {
    error.value = 'Không thể tải danh sách sản phẩm.'
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const { data } = await http.get('/api/categories')
    categories.value = data.data
  } catch {
    categories.value = []
  }
}

function goToPage(p) {
  if (p < 1 || p > lastPage.value) return
  load(p)
}

watch(search, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => load(1), 350)
})

watch(categoryId, () => load(1))

onMounted(() => {
  loadCategories()
  load(1)
})

async function remove(product) {
  if (!confirm(`Xóa sản phẩm "${product.product_name}"?`)) return
  deletingId.value = product.product_id
  try {
    await http.delete(`/api/admin/products/${product.product_id}`)
    await load(page.value)
  } catch {
    alert('Xóa sản phẩm không thành công.')
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Sản phẩm</h1>
      <RouterLink :to="{ name: 'product-new' }" class="btn btn-primary">+ Thêm sản phẩm</RouterLink>
    </div>

    <div class="toolbar">
      <input v-model="search" type="search" placeholder="Tìm theo tên sản phẩm" style="flex: 1; max-width: 320px" />
      <select v-model="categoryId">
        <option value="">Tất cả danh mục</option>
        <option v-for="c in categories" :key="c.category_id" :value="c.category_id">
          {{ c.category_name }}
        </option>
      </select>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="products.length === 0" class="empty-state">Không có sản phẩm nào.</p>

    <template v-else>
      <div class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th></th>
              <th>Tên sản phẩm</th>
              <th>Danh mục</th>
              <th>Giá</th>
              <th>Giảm giá</th>
              <th>Số lượng</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products" :key="p.product_id">
              <td style="width: 56px">
                <img
                  :src="productImageUrl(p) || '/placeholder.svg'"
                  :alt="p.product_name"
                  class="row-thumb"
                  @error="$event.target.src = '/placeholder.svg'"
                />
              </td>
              <td>{{ p.product_name }}</td>
              <td>{{ p.category?.category_name || '—' }}</td>
              <td>{{ formatPrice(p.product_price) }}</td>
              <td>{{ p.product_discount ? formatPrice(p.product_discount) : '—' }}</td>
              <td>{{ p.product_quantity }}</td>
              <td>
                <div class="row-actions">
                  <RouterLink :to="{ name: 'product-edit', params: { id: p.product_id } }" class="btn btn-outline btn-sm">
                    Sửa
                  </RouterLink>
                  <button
                    class="btn btn-danger btn-sm"
                    :disabled="deletingId === p.product_id"
                    @click="remove(p)"
                  >
                    Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="lastPage > 1" class="pagination">
        <button class="btn btn-outline" :disabled="page <= 1" @click="goToPage(page - 1)">Trước</button>
        <span>Trang {{ page }} / {{ lastPage }} ({{ total }} sản phẩm)</span>
        <button class="btn btn-outline" :disabled="page >= lastPage" @click="goToPage(page + 1)">Sau</button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.row-thumb {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  background: #f1f3f5;
  display: block;
}
</style>
