<script setup>
import { onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Tag from 'primevue/tag'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const staffList = ref([])
const loading = ref(true)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

async function load(currentPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/admin/staff', { params: { per_page: 15, page: currentPage } })
    staffList.value = data.data
    page.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    total.value = data.meta.total
  } catch {
    error.value = 'Không thể tải danh sách nhân viên.'
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
      <h1 class="page-title">Nhân viên</h1>
      <RouterLink v-if="auth.isAdmin" :to="{ name: 'staff-new' }">
        <Button label="Thêm nhân viên" icon="pi pi-plus" />
      </RouterLink>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <DataTable
      v-else
      :value="staffList"
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
      <template #empty>Chưa có nhân viên nào.</template>
      <Column field="user_name" header="Họ tên" />
      <Column field="user_email" header="Email" />
      <Column header="Điện thoại">
        <template #body="{ data }">{{ data.user_phone || '—' }}</template>
      </Column>
      <Column header="Vai trò">
        <template #body="{ data }">
          <Tag :severity="data.role === 1 ? 'info' : 'secondary'" :value="data.role === 1 ? 'Quản trị viên' : 'Nhân viên'" />
        </template>
      </Column>
      <Column header="" style="width: 220px">
        <template #body="{ data }">
          <div v-if="auth.isAdmin || auth.staff?.user_id === data.user_id" class="row-actions">
            <RouterLink :to="{ name: 'staff-edit', params: { id: data.user_id } }">
              <Button label="Sửa" size="small" outlined />
            </RouterLink>
            <RouterLink :to="{ name: 'staff-password', params: { id: data.user_id } }">
              <Button label="Đổi mật khẩu" size="small" outlined />
            </RouterLink>
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<style scoped>
.row-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
</style>
