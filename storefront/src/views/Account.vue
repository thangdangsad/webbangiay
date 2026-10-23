<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div>
    <h2 class="mb-5 font-display text-2xl text-ink-900">Tài khoản của tôi</h2>

    <div v-if="auth.customer" class="max-w-lg rounded-xl border border-ink-200 bg-white p-7">
      <dl class="grid grid-cols-[140px_1fr] gap-y-3 text-sm">
        <dt class="font-semibold text-ink-500">Họ tên</dt>
        <dd class="text-ink-800">{{ auth.customer.user_name }}</dd>
        <dt class="font-semibold text-ink-500">Email</dt>
        <dd class="text-ink-800">{{ auth.customer.user_email }}</dd>
        <dt class="font-semibold text-ink-500">Số điện thoại</dt>
        <dd class="text-ink-800">{{ auth.customer.user_phone }}</dd>
        <dt class="font-semibold text-ink-500">Địa chỉ</dt>
        <dd class="text-ink-800">{{ auth.customer.user_address }}</dd>
      </dl>
      <p class="mt-5 text-sm text-ink-400">Lịch sử đơn hàng sẽ sớm được bổ sung.</p>
    </div>
  </div>
</template>
