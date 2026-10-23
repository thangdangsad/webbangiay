<script setup>
import { onMounted, ref } from 'vue'

import Card from 'primevue/card'

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
      <RouterLink :to="{ name: 'orders' }" class="stat-link">
        <Card class="stat-card">
          <template #content>
            <div class="stat-icon stat-icon-warning"><i class="pi pi-clock"></i></div>
            <div class="stat-label">Đơn chờ xác nhận</div>
            <div class="stat-value">{{ loading ? '…' : pendingOrders ?? '—' }}</div>
          </template>
        </Card>
      </RouterLink>
      <RouterLink :to="{ name: 'products' }" class="stat-link">
        <Card class="stat-card">
          <template #content>
            <div class="stat-icon stat-icon-primary"><i class="pi pi-shopping-bag"></i></div>
            <div class="stat-label">Tổng sản phẩm</div>
            <div class="stat-value">{{ loading ? '…' : totalProducts ?? '—' }}</div>
          </template>
        </Card>
      </RouterLink>
      <RouterLink :to="{ name: 'customers' }" class="stat-link">
        <Card class="stat-card">
          <template #content>
            <div class="stat-icon stat-icon-success"><i class="pi pi-users"></i></div>
            <div class="stat-label">Tổng khách hàng</div>
            <div class="stat-value">{{ loading ? '…' : totalCustomers ?? '—' }}</div>
          </template>
        </Card>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.stat-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.stat-card {
  transition: box-shadow 0.15s;
}

.stat-link:hover .stat-card {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin-bottom: 14px;
}

.stat-icon-primary {
  background: color-mix(in srgb, var(--p-primary-color) 15%, transparent);
  color: var(--p-primary-color);
}

.stat-icon-warning {
  background: #fff4de;
  color: #9a6700;
}

.stat-icon-success {
  background: #e7f6ec;
  color: #1e6b36;
}

.stat-label {
  color: var(--p-text-muted-color);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  margin-top: 6px;
}
</style>
