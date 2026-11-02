<template>
  <div class="container">
    <section class="hero">
      <div class="txt">
        <span class="tag">{{ hero?.title || 'BỘ SƯU TẬP THU ĐÔNG 2026' }}</span>
        <h1>Bước đi tự tin,<br />từng nhịp chạy.</h1>
        <p>Hàng trăm mẫu giày chính hãng từ Nike, Adidas, Asics, Vans… giảm đến 40% cho khách hàng mới.</p>
        <NuxtLink class="btn brand" :to="hero?.link || '/san-pham'">Mua ngay →</NuxtLink>
        <NuxtLink class="btn ghost" to="/khuyen-mai" style="margin-left:8px">Xem khuyến mãi</NuxtLink>
      </div>
      <div class="shoe"><img v-if="hero?.image" :src="hero.image" alt="" /><span v-else>👟</span></div>
    </section>

    <div class="perks">
      <div class="perk"><b>🚚 Giao nhanh 2-4 ngày</b>Toàn quốc, kiểm tra hàng khi nhận</div>
      <div class="perk"><b>🔄 Đổi size miễn phí</b>Trong 7 ngày kể từ khi nhận</div>
      <div class="perk"><b>✅ Chính hãng 100%</b>Hoàn tiền 200% nếu phát hiện hàng giả</div>
      <div class="perk"><b>💳 Thanh toán linh hoạt</b>COD hoặc ví MoMo</div>
    </div>

    <section class="sec">
      <div class="sec-h"><h2>Danh mục nổi bật</h2><NuxtLink to="/san-pham">Xem tất cả</NuxtLink></div>
      <div class="cats"><NuxtLink v-for="(c, i) in home?.categories" :key="c.id" class="cat" :to="`/danh-muc/${c.slug}`">{{ c.name }}<span>{{ icons[i % 4] }}</span></NuxtLink></div>
    </section>

    <section class="sec">
      <div class="sec-h"><h2>Hàng mới về</h2><NuxtLink to="/san-pham?sort=newest">Xem thêm</NuxtLink></div>
      <ProductGrid :items="home?.new_arrivals ?? []" />
    </section>

    <section v-if="home?.flash_sale" class="promo">
      <div><h2>{{ home.flash_sale.name }}</h2><p>Giảm {{ home.flash_sale.type === 'percent' ? home.flash_sale.value + '%' : money(home.flash_sale.value) }} · Số lượng có hạn</p></div>
      <NuxtLink class="btn brand" to="/khuyen-mai">Săn sale ngay</NuxtLink>
    </section>

    <section class="sec">
      <div class="sec-h"><h2>Bán chạy nhất</h2><NuxtLink to="/san-pham?sort=best_seller">Xem thêm</NuxtLink></div>
      <ProductGrid :items="home?.best_sellers ?? []" />
    </section>

    <section class="sec">
      <div class="sec-h"><h2>Thương hiệu</h2></div>
      <div class="cats"><NuxtLink v-for="b in (home?.brands ?? []).slice(0, 8)" :key="b.id" class="cat" :to="`/san-pham?brand=${b.slug}`">{{ b.name.toUpperCase() }}</NuxtLink></div>
    </section>

    <section class="promo" style="background:#e8472b">
      <div><h2>Nhận ngay voucher 100.000đ</h2><p style="color:#ffe0d9">Đăng ký email để nhận ưu đãi và tin giày mới.</p></div>
      <form class="nl" @submit.prevent="subscribe"><input v-model="email" type="email" required placeholder="Email của bạn" /><button class="btn" type="submit">Đăng ký</button></form>
    </section>
    <div v-if="msg" class="okmsg" style="margin-top:12px">{{ msg }}</div>
  </div>
</template>

<script setup lang="ts">
const { $api } = useNuxtApp()
const { data: home } = await useAsyncData('home', async () => (await $api('/home')).data)
const hero = computed(() => home.value?.banners?.find((b: any) => b.position === 'hero'))
const icons = ['👟', '🏃', '👞', '🩴']
const email = ref(''); const msg = ref('')
async function subscribe() {
  await $api('/newsletter', { method: 'POST', body: { email: email.value } })
  msg.value = 'Cảm ơn bạn! Voucher sẽ được gửi qua email.'; email.value = ''
}
useSeoMeta({ title: 'Giày chính hãng', ogTitle: 'SoleHouse – Giày chính hãng' })
</script>
