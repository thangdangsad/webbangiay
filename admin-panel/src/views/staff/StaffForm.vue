<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import Select from 'primevue/select'
import { useToast } from 'primevue/usetoast'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  id: { type: [String, Number], default: null },
})

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()
const isEdit = computed(() => props.id !== null && props.id !== undefined)

const loading = ref(isEdit.value)
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})
const notFound = ref(false)

const ROLE_OPTIONS = [
  { label: 'Nhân viên', value: 2 },
  { label: 'Quản trị viên', value: 1 },
]

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
    toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã lưu thông tin nhân viên.', life: 3000 })
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
    <Message v-else-if="notFound" severity="error" :closable="false">Không tìm thấy nhân viên.</Message>

    <Card v-else class="form-card">
      <template #content>
        <form class="staff-form" @submit.prevent="submit">
          <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

          <div class="field">
            <label for="user_name">Họ tên *</label>
            <InputText id="user_name" v-model="form.user_name" required fluid />
            <small v-if="fieldErrors.user_name" class="field-error">{{ fieldErrors.user_name[0] }}</small>
          </div>

          <div class="field">
            <label for="user_email">Email {{ isEdit ? '' : '*' }}</label>
            <InputText
              id="user_email"
              v-model="form.user_email"
              type="email"
              :disabled="isEdit"
              :required="!isEdit"
              fluid
            />
            <small v-if="fieldErrors.user_email" class="field-error">{{ fieldErrors.user_email[0] }}</small>
            <small v-else-if="isEdit">Email không thể thay đổi.</small>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="user_phone">Điện thoại</label>
              <InputText id="user_phone" v-model="form.user_phone" fluid />
              <small v-if="fieldErrors.user_phone" class="field-error">{{ fieldErrors.user_phone[0] }}</small>
            </div>
            <div class="field">
              <label for="user_address">Địa chỉ</label>
              <InputText id="user_address" v-model="form.user_address" fluid />
              <small v-if="fieldErrors.user_address" class="field-error">{{ fieldErrors.user_address[0] }}</small>
            </div>
          </div>

          <template v-if="!isEdit">
            <div class="field">
              <label for="password">Mật khẩu * (ít nhất 6 ký tự)</label>
              <Password
                id="password"
                v-model="form.password"
                :feedback="false"
                toggle-mask
                required
                fluid
              />
              <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</small>
            </div>
            <div class="field">
              <label for="role">Vai trò</label>
              <Select id="role" v-model="form.role" :options="ROLE_OPTIONS" option-label="label" option-value="value" fluid />
            </div>
          </template>

          <div class="form-actions">
            <Button type="submit" :loading="submitting" label="Lưu" icon="pi pi-check" />
            <RouterLink :to="{ name: 'staff' }">
              <Button type="button" label="Hủy" severity="secondary" outlined />
            </RouterLink>
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.form-card {
  max-width: 560px;
}

.staff-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

@media (max-width: 560px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-weight: 600;
  font-size: 14px;
}

.field-error {
  color: var(--p-red-500);
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 6px;
}
</style>
