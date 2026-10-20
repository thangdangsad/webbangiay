<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const submitting = ref(false)
const errorMessage = ref('')

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  try {
    await auth.login({ email: form.email, password: form.password })
    router.push(route.query.redirect || { name: 'dashboard' })
  } catch (err) {
    if (err.response?.status === 401 || err.response?.status === 422) {
      errorMessage.value =
        err.response.data.errors?.email?.[0] || err.response.data.message || 'Đăng nhập thất bại.'
    } else {
      errorMessage.value = 'Đăng nhập thất bại. Vui lòng thử lại.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <form class="card auth-form" @submit.prevent="submit">
      <h2>Czuka Admin</h2>
      <p class="subtitle">Đăng nhập dành cho quản trị viên &amp; nhân viên</p>
      <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>

      <div class="form-group">
        <label>Email</label>
        <input v-model="form.email" type="email" required autocomplete="username" />
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input v-model="form.password" type="password" required autocomplete="current-password" />
      </div>

      <button class="btn btn-primary" type="submit" :disabled="submitting">
        {{ submitting ? 'Đang đăng nhập...' : 'Đăng nhập' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--sidebar-bg);
}

.auth-form {
  width: 100%;
  max-width: 400px;
  padding: 32px;
}

.auth-form h2 {
  margin: 0 0 4px;
  text-align: center;
}

.subtitle {
  text-align: center;
  color: var(--color-muted);
  font-size: 13px;
  margin: 0 0 20px;
}

.auth-form .btn {
  width: 100%;
}
</style>
