<script setup>
import { onMounted, ref } from 'vue'

import { http } from '@/api/client'
import { formatDate } from '@/utils/format'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const customer = ref(null)
const orderHistory = ref([])
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get(`/api/admin/customers/${props.id}`)
    customer.value = data.data
    orderHistory.value = data.order_history || []
  } catch {
    error.value = 'Không thể tải thông tin khách hàng.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Chi tiết khách hàng</h1>
      <RouterLink :to="{ name: 'customers' }" class="btn btn-outline">&larr; Quay lại</RouterLink>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>

    <template v-else-if="customer">
      <div class="card detail-card">
        <dl class="detail-list">
          <dt>Họ tên</dt>
          <dd>{{ customer.user_name }}</dd>
          <dt>Email</dt>
          <dd>{{ customer.user_email }}</dd>
          <dt>Điện thoại</dt>
          <dd>{{ customer.user_phone || '—' }}</dd>
          <dt>Địa chỉ</dt>
          <dd>{{ customer.user_address || '—' }}</dd>
          <dt>Ngày tạo</dt>
          <dd>{{ formatDate(customer.created_at) }}</dd>
        </dl>
      </div>

      <h3 class="section-title">Lịch sử đơn hàng</h3>
      <p v-if="orderHistory.length === 0" class="empty-state">Khách hàng chưa đặt hàng nào.</p>
      <div v-else class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Người nhận</th>
              <th>Điện thoại</th>
              <th>Địa chỉ</th>
              <th>Sản phẩm</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="info in orderHistory" :key="info.customer_id">
              <td>{{ info.customer_name }}</td>
              <td>{{ info.customer_phone || '—' }}</td>
              <td>{{ info.customer_address || '—' }}</td>
              <td>
                <ul class="line-list">
                  <li v-for="line in info.orders" :key="line.order_id">
                    x{{ line.soluong }} {{ line.product?.product_name || '—' }}
                  </li>
                </ul>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<style scoped>
.detail-card {
  padding: 24px;
  max-width: 560px;
  margin-bottom: 24px;
}

.section-title {
  margin: 0 0 12px;
}

.line-list {
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
}
</style>
