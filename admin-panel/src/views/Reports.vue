<template>
  <div>
    <div class="ph"><div><h1>Báo cáo</h1><p>Doanh thu, bán chạy, tồn kho, hủy/đổi trả</p></div>
      <div class="acts"><select v-model="range" class="f" style="width:auto" @change="load"><option value="7d">7 ngày</option><option value="30d">30 ngày</option><option value="90d">90 ngày</option></select><button class="btn" @click="csv">⬇ Xuất CSV</button></div></div>
    <div class="card tcard"><div class="tabs"><a v-for="t in tabs" :key="t[0]" href="#" :class="{ on: tab === t[0] }" @click.prevent="tab = t[0]; load()">{{ t[1] }}</a></div><div style="padding:18px">
      <div v-if="loading" class="loading">Đang tải...</div>
      <template v-else-if="tab === 'revenue'">
        <div class="kpis"><div class="kpi"><small>Doanh thu</small><b>{{ money(d.total) }}</b></div><div class="kpi"><small>Số đơn</small><b>{{ d.orders }}</b></div><div class="kpi"><small>Giá trị TB / đơn</small><b>{{ money(d.orders ? Math.round(d.total / d.orders) : 0) }}</b></div></div>
        <div class="chart-wrap"><div class="chart"><div v-for="s in d.series" :key="s.date" :style="{ height: Math.max(1, (s.revenue / maxRev) * 100) + '%' }" :title="`${s.date}: ${money(s.revenue)}`"><span v-if="d.series.length <= 14">{{ s.date.slice(8) }}/{{ s.date.slice(5, 7) }}</span></div></div></div>
        <h4 style="margin:18px 0 8px">Theo phương thức thanh toán</h4><div v-for="m in d.by_payment_method" :key="m.payment_method" class="hb"><span>{{ payMethod(m.payment_method) }}</span><div class="bar"><i :style="{ width: (m.revenue / (d.total || 1)) * 100 + '%' }"></i></div><b>{{ money(m.revenue) }}</b></div></template>
      <template v-else-if="tab === 'top'">
        <div class="acts" style="margin-bottom:12px"><button v-for="b in [['product', 'Sản phẩm'], ['brand', 'Thương hiệu'], ['category', 'Danh mục']]" :key="b[0]" class="btn sm" :class="{ dark: by === b[0] }" @click="by = b[0]; load()">{{ b[1] }}</button></div>
        <table class="tb"><thead><tr><th>#</th><th>Tên</th><th class="r">Số lượng</th><th class="r">Doanh thu</th></tr></thead><tbody><tr v-for="(r, i) in d" :key="i"><td>{{ i + 1 }}</td><td>{{ r.name }}</td><td class="r">{{ r.qty }}</td><td class="r">{{ money(r.revenue) }}</td></tr><tr v-if="!d.length"><td colspan="4" class="loading">Chưa có dữ liệu</td></tr></tbody></table></template>
      <template v-else-if="tab === 'inventory'">
        <div class="kpis"><div class="kpi"><small>Giá trị tồn (giá bán)</small><b>{{ money(d.retail_value) }}</b></div><div class="kpi"><small>Tổng số đôi</small><b>{{ d.units }}</b></div><div class="kpi"><small>Vốn nhập (đã ghi nhận)</small><b>{{ money(d.cost_basis) }}</b></div></div>
        <h4 style="margin:6px 0 8px">Hàng tồn lâu (không bán được 60 ngày)</h4><table class="tb"><thead><tr><th>SKU</th><th>Sản phẩm</th><th>Size</th><th class="r">Tồn</th></tr></thead><tbody><tr v-for="r in d.slow_moving" :key="r.sku"><td>{{ r.sku }}</td><td>{{ r.name }}</td><td>{{ r.size }}</td><td class="r">{{ r.on_hand }}</td></tr><tr v-if="!d.slow_moving.length"><td colspan="4" class="loading">Không có</td></tr></tbody></table></template>
      <template v-else>
        <div class="kpis"><div class="kpi"><small>Số đơn</small><b>{{ d.orders }}</b></div><div class="kpi"><small>Tỉ lệ hủy</small><b>{{ d.cancel_rate }}%</b></div><div class="kpi"><small>Tỉ lệ đổi trả</small><b>{{ d.return_rate }}%</b></div></div>
        <h4 style="margin:6px 0 8px">Lý do đổi trả phổ biến</h4><div v-for="r in d.top_reasons" :key="r.reason" class="hb"><span style="grid-column:1/3">{{ r.reason }}</span><b>{{ r.c }}</b></div><p v-if="!d.top_reasons.length" class="muted">Chưa có.</p></template>
    </div></div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api, { money, payMethod } from '../api'
const tabs = [['revenue', 'Doanh thu'], ['top', 'Bán chạy'], ['inventory', 'Tồn kho'], ['returns', 'Hủy / đổi trả']]
const tab = ref('revenue'); const range = ref('30d'); const by = ref('product'); const d = ref({}); const loading = ref(true)
const url = () => ({ revenue: 'revenue', top: 'top-products', inventory: 'inventory', returns: 'returns' })[tab.value]
const maxRev = computed(() => Math.max(1, ...(d.value.series || []).map((s) => s.revenue)))
async function load() { loading.value = true; try { d.value = (await api.get(`/admin/reports/${url()}`, { params: { range: range.value, by: by.value } })).data.data } finally { loading.value = false } }
async function csv() { const r = await api.get(`/admin/reports/${url()}`, { params: { range: range.value, by: by.value, format: 'csv' }, responseType: 'blob' }); const a = document.createElement('a'); a.href = URL.createObjectURL(r.data); a.download = `report-${tab.value}.csv`; a.click() }
onMounted(load)
</script>
