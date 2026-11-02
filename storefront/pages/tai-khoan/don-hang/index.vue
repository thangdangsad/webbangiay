<template>
  <div>
    <h2 style="margin-bottom:16px">Đơn hàng của tôi</h2>
    <div class="chips" style="margin-bottom:18px"><span v-for="t in tabs" :key="t[0]" class="chip" :class="{ on: status === t[0] }" @click="status = t[0]; page = 1">{{ t[1] }}</span></div>
    <div v-if="!res?.data.length" class="empty">Chưa có đơn hàng nào.</div>
    <div v-for="o in res?.data" :key="o.code" class="ord">
      <div class="h"><span><b>{{ o.code }}</b> · {{ dateTime(o.placed_at) }}</span><span class="status" :class="statusClass(o.status)">{{ statusLabel(o.status) }}</span></div>
      <div class="b"><div class="img"><img v-if="o.first_item?.image" :src="o.first_item.image.sm" /><span v-else>👟</span></div>
        <div><b>{{ o.first_item?.name }}</b><div style="color:var(--muted);font-size:13px">Size {{ o.first_item?.size }} · {{ o.item_count }} sản phẩm · {{ payLabel(o.payment_method) }}</div></div></div>
      <div class="f"><b>{{ money(o.grand_total) }}</b><NuxtLink class="btn sm ghost" :to="`/tai-khoan/don-hang/${o.code}`">Xem chi tiết</NuxtLink></div>
    </div>
    <Pager v-if="res" :page="res.meta.page" :total="res.meta.total" :limit="res.meta.limit" @go="(n) => (page = n)" />
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth', layout: 'account' })
const { $api } = useNuxtApp()
const tabs = [['', 'Tất cả'], ['pending', 'Chờ xác nhận'], ['confirmed', 'Đã xác nhận'], ['shipping', 'Đang giao'], ['completed', 'Hoàn thành'], ['cancelled', 'Đã hủy']]
const status = ref(''); const page = ref(1)
const { data: res } = await useAsyncData('my-orders', () => $api('/me/orders', { query: { status: status.value || undefined, page: page.value } }), { watch: [status, page] })
useSeoMeta({ title: 'Đơn hàng của tôi', robots: 'noindex' })
</script>
