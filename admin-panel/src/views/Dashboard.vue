<template>
  <div v-if="d">
    <div class="ph"><div><h1>Dashboard</h1><p>Tổng quan hoạt động cửa hàng</p></div>
      <div class="acts"><select v-model="range" class="f" style="width:auto" @change="load"><option value="today">Hôm nay</option><option value="7d">7 ngày</option><option value="30d">30 ngày</option><option value="90d">90 ngày</option></select></div></div>
    <div class="kpis">
      <div class="kpi"><small>Doanh thu</small><b>{{ money(d.kpi.revenue) }}</b></div>
      <div class="kpi"><small>Số đơn</small><b>{{ d.kpi.orders }}</b></div>
      <div class="kpi" :class="{ warn: d.kpi.pending_orders > 0 }"><small>Đơn chờ xử lý</small><b>{{ d.kpi.pending_orders }}</b></div>
      <div class="kpi"><small>Khách mới</small><b>{{ d.kpi.new_customers }}</b></div>
    </div>
    <div class="cols">
      <div class="card"><h3>Doanh thu theo ngày</h3>
        <div class="chart-wrap"><div class="chart"><div v-for="s in d.series" :key="s.date" :style="{ height: h(s.revenue) + '%' }" :title="`${s.date}: ${money(s.revenue)} (${s.orders} đơn)`"><span v-if="d.series.length <= 14">{{ s.date.slice(8) }}/{{ s.date.slice(5, 7) }}</span></div></div></div></div>
      <div class="card"><h3>Bán chạy <router-link to="/reports">Xem báo cáo</router-link></h3>
        <div v-if="!d.top_products.length" class="muted">Chưa có dữ liệu.</div>
        <div v-for="(p, i) in d.top_products" :key="i" style="display:flex;justify-content:space-between;margin:10px 0"><span>{{ i + 1 }}. {{ p.product_name }}</span><b>{{ p.qty }}</b></div></div>
    </div>
    <div class="cols eq">
      <div class="card"><h3>Cảnh báo tồn kho thấp <router-link v-if="auth.can('inventory:read')" to="/inventory">Xem kho</router-link></h3>
        <div v-if="!d.low_stock.length" class="muted">Không có cảnh báo.</div>
        <div v-for="l in d.low_stock" :key="l.sku" style="display:flex;justify-content:space-between;margin:8px 0"><span>{{ l.name }} <small class="muted">(size {{ l.size }})</small></span><span class="pill" :class="l.available <= 0 ? 'p-red' : 'p-yellow'">Còn {{ l.available }}</span></div></div>
      <div class="card"><h3>Đơn mới nhất <router-link to="/orders">Tất cả</router-link></h3>
        <div v-for="o in d.latest_orders" :key="o.id" style="display:flex;justify-content:space-between;align-items:center;margin:10px 0"><router-link class="lnk" :to="`/orders/${o.id}`"><b>{{ o.code }}</b></router-link><span>{{ money(o.grand_total) }}</span><Pill :map="ORDER_STATUS" :value="o.status" /></div></div>
    </div>
  </div>
  <div v-else class="loading">Đang tải...</div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api, { money, ORDER_STATUS } from '../api'
import { useAuthStore } from '../stores/auth'
import Pill from '../components/Pill.vue'
const auth = useAuthStore(); const d = ref(null); const range = ref('7d')
const max = computed(() => Math.max(1, ...(d.value?.series || []).map((s) => s.revenue)))
const h = (v) => Math.max(1, Math.round((v / max.value) * 100))
async function load() { d.value = (await api.get('/admin/dashboard/summary', { params: { range: range.value } })).data.data }
onMounted(load)
</script>
