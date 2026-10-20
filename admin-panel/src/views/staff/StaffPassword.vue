<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const auth = useAuthStore()
const router = useRouter()

const isSelf = computed(() => Number(auth.staff?.user_id) === Number(props.id))

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const fieldErrors = ref({})

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  fieldErrors.value = {}

  const payload = {
    password: form.password,
    password_confirmation: form.password_confirmation,
  }
  if (isSelf.value) {
    payload.current_password = form.current_password
  }

  try {
    await http.patch(`/api/admin/staff/${props.id}/password`, payload)
    successMessage.value = 'Đổi mật khẩu thành công.'
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
    setTimeout(() => router.push({ name: 'staff' }), 900)
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      errorMessage.value = err.response.data.message || 'Vui lòng kiểm tra lại thông tin.'
    } else if (err.response?.status === 403) {
      errorMessage.value = 'Bạn không có quyền thực hiện hành động này.'
    } else {
      errorMessage.value = 'Đổi mật khẩu không thành công.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Đổi mật khẩu</h1>
    </div>

    <form class="card form-card" @submit.prevent="submit">
      <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>
      <p v-if="successMessage" class="alert alert-success">{{ successMessage }}</p>
      <p v-if="!isSelf" class="hint">
        Bạn đang đặt lại mật khẩu cho một tài khoản khác (không cần mật khẩu hiện tại).
      </p>

      <div v-if="isSelf" class="form-group">
        <label>Mật khẩu hiện tại *</label>
        <input v-model="form.current_password" type="password" required autocomplete="current-password" />
        <small v-if="fieldErrors.current_password" class="field-error">{{ fieldErrors.current_password[0] }}</small>
      </div>

      <div class="form-group">
        <label>Mật khẩu mới * (ít nhất 6 ký tự)</label>
        <input v-model="form.password" type="password" minlength="6" required autocomplete="new-password" />
        <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</small>
      </div>

      <div class="form-group">
        <label>Xác nhận mật khẩu mới *</label>
        <input v-model="form.password_confirmation" type="password" minlength="6" required autocomplete="new-password" />
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit" :disabled="submitting">
          {{ submitting ? 'Đang lưu...' : 'Đổi mật khẩu' }}
        </button>
        <RouterLink :to="{ name: 'staff' }" class="btn btn-outline">Hủy</RouterLink>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form-card {
  padding: 24px;
  max-width: 480px;
}

.hint {
  color: var(--color-muted);
  font-size: 13px;
  margin-top: -6px;
  margin-bottom: 14px;
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}
</style>
