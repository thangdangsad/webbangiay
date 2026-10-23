<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Password from 'primevue/password'

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

    <Card class="form-card">
      <template #content>
        <form class="password-form" @submit.prevent="submit">
          <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>
          <Message v-if="successMessage" severity="success" :closable="false">{{ successMessage }}</Message>
          <p v-if="!isSelf" class="hint">
            Bạn đang đặt lại mật khẩu cho một tài khoản khác (không cần mật khẩu hiện tại).
          </p>

          <div v-if="isSelf" class="field">
            <label for="current_password">Mật khẩu hiện tại *</label>
            <Password
              id="current_password"
              v-model="form.current_password"
              :feedback="false"
              toggle-mask
              required
              autocomplete="current-password"
              fluid
            />
            <small v-if="fieldErrors.current_password" class="field-error">{{ fieldErrors.current_password[0] }}</small>
          </div>

          <div class="field">
            <label for="password">Mật khẩu mới * (ít nhất 6 ký tự)</label>
            <Password
              id="password"
              v-model="form.password"
              :feedback="false"
              toggle-mask
              required
              autocomplete="new-password"
              fluid
            />
            <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</small>
          </div>

          <div class="field">
            <label for="password_confirmation">Xác nhận mật khẩu mới *</label>
            <Password
              id="password_confirmation"
              v-model="form.password_confirmation"
              :feedback="false"
              toggle-mask
              required
              autocomplete="new-password"
              fluid
            />
          </div>

          <div class="form-actions">
            <Button type="submit" :loading="submitting" label="Đổi mật khẩu" icon="pi pi-check" />
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
  max-width: 480px;
}

.password-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
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

.hint {
  color: var(--p-text-muted-color);
  font-size: 13px;
  margin: -6px 0 0;
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 6px;
}
</style>
