<template>
  <div>
    <div class="topbar">Miễn phí vận chuyển đơn từ {{ money(info?.free_ship_threshold ?? 1000000) }} · Đổi trả trong {{ info?.return_days ?? 7 }} ngày</div>
    <header class="site">
      <div class="container hdr">
        <NuxtLink class="logo" to="/">Sole<b>House</b></NuxtLink>
        <nav class="main">
          <NuxtLink to="/san-pham?gender=men">Nam</NuxtLink>
          <NuxtLink to="/san-pham?gender=women">Nữ</NuxtLink>
          <NuxtLink to="/san-pham?gender=kids">Trẻ em</NuxtLink>
          <NuxtLink to="/san-pham">Thương hiệu</NuxtLink>
          <NuxtLink to="/khuyen-mai">Sale 🔥</NuxtLink>
        </nav>
        <form class="search" @submit.prevent="doSearch"><input v-model="q" placeholder="Tìm giày, thương hiệu..." /></form>
        <div class="icons">
          <NuxtLink v-if="auth.loggedIn" to="/tai-khoan">{{ auth.user?.full_name.split(' ').pop() }}</NuxtLink>
          <NuxtLink v-else to="/dang-nhap">Đăng nhập</NuxtLink>
          <NuxtLink to="/tai-khoan/yeu-thich">♡</NuxtLink>
          <NuxtLink to="/gio-hang">Giỏ hàng<span v-if="cart.count" class="badge">{{ cart.count }}</span></NuxtLink>
        </div>
      </div>
    </header>
    <slot />
    <footer class="site">
      <div class="container">
        <div class="fcols">
          <div>
            <div class="logo">Sole<b>House</b></div>
            <p style="color:#6b7280;margin-top:10px;max-width:280px">Giày chính hãng, giao nhanh toàn quốc, đổi size dễ dàng.</p>
          </div>
          <div><h4>Mua sắm</h4><NuxtLink to="/san-pham?gender=men">Giày nam</NuxtLink><NuxtLink to="/san-pham?gender=women">Giày nữ</NuxtLink><NuxtLink to="/khuyen-mai">Khuyến mãi</NuxtLink></div>
          <div><h4>Hỗ trợ</h4><NuxtLink to="/huong-dan-chon-size">Hướng dẫn chọn size</NuxtLink><NuxtLink to="/chinh-sach/chinh-sach-doi-tra">Chính sách đổi trả</NuxtLink><NuxtLink to="/chinh-sach/chinh-sach-van-chuyen">Vận chuyển</NuxtLink><NuxtLink to="/tra-cuu-don-hang">Tra cứu đơn hàng</NuxtLink></div>
          <div><h4>Liên hệ</h4><NuxtLink to="/lien-he">Hotline: {{ info?.phone || '1900 0000' }}</NuxtLink><NuxtLink to="/lien-he">{{ info?.email || 'support@solehouse.vn' }}</NuxtLink><NuxtLink to="/gioi-thieu">Giới thiệu</NuxtLink></div>
        </div>
        <div class="copy">© 2026 SoleHouse. Mọi quyền được bảo lưu.</div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const cart = useCartStore()
const { $api } = useNuxtApp()
const q = ref('')
const route = useRoute()
watch(() => route.query.q, (v) => { if (route.path === '/tim-kiem') q.value = String(v ?? '') }, { immediate: true })
const { data: info } = await useAsyncData('store-info', async () => (await $api('/store-info')).data, { default: () => null })
const doSearch = () => q.value.trim() && navigateTo({ path: '/tim-kiem', query: { q: q.value.trim() } })
</script>
