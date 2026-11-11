<template>
  <div v-if="d">
    <div class="tabs mode"><a v-for="t in modes" :key="t[0]" href="#" :class="{ on: mode === t[0] }" @click.prevent="mode = t[0]">{{ t[1] }}</a></div>
    <template v-if="mode === 'overview'">
    <div class="ph"><div><h1>Bảng điều khiển</h1><p>Tổng quan hoạt động cửa hàng · {{ rangeLabel }}</p></div>
      <div class="acts"><select v-model="range" class="f" style="width:auto" @change="load"><option value="today">Hôm nay</option><option value="7d">7 ngày</option><option value="30d">30 ngày</option><option value="90d">90 ngày</option></select></div></div>
    <div class="kpis">
      <div class="kpi"><small>💰 Doanh thu</small><b>{{ money(d.kpi.revenue) }}</b><em>TB {{ money(avgDay) }}/ngày</em></div>
      <div class="kpi k2"><small>🧾 Số đơn hàng</small><b>{{ d.kpi.orders }}</b><em>Giá trị TB {{ money(d.kpi.orders ? d.kpi.revenue / d.kpi.orders : 0) }}/đơn</em></div>
      <div class="kpi k3" :class="{ warn: d.kpi.pending_orders > 0 }"><small>⏳ Đơn chờ xác nhận</small><b>{{ d.kpi.pending_orders }}</b><router-link to="/orders" class="lnk">Xử lý ngay →</router-link></div>
      <div class="kpi k4"><small>👥 Khách hàng mới</small><b>{{ d.kpi.new_customers }}</b><em>{{ rangeLabel }}</em></div>
    </div>
    <div class="cols">
      <div class="card"><h3>Doanh thu theo ngày <small class="muted">Cao nhất {{ money(max > 1 ? max : 0) }}</small></h3>
        <div class="chart-wrap"><div class="chart"><div v-for="s in d.series" :key="s.date" :style="{ height: h(s.revenue) + '%' }"><b class="v">{{ s.date.slice(8) }}/{{ s.date.slice(5, 7) }}: {{ money(s.revenue) }} · {{ s.orders }} đơn</b><span v-if="d.series.length <= 14">{{ s.date.slice(8) }}/{{ s.date.slice(5, 7) }}</span></div></div></div></div>
      <div class="card"><h3>Sản phẩm bán chạy <a href="#" @click.prevent="mode = 'report'">Xem báo cáo</a></h3>
        <div v-if="!d.top_products.length" class="muted">Chưa có dữ liệu.</div>
        <div v-for="(p, i) in d.top_products" :key="i" class="rank"><span class="n">{{ i + 1 }}</span><div>{{ p.product_name }}<div class="bar"><i :style="{ width: Math.round((p.qty / topMax) * 100) + '%' }"></i></div></div><b>{{ p.qty }} <small class="muted">đã bán</small></b></div></div>
    </div>
    <div class="cols eq">
      <div class="card"><h3>⚠️ Sắp hết hàng <router-link v-if="auth.can('inventory:read')" to="/inventory">Xem kho</router-link></h3>
        <div v-if="!d.low_stock.length" class="muted">Không có cảnh báo.</div>
        <div v-for="l in d.low_stock" :key="l.sku" style="display:flex;justify-content:space-between;margin:8px 0"><span>{{ l.name }} <small class="muted">(size {{ l.size }})</small></span><span class="pill" :class="l.available <= 0 ? 'p-red' : 'p-yellow'">Còn {{ l.available }}</span></div></div>
      <div class="card"><h3>Đơn hàng mới nhất <router-link to="/orders">Tất cả</router-link></h3>
        <div v-for="o in d.latest_orders" :key="o.id" style="display:flex;justify-content:space-between;align-items:center;margin:10px 0"><router-link class="lnk" :to="`/orders/${o.id}`"><b>{{ o.code }}</b></router-link><span>{{ money(o.grand_total) }}</span><Pill :map="ORDER_STATUS" :value="o.status" /></div></div>
    </div>
    </template>
    <Reports v-else />
  </div>
  <div v-else class="loading">Đang tải...</div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api, { money, ORDER_STATUS } from '../api'
import { useAuthStore } from '../stores/auth'
import Reports from './Reports.vue'
import { useRoute } from 'vue-router'
import Pill from '../components/Pill.vue'
const route = useRoute(); const auth = useAuthStore(); const d = ref(null); const range = ref('7d')
const max = computed(() => Math.max(1, ...(d.value?.series || []).map((s) => s.revenue)))
const RANGES = { today: 'hôm nay', '7d': '7 ngày qua', '30d': '30 ngày qua', '90d': '90 ngày qua' }
const rangeLabel = computed(() => RANGES[range.value])
const topMax = computed(() => Math.max(1, ...(d.value?.top_products || []).map((p) => Number(p.qty))))
const avgDay = computed(() => (d.value ? d.value.kpi.revenue / Math.max(1, d.value.series.length) : 0))
const h = (v) => Math.max(1, Math.round((v / max.value) * 100))
async function load() { d.value = (await api.get('/admin/dashboard/summary', { params: { range: range.value } })).data.data }
const modes = [['overview', 'Tổng quan'], ...(auth.can('report:read') ? [['report', 'Báo cáo chi tiết']] : [])]
const mode = ref(route.query.tab === 'report' && auth.can('report:read') ? 'report' : 'overview')
onMounted(load)
</script>
