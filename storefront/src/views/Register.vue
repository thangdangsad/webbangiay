<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { FwbAlert, FwbButton, FwbInput } from 'flowbite-vue'

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
  <div class="flex justify-center py-8">
    <div
      v-if="success"
      class="w-full max-w-md rounded-xl border border-ink-200 bg-white p-8 text-center shadow-sm"
    >
      <h2 class="font-display text-2xl text-ink-900">Đăng ký thành công</h2>
      <p class="mt-2 text-ink-600">Mời bạn đăng nhập để tiếp tục.</p>
      <FwbButton
        class="mt-5 bg-accent-600 hover:bg-accent-700 focus:ring-accent-300"
        @click="router.push({ name: 'login' })"
      >
        Đến trang đăng nhập
      </FwbButton>
    </div>

    <form
      v-else
      class="w-full max-w-md rounded-xl border border-ink-200 bg-white p-8 shadow-sm"
      @submit.prevent="submit"
    >
      <h2 class="mb-5 text-center font-display text-2xl text-ink-900">Đăng ký</h2>
      <FwbAlert v-if="errorMessage" type="danger" class="mb-4">{{ errorMessage }}</FwbAlert>

      <div class="space-y-4">
        <FwbInput
          v-model="form.name"
          label="Họ tên"
          required
          :validation-status="fieldErrors.name ? 'error' : undefined"
          :validation-message="fieldErrors.name?.[0]"
        />
        <FwbInput
          v-model="form.email"
          type="email"
          label="Email"
          required
          :validation-status="fieldErrors.email ? 'error' : undefined"
          :validation-message="fieldErrors.email?.[0]"
        />
        <FwbInput
          v-model="form.phone"
          label="Số điện thoại"
          required
          :validation-status="fieldErrors.phone ? 'error' : undefined"
          :validation-message="fieldErrors.phone?.[0]"
        />
        <FwbInput
          v-model="form.address"
          label="Địa chỉ"
          required
          :validation-status="fieldErrors.address ? 'error' : undefined"
          :validation-message="fieldErrors.address?.[0]"
        />
        <FwbInput
          v-model="form.password"
          type="password"
          label="Mật khẩu"
          required
          minlength="6"
          autocomplete="new-password"
          :validation-status="fieldErrors.password ? 'error' : undefined"
          :validation-message="fieldErrors.password?.[0]"
        />
      </div>

      <FwbButton
        type="submit"
        size="lg"
        class="mt-6 w-full bg-accent-600 hover:bg-accent-700 focus:ring-accent-300"
        :disabled="submitting"
      >
        {{ submitting ? 'Đang đăng ký...' : 'Đăng ký' }}
      </FwbButton>

      <p class="mt-4 text-center text-sm text-ink-600">
        Bạn đã có tài khoản?
        <RouterLink to="/login" class="font-semibold text-accent-600 hover:text-accent-700">
          Đăng nhập
        </RouterLink>
      </p>
    </form>
  </div>
</template>
