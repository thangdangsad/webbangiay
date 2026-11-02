<template>
  <NuxtLayout name="default">
    <div class="container">
      <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Tài khoản</div>
      <div class="acc">
        <aside class="menu">
          <NuxtLink to="/tai-khoan/thong-tin" active-class="on">Thông tin cá nhân</NuxtLink>
          <NuxtLink to="/tai-khoan/don-hang" active-class="on">Đơn hàng của tôi</NuxtLink>
          <NuxtLink to="/tai-khoan/dia-chi" active-class="on">Sổ địa chỉ</NuxtLink>
          <NuxtLink to="/tai-khoan/yeu-thich" active-class="on">Yêu thích</NuxtLink>
          <a href="#" @click.prevent="auth.logout()">Đăng xuất</a>
        </aside>
        <section><slot /></section>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
const auth = useAuthStore()
</script>
