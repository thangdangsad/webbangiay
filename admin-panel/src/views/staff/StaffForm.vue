<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  id: { type: [String, Number], default: null },
})

const auth = useAuthStore()
const router = useRouter()
const isEdit = computed(() => props.id !== null && props.id !== undefined)

const loading = ref(isEdit.value)
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})
const notFound = ref(false)

const form = reactive({
  user_name: '',
  user_email: '',
  user_phone: '',
  user_address: '',
  password: '',
  role: 2,
})

/**
 * There is no GET /api/admin/staff/{id} endpoint — only the list endpoint
 * exists. If editing our own account we already have it in the auth store;
 * otherwise, fall back to paging through the staff list to find the match
 * (the staff table is small — an internal admin tool, not a concern here).
 */
async function loadStaff() {
  const targetId = Number(props.id)

  if (auth.staff && Number(auth.staff.user_id) === targetId) {
    applyStaff(auth.staff)
    loading.value = false
    return
  }

  try {
    let page = 1
    let found = null
    while (!found) {
      const { data } = await http.get('/api/admin/staff', { params: { per_page: 100, page } })
      found = data.data.find((s) => Number(s.user_id) === targetId)
      if (found || page >= data.meta.last_page) break
      page += 1
    }
    if (found) {
      applyStaff(found)
    } else {
      notFound.value = true
    }
  } catch {
    errorMessage.value = 'Không thể tải thông tin nhân viên.'
  } finally {
    loading.value = false
  }
}

function applyStaff(s) {
  form.user_name = s.user_name || ''
  form.user_email = s.user_email || ''
  form.user_phone = s.user_phone || ''
  form.user_address = s.user_address || ''
  form.role = s.role
}

onMounted(() => {
  if (isEdit.value) {
    loadStaff()
  }
})

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}
  try {
    if (isEdit.value) {
      await http.patch(`/api/admin/staff/${props.id}`, {
        user_name: form.user_name,
        user_phone: form.user_phone || null,
        user_address: form.user_address || null,
      })
    } else {
      await http.post('/api/admin/staff', {
        user_name: form.user_name,
        user_email: form.user_email,
        user_phone: form.user_phone || null,
        user_address: form.user_address || null,
        password: form.password,
        role: form.role,
      })
    }
    router.push({ name: 'staff' })
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      errorMessage.value = err.response.data.message || 'Vui lòng kiểm tra lại thông tin.'
    } else if (err.response?.status === 403) {
      errorMessage.value = 'Bạn không có quyền thực hiện hành động này.'
    } else {
      errorMessage.value = 'Lưu thông tin không thành công.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ isEdit ? 'Sửa nhân viên' : 'Thêm nhân viên' }}</h1>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="notFound" class="alert alert-error">Không tìm thấy nhân viên.</p>

    <form v-else class="card form-card" @submit.prevent="submit">
      <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>

      <div class="form-group">
        <label>Họ tên *</label>
        <input v-model="form.user_name" type="text" required />
        <small v-if="fieldErrors.user_name" class="field-error">{{ fieldErrors.user_name[0] }}</small>
      </div>

      <div class="form-group">
        <label>Email {{ isEdit ? '' : '*' }}</label>
        <input v-model="form.user_email" type="email" :disabled="isEdit" :required="!isEdit" />
        <small v-if="fieldErrors.user_email" class="field-error">{{ fieldErrors.user_email[0] }}</small>
        <small v-else-if="isEdit">Email không thể thay đổi.</small>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Điện thoại</label>
          <input v-model="form.user_phone" type="text" />
          <small v-if="fieldErrors.user_phone" class="field-error">{{ fieldErrors.user_phone[0] }}</small>
        </div>
        <div class="form-group">
          <label>Địa chỉ</label>
          <input v-model="form.user_address" type="text" />
          <small v-if="fieldErrors.user_address" class="field-error">{{ fieldErrors.user_address[0] }}</small>
        </div>
      </div>

      <template v-if="!isEdit">
        <div class="form-group">
          <label>Mật khẩu * (ít nhất 6 ký tự)</label>
          <input v-model="form.password" type="password" minlength="6" required />
          <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</small>
        </div>
        <div class="form-group">
          <label>Vai trò</label>
          <select v-model.number="form.role">
            <option :value="2">Nhân viên</option>
            <option :value="1">Quản trị viên</option>
          </select>
        </div>
      </template>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit" :disabled="submitting">
          {{ submitting ? 'Đang lưu...' : 'Lưu' }}
        </button>
        <RouterLink :to="{ name: 'staff' }" class="btn btn-outline">Hủy</RouterLink>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form-card {
  padding: 24px;
  max-width: 560px;
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}
</style>
