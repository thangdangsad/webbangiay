<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ name: '', email: '', phone: '', address: '', password: '' })
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})
const success = ref(false)

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}
  try {
    await auth.register({ ...form })
    success.value = true
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      errorMessage.value = 'Vui lòng kiểm tra lại thông tin đăng ký.'
    } else {
      errorMessage.value = 'Đăng ký thất bại. Vui lòng thử lại.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div v-if="success" class="card auth-form success">
      <h2>Đăng ký thành công</h2>
      <p>Mời bạn đăng nhập để tiếp tục.</p>
      <button class="btn btn-primary" @click="router.push({ name: 'login' })">Đến trang đăng nhập</button>
    </div>

    <form v-else class="card auth-form" @submit.prevent="submit">
      <h2>Đăng ký</h2>
      <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>

      <div class="form-group">
        <label>Họ tên</label>
        <input v-model="form.name" type="text" required />
        <small v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name[0] }}</small>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input v-model="form.email" type="email" required />
        <small v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email[0] }}</small>
      </div>
      <div class="form-group">
        <label>Số điện thoại</label>
        <input v-model="form.phone" type="text" required />
        <small v-if="fieldErrors.phone" class="field-error">{{ fieldErrors.phone[0] }}</small>
      </div>
      <div class="form-group">
        <label>Địa chỉ</label>
        <input v-model="form.address" type="text" required />
        <small v-if="fieldErrors.address" class="field-error">{{ fieldErrors.address[0] }}</small>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input v-model="form.password" type="password" required minlength="6" autocomplete="new-password" />
        <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</small>
      </div>

      <button class="btn btn-primary" type="submit" :disabled="submitting">
        {{ submitting ? 'Đang đăng ký...' : 'Đăng ký' }}
      </button>

      <p class="switch-link">
        Bạn đã có tài khoản? <RouterLink to="/login">Đăng nhập</RouterLink>
      </p>
    </form>
  </div>
</template>

<style scoped>
.auth-page {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}

.auth-form {
  width: 100%;
  max-width: 420px;
  padding: 32px;
}

.auth-form h2 {
  margin-top: 0;
  text-align: center;
}

.field-error {
  color: var(--color-accent);
}

.switch-link {
  text-align: center;
  margin-top: 16px;
}

.success {
  text-align: center;
}
</style>
