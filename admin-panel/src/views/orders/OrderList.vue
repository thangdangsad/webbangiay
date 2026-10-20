<script setup>
import { onMounted, ref } from 'vue'

import { http } from '@/api/client'
import { formatPrice, orderStatusLabel } from '@/utils/format'

const TABS = [
  { label: 'Chờ xác nhận', value: 0 },
  { label: 'Đã xác nhận', value: 1 },
  { label: 'Đã hủy', value: 2 },
  { label: 'Tất cả', value: null },
]

const orders = ref([])
const loading = ref(true)
const error = ref('')
const status = ref(0)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

function badgeClass(trangThai) {
  const n = trangThai === null || trangThai === undefined ? 0 : Number(trangThai)
  return { 0: 'badge-pending', 1: 'badge-confirmed', 2: 'badge-cancelled' }[n] ?? ''
}

async function load(currentPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/admin/orders', {
      params: { status: status.value ?? undefined, per_page: 15, page: currentPage },
    })
    orders.value = data.data
    page.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    total.value = data.meta.total
  } catch {
    error.value = 'Không thể tải danh sách đơn hàng.'
  } finally {
    loading.value = false
  }
}

function selectTab(value) {
  status.value = value
  load(1)
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
      <h1 class="page-title">Đơn hàng</h1>
    </div>

    <div class="tabs">
      <button
        v-for="t in TABS"
        :key="String(t.value)"
        class="tab"
        :class="{ active: status === t.value }"
        @click="selectTab(t.value)"
      >
        {{ t.label }}
      </button>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="orders.length === 0" class="empty-state">Không có đơn hàng nào.</p>

    <template v-else>
      <div class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Mã đơn</th>
              <th>Sản phẩm</th>
              <th>Khách hàng</th>
              <th>Số lượng</th>
              <th>Trạng thái</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="o in orders" :key="o.order_id">
              <td>{{ o.mahang ?? o.order_id }}</td>
              <td>{{ o.product?.product_name || '—' }}</td>
              <td>{{ o.customerInfo?.customer_name || '—' }}</td>
              <td>{{ o.soluong ?? '—' }}</td>
              <td><span class="badge" :class="badgeClass(o.trangThai)">{{ orderStatusLabel(o.trangThai) }}</span></td>
              <td>
                <RouterLink :to="{ name: 'order-detail', params: { id: o.order_id } }" class="btn btn-outline btn-sm">
                  Xem
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="lastPage > 1" class="pagination">
        <button class="btn btn-outline" :disabled="page <= 1" @click="goToPage(page - 1)">Trước</button>
        <span>Trang {{ page }} / {{ lastPage }} ({{ total }} đơn)</span>
        <button class="btn btn-outline" :disabled="page >= lastPage" @click="goToPage(page + 1)">Sau</button>
      </div>
    </template>
  </div>
</template>
