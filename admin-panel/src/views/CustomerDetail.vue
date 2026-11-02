<template>
  <div v-if="c">
    <div class="ph"><div class="crumb"><router-link to="/customers">Khách hàng</router-link> › {{ c.full_name }}<h1>{{ c.full_name }} <span class="pill" :class="c.status === 'active' ? 'p-green' : 'p-red'">{{ c.status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span></h1></div>
      <div class="acts"><button v-if="auth.can('customer:write')" class="btn" :class="c.status === 'active' ? 'danger' : 'pri'" @click="toggle">{{ c.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa' }}</button></div></div>
    <div class="kpis"><div class="kpi"><small>Số đơn</small><b>{{ c.stats.orders }}</b></div><div class="kpi"><small>Tổng chi tiêu</small><b>{{ money(c.stats.total_spent) }}</b></div><div class="kpi"><small>Đơn hủy</small><b>{{ c.stats.cancelled }}</b></div><div class="kpi"><small>Tham gia</small><b style="font-size:18px">{{ dOnly(c.created_at) }}</b></div></div>
    <div class="cols side"><div class="card tcard"><h3 style="padding:16px 16px 0">Lịch sử đơn hàng</h3>
      <table class="tb"><thead><tr><th>Mã đơn</th><th class="r">Tổng</th><th>Trạng thái</th></tr></thead><tbody><tr v-for="o in c.orders" :key="o.id"><td><router-link class="lnk" :to="`/orders/${o.id}`"><b>{{ o.code }}</b></router-link><small>{{ dt(o.placed_at) }}</small></td><td class="r">{{ money(o.grand_total) }}</td><td><Pill :map="ORDER_STATUS" :value="o.status" /></td></tr><tr v-if="!c.orders.length"><td colspan="3" class="loading">Chưa có đơn</td></tr></tbody></table></div>
      <div><div class="card"><h3>Liên hệ</h3>{{ c.email || '—' }} <span v-if="c.email_verified" class="pill p-green">đã xác thực</span><br />{{ c.phone || '—' }}</div>
        <div class="card"><h3>Địa chỉ</h3><div v-for="a in c.addresses" :key="a.id" style="margin-bottom:10px"><b>{{ a.receiver_name }}</b> · {{ a.phone }} <span v-if="a.is_default" class="pill p-blue">Mặc định</span><br /><span class="muted">{{ a.street }}</span></div><span v-if="!c.addresses.length" class="muted">Chưa có.</span></div></div></div>
  </div>
  <div v-else class="loading">Đang tải...</div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { money, dt, dOnly, ORDER_STATUS } from '../api'
import { useAuthStore } from '../stores/auth'
import Pill from '../components/Pill.vue'
const route = useRoute(); const auth = useAuthStore(); const c = ref(null)
const load = async () => (c.value = (await api.get(`/admin/customers/${route.params.id}`)).data.data)
async function toggle() { const to = c.value.status === 'active' ? 'blocked' : 'active'; if (to === 'blocked' && !confirm('Khóa tài khoản này? Khách sẽ bị đăng xuất khỏi mọi thiết bị.')) return; await api.patch(`/admin/customers/${c.value.id}/status`, { status: to }); load() }
onMounted(load)
</script>
