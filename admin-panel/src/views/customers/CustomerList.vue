<script setup>
import { onMounted, ref } from 'vue'

import { http } from '@/api/client'

const customers = ref([])
const loading = ref(true)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

async function load(currentPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/admin/customers', { params: { per_page: 15, page: currentPage } })
    customers.value = data.data
    page.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    total.value = data.meta.total
  } catch {
    error.value = 'Không thể tải danh sách khách hàng.'
  } finally {
    loading.value = false
  }
}

function goToPage(p) {
  if (p < 1 || p > lastPage.value) return
  load(p)
}

onMounted(() => load(1))
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Khách hàng</h1>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="customers.length === 0" class="empty-state">Chưa có khách hàng nào.</p>

    <template v-else>
      <div class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Họ tên</th>
              <th>Email</th>
              <th>Điện thoại</th>
              <th>Địa chỉ</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in customers" :key="c.user_id">
              <td>{{ c.user_name }}</td>
              <td>{{ c.user_email }}</td>
              <td>{{ c.user_phone || '—' }}</td>
              <td>{{ c.user_address || '—' }}</td>
              <td>
                <RouterLink :to="{ name: 'customer-detail', params: { id: c.user_id } }" class="btn btn-outline btn-sm">
                  Xem
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="lastPage > 1" class="pagination">
        <button class="btn btn-outline" :disabled="page <= 1" @click="goToPage(page - 1)">Trước</button>
        <span>Trang {{ page }} / {{ lastPage }} ({{ total }} khách hàng)</span>
        <button class="btn btn-outline" :disabled="page >= lastPage" @click="goToPage(page + 1)">Sau</button>
      </div>
    </template>
  </div>
</template>
