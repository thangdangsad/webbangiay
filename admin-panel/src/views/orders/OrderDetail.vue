<script setup>
import { computed, onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

import { http } from '@/api/client'
import { formatDate, orderStatusLabel } from '@/utils/format'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const confirm = useConfirm()
const toast = useToast()

const STATUS_OPTIONS = [
  { label: 'Chờ xác nhận', value: 0 },
  { label: 'Đã xác nhận', value: 1 },
  { label: 'Đã hủy', value: 2 },
]

const order = ref(null)
const loading = ref(true)
const error = ref('')
const updating = ref(false)

const siblingOrders = computed(() => order.value?.customerInfo?.orders || [])
const transactions = computed(() => order.value?.customerInfo?.transactions || [])

function badgeSeverity(trangThai) {
  const n = trangThai === null || trangThai === undefined ? 0 : Number(trangThai)
  return { 0: 'warn', 1: 'success', 2: 'danger' }[n] ?? 'secondary'
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
    toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã cập nhật trạng thái đơn hàng.', life: 3000 })
    await load()
  } catch {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Cập nhật trạng thái không thành công.', life: 4000 })
  } finally {
    updating.value = false
  }
}

function onStatusChange(event) {
  const value = event.value
  if (value === null || value === undefined) return

  // Cancelling an order is destructive — confirm first. Other transitions
  // (confirm / reset to pending) apply immediately, matching prior behavior.
  if (Number(value) === 2) {
    confirm.require({
      message: 'Bạn có chắc muốn hủy đơn hàng này?',
      header: 'Xác nhận hủy đơn',
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: 'Hủy đơn',
      rejectLabel: 'Không',
      acceptProps: { severity: 'danger' },
      accept: () => setStatus(2),
    })
    return
  }

  setStatus(value)
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Chi tiết đơn hàng</h1>
      <RouterLink :to="{ name: 'orders' }">
        <Button label="Quay lại" icon="pi pi-arrow-left" outlined />
      </RouterLink>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <template v-else-if="order">
      <Card class="detail-card">
        <template #content>
          <div class="status-row">
            <Tag :severity="badgeSeverity(order.trangThai)" :value="orderStatusLabel(order.trangThai)" />
            <SelectButton
              :model-value="Number(order.trangThai) || 0"
              :options="STATUS_OPTIONS"
              option-label="label"
              option-value="value"
              :disabled="updating"
              @change="onStatusChange"
            />
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
          <DataTable :value="siblingOrders" data-key="order_id" striped-rows>
            <Column header="Sản phẩm">
              <template #body="{ data }">{{ data.variant?.product?.product_name || '—' }}</template>
            </Column>
            <Column header="Size">
              <template #body="{ data }">{{ data.variant?.size || '—' }}</template>
            </Column>
            <Column field="soluong" header="Số lượng" />
            <Column header="Mã đơn">
              <template #body="{ data }">{{ data.mahang ?? data.order_id }}</template>
            </Column>
            <Column header="Ngày đặt">
              <template #body="{ data }">{{ formatDate(data.ngayDatHang) }}</template>
            </Column>
          </DataTable>

          <template v-if="transactions.length">
            <h3>Giao dịch / thanh toán</h3>
            <DataTable :value="transactions" data-key="giaodich_id" striped-rows>
              <Column header="Sản phẩm">
                <template #body="{ data }">{{ data.variant?.product?.product_name || '—' }}</template>
              </Column>
              <Column header="Size">
                <template #body="{ data }">{{ data.variant?.size || '—' }}</template>
              </Column>
              <Column field="soluong" header="Số lượng" />
              <Column field="magiaodich" header="Mã giao dịch" />
              <Column header="Ngày">
                <template #body="{ data }">{{ formatDate(data.ngayThangNam) }}</template>
              </Column>
            </DataTable>
          </template>
        </template>
      </Card>
    </template>
  </div>
</template>

<style scoped>
.detail-card h3 {
  margin: 24px 0 12px;
}

.detail-card h3:first-of-type {
  margin-top: 0;
}

.status-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  padding-bottom: 16px;
  margin-bottom: 8px;
  border-bottom: 1px solid var(--p-content-border-color);
}

.detail-list {
  display: grid;
  grid-template-columns: 140px 1fr;
  row-gap: 10px;
  margin: 0;
}

.detail-list dt {
  font-weight: 600;
  color: var(--p-text-muted-color);
}

.detail-list dd {
  margin: 0;
}
</style>
