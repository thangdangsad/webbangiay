<template>
  <div class="container" v-if="p">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › <NuxtLink to="/san-pham">Sản phẩm</NuxtLink> › <template v-if="p.category"><NuxtLink :to="`/danh-muc/${p.category.slug}`">{{ p.category.name }}</NuxtLink> › </template>{{ p.name }}</div>
    <div class="pdp">
      <div class="gal">
        <div class="main"><img v-if="current" :src="current.md" :alt="p.name" /><span v-else style="font-size:220px">👟</span></div>
        <div class="thumbs"><div v-for="(im, i) in gallery" :key="im.id" :class="{ on: i === idx }" @click="idx = i"><img :src="im.sm" :alt="im.alt" /></div></div>
      </div>
      <div>
        <div class="brand-n" style="color:var(--muted);text-transform:uppercase;font-size:13px">{{ p.brand?.name }}</div>
        <h1>{{ p.name }}</h1>
        <div style="font-size:13px;color:var(--muted)">SKU: {{ variant?.sku ?? p.variants[0]?.sku }}
          <span v-if="p.rating.count" class="rating"> · ★ {{ p.rating.avg }} ({{ p.rating.count }} đánh giá)</span></div>
        <div class="price">{{ money(shownPrice) }}<s v-if="shownCompare">{{ money(shownCompare) }}</s><span v-if="shownCompare" class="off">-{{ Math.round(((shownCompare - shownPrice) * 100) / shownCompare) }}%</span></div>

        <div class="opt"><h4>Màu sắc: <b>{{ colorName }}</b></h4>
          <div class="sw"><i v-for="c in p.colors" :key="c.id" :class="{ on: colorId === c.id }" :style="{ background: c.hex }" :title="c.name" @click="pickColor(c.id)"></i></div></div>

        <div class="opt"><h4>Chọn size (EU) <NuxtLink to="/huong-dan-chon-size">Hướng dẫn chọn size</NuxtLink></h4>
          <div class="chips"><span v-for="s in p.sizes" :key="s.id" class="chip" :class="{ on: sizeId === s.id, off: !avail(s.id) }" @click="avail(s.id) && (sizeId = s.id)">{{ s.label }}</span></div></div>

        <div class="opt"><h4>Số lượng <small v-if="variant" style="font-weight:400;color:var(--muted)">Còn {{ variant.available }} sản phẩm</small></h4>
          <div class="qty"><button @click="qty = Math.max(1, qty - 1)">−</button><span>{{ qty }}</span><button @click="qty = Math.min(variant?.available || 1, qty + 1)">+</button></div></div>

        <div v-if="err" class="errmsg">{{ err }}</div>
        <div class="buy">
          <button class="btn brand" style="flex:1" :disabled="busy" @click="add(false)">Thêm vào giỏ</button>
          <button class="btn" style="flex:1" :disabled="busy" @click="add(true)">Mua ngay</button>
          <button class="btn ghost" style="width:46px;padding:0" @click="wl.toggle(p.id)">{{ wl.has(p.id) ? '♥' : '♡' }}</button>
        </div>
        <div class="info"><div>🚚 Giao 2-4 ngày · miễn phí từ {{ money(1000000) }}</div><div>🔄 Đổi size miễn phí trong 7 ngày</div><div>✅ Hàng chính hãng, được kiểm tra khi nhận</div></div>
      </div>
    </div>

    <div class="tabs"><a v-for="t in tabs" :key="t[0]" :class="{ on: tab === t[0] }" @click="tab = t[0]">{{ t[1] }}</a></div>
    <div class="prose" style="padding:20px 0">
      <div v-if="tab === 'desc'" v-html="p.description || '<p>Đang cập nhật.</p>'"></div>
      <div v-else-if="tab === 'mat'"><p>{{ p.material || 'Đang cập nhật.' }}</p></div>
      <div v-else>
        <p v-if="!reviews?.data.length">Chưa có đánh giá nào.</p>
        <div v-for="r in reviews?.data" :key="r.id" style="border-bottom:1px solid var(--line);padding:12px 0"><b>{{ r.author }}</b> <span class="rating">{{ '★'.repeat(r.rating) }}</span><p>{{ r.content }}</p></div>
        <form v-if="auth.loggedIn" style="margin-top:16px;max-width:480px" @submit.prevent="sendReview">
          <div class="fld"><label>Đánh giá của bạn</label><select v-model="rv.rating"><option v-for="n in 5" :key="n" :value="6 - n">{{ 6 - n }} sao</option></select></div>
          <div class="fld"><textarea v-model="rv.content" placeholder="Chia sẻ trải nghiệm..."></textarea></div>
          <button class="btn sm" type="submit">Gửi đánh giá</button> <span v-if="rvMsg" style="margin-left:10px">{{ rvMsg }}</span>
        </form>
      </div>
    </div>

    <section v-if="p.related?.length" class="sec"><div class="sec-h"><h2>Sản phẩm liên quan</h2></div><ProductGrid :items="p.related" /></section>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { $api } = useNuxtApp()
const cart = useCartStore(); const wl = useWishlistStore(); const auth = useAuthStore(); const ui = useUiStore()
const { data: p, error } = await useAsyncData(`pdp-${route.params.slug}`, async () => (await $api(`/products/${route.params.slug}`)).data)
if (error.value || !p.value) throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy sản phẩm', fatal: true })
const { data: reviews, refresh: reloadReviews } = await useAsyncData(`rv-${route.params.slug}`, () => $api(`/products/${route.params.slug}/reviews`))

const colorId = ref<number>(p.value.colors[0]?.id)
const sizeId = ref<number | null>(null)
const qty = ref(1); const idx = ref(0); const err = ref(''); const busy = ref(false)
const tab = ref('desc')
const tabs = [['desc', 'Mô tả'], ['mat', 'Chất liệu'], ['rev', 'Đánh giá']]

const gallery = computed(() => {
  const own = p.value.images.filter((i: any) => i.color_id === colorId.value)
  return own.length ? own : p.value.images
})
const current = computed(() => gallery.value[idx.value] ?? p.value.image)
const variant = computed(() => p.value.variants.find((v: any) => v.color_id === colorId.value && v.size_id === sizeId.value))
const colorName = computed(() => p.value.colors.find((c: any) => c.id === colorId.value)?.name)
const shownPrice = computed(() => variant.value?.price ?? p.value.price)
const shownCompare = computed(() => variant.value ? variant.value.compare_at_price : p.value.compare_at_price)
const avail = (sid: number) => (p.value.variants.find((v: any) => v.color_id === colorId.value && v.size_id === sid)?.available ?? 0) > 0
function pickColor(id: number) { colorId.value = id; idx.value = 0; if (sizeId.value && !avail(sizeId.value)) sizeId.value = null; qty.value = 1 }
watch(sizeId, () => (qty.value = 1))

async function add(buyNow: boolean) {
  err.value = ''
  if (!variant.value) return (err.value = 'Vui lòng chọn đủ màu và size')
  busy.value = true
  try {
    await cart.add(variant.value.id, qty.value)
    ui.notify('Đã thêm vào giỏ hàng')
    if (buyNow) await navigateTo('/gio-hang')
  } catch (e: any) { err.value = e.message } finally { busy.value = false }
}

const rv = reactive({ rating: 5, content: '' }); const rvMsg = ref('')
async function sendReview() {
  try { const r = await $api(`/products/${route.params.slug}/reviews`, { method: 'POST', body: rv }); rvMsg.value = r.data.message; reloadReviews() }
  catch (e: any) { rvMsg.value = e.message }
}

useSeoMeta({ title: () => p.value.seo_title, description: () => p.value.seo_description || `${p.value.name} chính hãng – giao nhanh, đổi size dễ dàng.`, ogImage: () => p.value.image?.md })
useHead(() => ({ script: [{ type: 'application/ld+json', innerHTML: JSON.stringify({
  '@context': 'https://schema.org', '@type': 'Product', name: p.value.name, image: p.value.images.map((i: any) => i.url), brand: p.value.brand?.name,
  sku: p.value.variants[0]?.sku, offers: { '@type': 'Offer', priceCurrency: 'VND', price: p.value.price, availability: p.value.in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' },
}) }] }))
</script>
