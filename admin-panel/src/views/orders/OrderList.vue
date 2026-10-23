<script setup>
import { onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'

import { http } from '@/api/client'
import { orderStatusLabel } from '@/utils/format'

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

function badgeSeverity(trangThai) {
  const n = trangThai === null || trangThai === undefined ? 0 : Number(trangThai)
  return { 0: 'warn', 1: 'success', 2: 'danger' }[n] ?? 'secondary'
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

function onTabChange() {
  load(1)
}

function onPage(event) {
  load(event.page + 1)
}

onMounted(() => load(1))
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Đơn hàng</h1>
    </div>

    <SelectButton
      v-model="status"
      :options="TABS"
      option-label="label"
      option-value="value"
      class="status-tabs"
      @change="onTabChange"
    />

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <DataTable
      v-else
      :value="orders"
      :loading="loading"
      data-key="order_id"
      striped-rows
      lazy
      paginator
      :rows="15"
      :total-records="total"
      :first="(page - 1) * 15"
      @page="onPage"
    >
      <template #empty>Không có đơn hàng nào.</template>
      <Column header="Mã đơn">
        <template #body="{ data }">{{ data.mahang ?? data.order_id }}</template>
      </Column>
      <Column header="Sản phẩm">
        <template #body="{ data }">{{ data.variant?.product?.product_name || '—' }}</template>
      </Column>
      <Column header="Size">
        <template #body="{ data }">{{ data.variant?.size || '—' }}</template>
      </Column>
      <Column header="Khách hàng">
        <template #body="{ data }">{{ data.customerInfo?.customer_name || '—' }}</template>
      </Column>
      <Column header="Số lượng">
        <template #body="{ data }">{{ data.soluong ?? '—' }}</template>
      </Column>
      <Column header="Trạng thái">
        <template #body="{ data }">
          <Tag :severity="badgeSeverity(data.trangThai)" :value="orderStatusLabel(data.trangThai)" />
        </template>
      </Column>
      <Column header="" style="width: 100px">
        <template #body="{ data }">
          <RouterLink :to="{ name: 'order-detail', params: { id: data.order_id } }">
            <Button label="Xem" size="small" outlined />
          </RouterLink>
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<style scoped>
.status-tabs {
  margin-bottom: 18px;
  flex-wrap: wrap;
}
</style>
