<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="account-page">
    <h2 class="page-title">Tài khoản của tôi</h2>

    <div v-if="auth.customer" class="card account-card">
      <dl>
        <dt>Họ tên</dt>
        <dd>{{ auth.customer.user_name }}</dd>
        <dt>Email</dt>
        <dd>{{ auth.customer.user_email }}</dd>
        <dt>Số điện thoại</dt>
        <dd>{{ auth.customer.user_phone }}</dd>
        <dt>Địa chỉ</dt>
        <dd>{{ auth.customer.user_address }}</dd>
      </dl>
      <p class="hint">Lịch sử đơn hàng sẽ sớm được bổ sung.</p>
    </div>
  </div>
</template>

<style scoped>
.account-card {
  padding: 28px;
  max-width: 480px;
}

dl {
  display: grid;
  grid-template-columns: 140px 1fr;
  row-gap: 10px;
}

dt {
  font-weight: 600;
  color: var(--color-muted);
}

dd {
  margin: 0;
}

.hint {
  margin-top: 20px;
  color: var(--color-muted);
  font-size: 14px;
}
</style>
