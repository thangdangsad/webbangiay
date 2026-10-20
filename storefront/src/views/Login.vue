<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth = useAuthStore()
const cart = useCartStore()
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
    // Logging in merges any guest cart into the customer's cart server-side.
    await cart.fetchCart()
    router.push(route.query.redirect || { name: 'home' })
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
      <h2>Đăng nhập</h2>
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

      <p class="switch-link">
        Bạn chưa có tài khoản? <RouterLink to="/register">Đăng ký</RouterLink>
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

.switch-link {
  text-align: center;
  margin-top: 16px;
}
</style>
