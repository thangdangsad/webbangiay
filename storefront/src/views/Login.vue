<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FwbAlert, FwbButton, FwbInput } from 'flowbite-vue'

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
  <div class="flex justify-center py-8">
    <form
      class="w-full max-w-md rounded-xl border border-ink-200 bg-white p-8 shadow-sm"
      @submit.prevent="submit"
    >
      <h2 class="mb-5 text-center font-display text-2xl text-ink-900">Đăng nhập</h2>
      <FwbAlert v-if="errorMessage" type="danger" class="mb-4">{{ errorMessage }}</FwbAlert>

      <div class="space-y-4">
        <FwbInput
          v-model="form.email"
          type="email"
          label="Email"
          required
          autocomplete="username"
        />
        <FwbInput
          v-model="form.password"
          type="password"
          label="Mật khẩu"
          required
          autocomplete="current-password"
        />
      </div>

      <FwbButton
        type="submit"
        size="lg"
        class="mt-6 w-full bg-accent-600 hover:bg-accent-700 focus:ring-accent-300"
        :disabled="submitting"
      >
        {{ submitting ? 'Đang đăng nhập...' : 'Đăng nhập' }}
      </FwbButton>

      <p class="mt-4 text-center text-sm text-ink-600">
        Bạn chưa có tài khoản?
        <RouterLink to="/register" class="font-semibold text-accent-600 hover:text-accent-700">
          Đăng ký
        </RouterLink>
      </p>
    </form>
  </div>
</template>
