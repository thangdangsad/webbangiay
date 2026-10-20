<script setup>
import { onMounted, ref } from 'vue'

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

function goToPage(p) {
  if (p < 1 || p > lastPage.value) return
  load(p)
}

onMounted(() => load(1))
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Nhân viên</h1>
      <RouterLink v-if="auth.isAdmin" :to="{ name: 'staff-new' }" class="btn btn-primary">+ Thêm nhân viên</RouterLink>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="staffList.length === 0" class="empty-state">Chưa có nhân viên nào.</p>

    <template v-else>
      <div class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Họ tên</th>
              <th>Email</th>
              <th>Điện thoại</th>
              <th>Vai trò</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in staffList" :key="s.user_id">
              <td>{{ s.user_name }}</td>
              <td>{{ s.user_email }}</td>
              <td>{{ s.user_phone || '—' }}</td>
              <td>
                <span class="badge" :class="s.role === 1 ? 'badge-role-admin' : 'badge-role-staff'">
                  {{ s.role === 1 ? 'Quản trị viên' : 'Nhân viên' }}
                </span>
              </td>
              <td>
                <div class="row-actions" v-if="auth.isAdmin || auth.staff?.user_id === s.user_id">
                  <RouterLink :to="{ name: 'staff-edit', params: { id: s.user_id } }" class="btn btn-outline btn-sm">
                    Sửa
                  </RouterLink>
                  <RouterLink :to="{ name: 'staff-password', params: { id: s.user_id } }" class="btn btn-outline btn-sm">
                    Đổi mật khẩu
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="lastPage > 1" class="pagination">
        <button class="btn btn-outline" :disabled="page <= 1" @click="goToPage(page - 1)">Trước</button>
        <span>Trang {{ page }} / {{ lastPage }} ({{ total }} nhân viên)</span>
        <button class="btn btn-outline" :disabled="page >= lastPage" @click="goToPage(page + 1)">Sau</button>
      </div>
    </template>
  </div>
</template>
