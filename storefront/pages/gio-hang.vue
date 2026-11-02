<template>
  <div class="container">
    <div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Giỏ hàng</div>
    <h1 style="margin-bottom:18px">Giỏ hàng ({{ cart.cart.count }})</h1>
    <div v-if="!cart.cart.items.length" class="empty"><p>Giỏ hàng của bạn đang trống.</p><NuxtLink class="btn brand" style="margin-top:14px" to="/san-pham">Tiếp tục mua sắm</NuxtLink></div>
    <div v-else class="cols">
      <div>
        <div v-for="w in cart.cart.warnings" :key="w.message" class="warnmsg">{{ w.message }}</div>
        <div v-for="it in cart.cart.items" :key="it.variant_id" class="line">
          <NuxtLink :to="`/san-pham/${it.slug}`" class="img"><img v-if="it.image" :src="it.image.sm" :alt="it.name" /><span v-else>👟</span></NuxtLink>
          <div>
            <NuxtLink :to="`/san-pham/${it.slug}`"><b>{{ it.name }}</b></NuxtLink>
            <div><small>Màu {{ it.color }} · Size {{ it.size }}</small></div>
            <div class="price" style="margin-top:6px">{{ money(it.unit_price) }}<s v-if="it.compare_at_price">{{ money(it.compare_at_price) }}</s></div>
            <div v-if="it.issue" class="errmsg" style="padding:4px 10px">{{ it.issue === 'out_of_stock' ? 'Hết hàng' : `Chỉ còn ${it.available}` }}</div>
            <div class="qty" style="margin-top:8px"><button @click="chg(it, it.qty - 1)">−</button><span>{{ it.qty }}</span><button @click="chg(it, it.qty + 1)">+</button></div>
            <button class="rm" @click="cart.remove(it.variant_id)">Xóa</button>
          </div>
          <b>{{ money(it.line_total) }}</b>
        </div>
      </div>
      <div class="box">
        <h3>Tóm tắt đơn hàng</h3>
        <div v-if="cart.cart.coupon" class="okmsg">Mã <b>{{ cart.cart.coupon.code }}</b> đã áp dụng <a href="#" @click.prevent="cart.removeCoupon()">Gỡ</a></div>
        <form v-else class="coupon" @submit.prevent="useCoupon"><input class="f" v-model="code" placeholder="Mã giảm giá" /><button class="btn sm" style="height:42px" type="submit">Áp dụng</button></form>
        <div v-if="cerr" class="errmsg">{{ cerr }}</div>
        <div class="row"><span>Tạm tính</span><b>{{ money(cart.cart.subtotal) }}</b></div>
        <div class="row"><span>Giảm giá</span><b>-{{ money(cart.cart.discount_total) }}</b></div>
        <div class="row"><span>Phí vận chuyển</span><span style="color:var(--muted)">Tính ở bước thanh toán</span></div>
        <div class="row total"><span>Tổng cộng</span><span>{{ money(cart.cart.total) }}</span></div>
        <NuxtLink class="btn brand block" to="/thanh-toan" :class="{ disabled: hasIssue }" :aria-disabled="hasIssue">Tiến hành thanh toán</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const cart = useCartStore()
const code = ref(''); const cerr = ref('')
const hasIssue = computed(() => cart.cart.items.some((i) => i.issue))
onMounted(() => cart.fetch())
async function chg(it: any, q: number) { try { await cart.setQty(it.variant_id, q) } catch (e: any) { cerr.value = e.message } }
async function useCoupon() { cerr.value = ''; try { await cart.applyCoupon(code.value) ; code.value = '' } catch (e: any) { cerr.value = e.message } }
useSeoMeta({ title: 'Giỏ hàng', robots: 'noindex' })
</script>
