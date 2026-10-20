<script setup>
import { onMounted, ref } from 'vue'

import { http } from '@/api/client'

const pendingOrders = ref(null)
const totalProducts = ref(null)
const totalCustomers = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const [pendingRes, productsRes, customersRes] = await Promise.all([
      http.get('/api/admin/orders', { params: { status: 0, per_page: 1 } }),
      http.get('/api/products', { params: { per_page: 1 } }),
      http.get('/api/admin/customers', { params: { per_page: 1 } }),
    ])
    pendingOrders.value = pendingRes.data.meta.total
    totalProducts.value = productsRes.data.meta.total
    totalCustomers.value = customersRes.data.meta.total
  } catch {
    // Leave counts as null; the cards below just show "—".
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Tổng quan</h1>
    </div>

    <div class="stat-grid">
      <RouterLink :to="{ name: 'orders' }" class="card stat-card">
        <div class="stat-label">Đơn chờ xác nhận</div>
        <div class="stat-value">{{ loading ? '…' : pendingOrders ?? '—' }}</div>
      </RouterLink>
      <RouterLink :to="{ name: 'products' }" class="card stat-card">
        <div class="stat-label">Tổng sản phẩm</div>
        <div class="stat-value">{{ loading ? '…' : totalProducts ?? '—' }}</div>
      </RouterLink>
      <RouterLink :to="{ name: 'customers' }" class="card stat-card">
        <div class="stat-label">Tổng khách hàng</div>
        <div class="stat-value">{{ loading ? '…' : totalCustomers ?? '—' }}</div>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.stat-card {
  display: block;
  color: var(--color-text);
  text-decoration: none;
}

.stat-card:hover {
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}
</style>
