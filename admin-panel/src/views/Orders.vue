<template>
  <div>
    <div class="ph"><div><h1>Đơn hàng</h1><p>{{ meta?.total ?? 0 }} đơn</p></div><div class="acts"><button class="btn" @click="exportCsv">⬇ Xuất CSV</button></div></div>
    <div class="card tcard">
      <div class="tabs"><a v-for="t in tabs" :key="t[0]" href="#" :class="{ on: f.status === t[0] }" @click.prevent="f.status = t[0]; reload()">{{ t[1] }} <i v-if="count(t[0]) != null">{{ count(t[0]) }}</i></a></div>
      <div class="tbar"><input v-model="f.q" class="f" placeholder="Mã đơn / tên / SĐT" @input="debounced" />
        <select v-model="f.payment_method" class="f" @change="reload"><option value="">Thanh toán</option><option value="cod">COD</option><option value="momo">MoMo</option></select>
        <select v-model="f.payment_status" class="f" @change="reload"><option value="">Trạng thái TT</option><option value="unpaid">Chưa thanh toán</option><option value="pending">Chờ thanh toán</option><option value="paid">Đã thanh toán</option><option value="refunded">Đã hoàn tiền</option></select>
        <input v-model="f.from" class="f" type="date" style="width:150px" @change="reload" /><input v-model="f.to" class="f" type="date" style="width:150px" @change="reload" /></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th class="r">Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody><tr v-for="o in rows" :key="o.id"><td><router-link class="lnk" :to="`/orders/${o.id}`"><b>{{ o.code }}</b></router-link><small>{{ dt(o.placed_at) }}</small></td><td>{{ o.customer_name }}<small>{{ o.phone }}</small></td><td class="r"><b>{{ money(o.grand_total) }}</b></td>
          <td><span class="pill p-gray">{{ payMethod(o.payment_method) }}</span> <Pill :map="PAY_STATUS" :value="o.payment_status" /></td><td><Pill :map="ORDER_STATUS" :value="o.status" /></td><td class="r"><router-link class="btn sm" :to="`/orders/${o.id}`">Xem</router-link></td></tr>
          <tr v-if="!rows.length"><td colspan="6" class="loading">Không có đơn hàng</td></tr></tbody></table>
      <Pager v-if="meta" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { money, dt, ORDER_STATUS, PAY_STATUS, payMethod } from '../api'
import Pill from '../components/Pill.vue'
import Pager from '../components/Pager.vue'
const route = useRoute()
const tabs = [['', 'Tất cả'], ['pending', 'Chờ xác nhận'], ['confirmed', 'Đã xác nhận'], ['packing', 'Đang đóng gói'], ['shipping', 'Đang giao'], ['completed', 'Hoàn thành'], ['cancelled', 'Đã hủy'], ['returned', 'Đổi trả']]
const f = reactive({ q: String(route.query.q || ''), status: '', payment_method: '', payment_status: '', from: '', to: '' })
const rows = ref([]); const meta = ref(null); const loading = ref(true); const page = ref(1)
const count = (s) => (s === '' ? Object.values(meta.value?.status_counts || {}).reduce((a, b) => a + Number(b), 0) : meta.value?.status_counts?.[s] ?? 0)
let t; const debounced = () => { clearTimeout(t); t = setTimeout(reload, 300) }
const reload = () => { page.value = 1; load() }
const params = () => Object.fromEntries(Object.entries({ ...f, page: page.value }).filter(([, v]) => v !== '' && v != null))
async function load() { loading.value = true; try { const r = (await api.get('/admin/orders', { params: params() })).data; rows.value = r.data; meta.value = r.meta } finally { loading.value = false } }
async function exportCsv() { const r = await api.get('/admin/orders', { params: { ...params(), format: 'csv' }, responseType: 'blob' }); const a = document.createElement('a'); a.href = URL.createObjectURL(r.data); a.download = 'orders.csv'; a.click() }
onMounted(load)
</script>
