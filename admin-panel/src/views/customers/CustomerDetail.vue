<script setup>
import { onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'

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
      <RouterLink :to="{ name: 'customers' }">
        <Button label="Quay lại" icon="pi pi-arrow-left" outlined />
      </RouterLink>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <template v-else-if="customer">
      <Card class="detail-card">
        <template #content>
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
        </template>
      </Card>

      <h3 class="section-title">Lịch sử đơn hàng</h3>
      <DataTable :value="orderHistory" :loading="loading" data-key="customer_id" striped-rows>
        <template #empty>Khách hàng chưa đặt hàng nào.</template>
        <Column field="customer_name" header="Người nhận" />
        <Column header="Điện thoại">
          <template #body="{ data }">{{ data.customer_phone || '—' }}</template>
        </Column>
        <Column header="Địa chỉ">
          <template #body="{ data }">{{ data.customer_address || '—' }}</template>
        </Column>
        <Column header="Sản phẩm">
          <template #body="{ data }">
            <ul class="line-list">
              <li v-for="line in data.orders" :key="line.order_id">
                x{{ line.soluong }} {{ line.product?.product_name || '—' }}
              </li>
            </ul>
          </template>
        </Column>
      </DataTable>
    </template>
  </div>
</template>

<style scoped>
.detail-card {
  max-width: 560px;
  margin-bottom: 24px;
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
