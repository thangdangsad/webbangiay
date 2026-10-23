<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'

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
    <Card class="auth-card">
      <template #title>
        <div class="auth-title">
          <i class="pi pi-box"></i>
          <span>Czuka Admin</span>
        </div>
        <p class="auth-subtitle">Đăng nhập dành cho quản trị viên &amp; nhân viên</p>
      </template>
      <template #content>
        <form class="auth-form" @submit.prevent="submit">
          <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

          <div class="field">
            <label for="email">Email</label>
            <InputText
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="username"
              fluid
            />
          </div>
          <div class="field">
            <label for="password">Mật khẩu</label>
            <Password
              id="password"
              v-model="form.password"
              :feedback="false"
              toggle-mask
              required
              autocomplete="current-password"
              fluid
            />
          </div>

          <Button type="submit" :loading="submitting" label="Đăng nhập" fluid />
        </form>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  padding: 16px;
}

.auth-card {
  width: 100%;
  max-width: 400px;
}

.auth-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-size: 22px;
}

.auth-subtitle {
  text-align: center;
  color: var(--p-text-muted-color);
  font-size: 13px;
  font-weight: 400;
  margin: 6px 0 0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 8px;
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
</style>
