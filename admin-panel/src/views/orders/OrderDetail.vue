<script setup>
import { computed, onMounted, ref } from 'vue'

import { http } from '@/api/client'
import { formatDate, orderStatusLabel } from '@/utils/format'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const order = ref(null)
const loading = ref(true)
const error = ref('')
const updating = ref(false)

const siblingOrders = computed(() => order.value?.customerInfo?.orders || [])
const transactions = computed(() => order.value?.customerInfo?.transactions || [])

function badgeClass(trangThai) {
  const n = trangThai === null || trangThai === undefined ? 0 : Number(trangThai)
  return { 0: 'badge-pending', 1: 'badge-confirmed', 2: 'badge-cancelled' }[n] ?? ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get(`/api/admin/orders/${props.id}`)
    order.value = data.data
  } catch {
    error.value = 'Không thể tải chi tiết đơn hàng.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function setStatus(trangThai) {
  updating.value = true
  try {
    await http.patch(`/api/admin/orders/${props.id}`, { trangThai })
    await load()
  } catch {
    alert('Cập nhật trạng thái không thành công.')
  } finally {
    updating.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Chi tiết đơn hàng</h1>
      <RouterLink :to="{ name: 'orders' }" class="btn btn-outline">&larr; Quay lại</RouterLink>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>

    <template v-else-if="order">
      <div class="card detail-card">
        <div class="status-row">
          <span class="badge" :class="badgeClass(order.trangThai)">{{ orderStatusLabel(order.trangThai) }}</span>
          <div class="status-actions">
            <button class="btn btn-primary btn-sm" :disabled="updating" @click="setStatus(1)">Xác nhận</button>
            <button class="btn btn-danger btn-sm" :disabled="updating" @click="setStatus(2)">Hủy</button>
            <button class="btn btn-outline btn-sm" :disabled="updating" @click="setStatus(0)">Đặt lại chờ xác nhận</button>
          </div>
        </div>

        <h3>Thông tin khách hàng</h3>
        <dl class="detail-list">
          <dt>Họ tên</dt>
          <dd>{{ order.customerInfo?.customer_name || '—' }}</dd>
          <dt>Điện thoại</dt>
          <dd>{{ order.customerInfo?.customer_phone || '—' }}</dd>
          <dt>Email</dt>
          <dd>{{ order.customerInfo?.customer_email || '—' }}</dd>
          <dt>Địa chỉ</dt>
          <dd>{{ order.customerInfo?.customer_address || '—' }}</dd>
          <dt>Ghi chú</dt>
          <dd>{{ order.customerInfo?.customer_note || '—' }}</dd>
        </dl>

        <h3>Các sản phẩm trong đơn (cùng lượt thanh toán)</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Số lượng</th>
              <th>Mã đơn</th>
              <th>Ngày đặt</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in siblingOrders" :key="line.order_id" :class="{ current: line.order_id === order.order_id }">
              <td>{{ line.product?.product_name || '—' }}</td>
              <td>{{ line.soluong }}</td>
              <td>{{ line.mahang ?? line.order_id }}</td>
              <td>{{ formatDate(line.ngayDatHang) }}</td>
            </tr>
          </tbody>
        </table>

        <template v-if="transactions.length">
          <h3>Giao dịch / thanh toán</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Mã giao dịch</th>
                <th>Ngày</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in transactions" :key="t.giaodich_id">
                <td>{{ t.product?.product_name || '—' }}</td>
                <td>{{ t.soluong }}</td>
                <td>{{ t.magiaodich }}</td>
                <td>{{ formatDate(t.ngayThangNam) }}</td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </template>
  </div>
</template>

<style scoped>
.detail-card {
  padding: 24px;
}

.detail-card h3 {
  margin: 24px 0 12px;
}

.detail-card h3:first-of-type {
  margin-top: 0;
}

.status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
}

.status-actions {
  display: flex;
  gap: 8px;
}

tr.current {
  background: #fff8e1;
}
</style>
