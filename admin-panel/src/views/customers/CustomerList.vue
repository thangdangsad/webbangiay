<script setup>
import { computed, onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

import { http } from '@/api/client'

const customers = ref([])
const loading = ref(true)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

// The customer accounts endpoint has no server-side search parameter (it only
// supports `per_page`/`page` — see api/app/Http/Controllers/Admin/CustomerController.php),
// so this quick filter is a client-side-only convenience over the currently
// loaded page of rows, not a new API call shape.
const quickFilter = ref('')

// DataTable's own `filters`/global-filter mechanism only operates on rows it
// already holds client-side; it does not work together with `lazy` paging
// (where `:value` is just the current server page), so the filtering has to
// be done here instead, over the currently loaded page of rows.
const filteredCustomers = computed(() => {
  const term = quickFilter.value.trim().toLowerCase()
  if (!term) return customers.value
  return customers.value.filter((c) =>
    [c.user_name, c.user_email, c.user_phone, c.user_address].some((field) =>
      (field || '').toLowerCase().includes(term),
    ),
  )
})

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

function onPage(event) {
  load(event.page + 1)
}

onMounted(() => load(1))
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Khách hàng</h1>
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="quickFilter" placeholder="Tìm trong trang hiện tại..." />
      </IconField>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <DataTable
      v-else
      :value="filteredCustomers"
      :loading="loading"
      data-key="user_id"
      striped-rows
      lazy
      paginator
      :rows="15"
      :total-records="total"
      :first="(page - 1) * 15"
      @page="onPage"
    >
      <template #empty>Chưa có khách hàng nào.</template>
      <Column field="user_name" header="Họ tên" />
      <Column field="user_email" header="Email" />
      <Column field="user_phone" header="Điện thoại">
        <template #body="{ data }">{{ data.user_phone || '—' }}</template>
      </Column>
      <Column field="user_address" header="Địa chỉ">
        <template #body="{ data }">{{ data.user_address || '—' }}</template>
      </Column>
      <Column header="" style="width: 100px">
        <template #body="{ data }">
          <RouterLink :to="{ name: 'customer-detail', params: { id: data.user_id } }">
            <Button label="Xem" size="small" outlined />
          </RouterLink>
        </template>
      </Column>
    </DataTable>
  </div>
</template>
