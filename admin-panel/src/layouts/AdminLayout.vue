<template>
  <div class="app">
    <aside>
      <div class="logo">Sole<b>House</b> <span style="font-size:11px;color:#64748b">Admin</span></div>
      <template v-for="g in visible" :key="g.title">
        <h6>{{ g.title }}</h6>
        <router-link v-for="i in g.items" :key="i.to" :to="i.to" :class="{ on: isOn(i.to) }">{{ i.label }}<em v-if="badge[i.to]">{{ badge[i.to] }}</em></router-link>
      </template>
    </aside>
    <div>
      <div class="top">
        <form class="q" @submit.prevent="search"><input v-model="q" placeholder="Tìm đơn hàng theo mã / tên / SĐT..." /></form>
        <div class="me"><span class="av">{{ auth.user.full_name[0] }}</span><span>{{ auth.user.full_name }} · {{ auth.user.role.name }}</span>
          <a href="#" style="color:#6b7280;font-weight:400" @click.prevent="logout">Đăng xuất</a></div>
      </div>
      <main><slot /></main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const q = ref('')
const badge = reactive({})

const MENU = [
  { title: 'Tổng quan', items: [{ to: '/dashboard', label: '📊 Dashboard', perm: ['dashboard:read'] }] },
  { title: 'Bán hàng', items: [
    { to: '/orders', label: '🧾 Đơn hàng', perm: ['order:read'] }, { to: '/returns', label: '↩️ Đổi trả / hoàn tiền', perm: ['return:read'] },
    { to: '/customers', label: '👥 Khách hàng', perm: ['customer:read'] }, { to: '/reviews', label: '⭐ Đánh giá sản phẩm', perm: ['content:manage'] } ] },
  { title: 'Sản phẩm', items: [{ to: '/products', label: '👟 Sản phẩm', perm: ['product:read'] }, { to: '/catalog', label: '🗂️ Danh mục · Thương hiệu · Thuộc tính', perm: ['product:read'] }] },
  { title: 'Kho hàng', items: [
    { to: '/inventory', label: '📦 Tồn kho', perm: ['inventory:read'] }, { to: '/inventory/receipts', label: '📥 Phiếu nhập hàng', perm: ['inventory:read'] },
    { to: '/inventory/movements', label: '🔁 Lịch sử biến động', perm: ['inventory:read'] } ] },
  { title: 'Marketing', items: [
    { to: '/marketing/coupons', label: '🎟️ Mã giảm giá', perm: ['marketing:manage'] }, { to: '/marketing/promotions', label: '⚡ Khuyến mãi / Flash sale', perm: ['marketing:manage'] },
    { to: '/marketing/banners', label: '🖼️ Banner', perm: ['marketing:manage'] } ] },
  { title: 'Báo cáo', items: [{ to: '/reports', label: '📈 Báo cáo', perm: ['report:read'] }] },
  { title: 'Nội dung', items: [{ to: '/cms', label: '📝 Trang nội dung', perm: ['content:manage'] }] },
  { title: 'Cài đặt', items: [
    { to: '/settings', label: '⚙️ Cửa hàng & thanh toán', perm: ['settings:manage'] }, { to: '/settings/shipping', label: '🚚 Vận chuyển', perm: ['settings:manage'] },
    { to: '/settings/staff', label: '🔐 Nhân viên & phân quyền', perm: ['staff:manage'] }, { to: '/settings/audit-log', label: '🕘 Nhật ký hoạt động', perm: ['audit:read'] } ] },
]
const visible = computed(() => MENU.map((g) => ({ ...g, items: g.items.filter((i) => auth.canAny(i.perm)) })).filter((g) => g.items.length))
const isOn = (to) => route.path === to || (to !== '/dashboard' && route.path.startsWith(to + '/') && !MENU.flatMap((g) => g.items).some((i) => i.to !== to && i.to.startsWith(to + '/') && route.path.startsWith(i.to)))

async function search() { if (q.value.trim()) router.push({ path: '/orders', query: { q: q.value.trim() } }) }
async function logout() { await auth.logout(); router.push('/login') }
onMounted(async () => {
  if (auth.can('order:read')) { try { badge['/orders'] = (await api.get('/admin/orders', { params: { status: 'pending', limit: 1 } })).data.meta.total || '' } catch {} }
  if (auth.can('inventory:read')) { try { badge['/inventory'] = (await api.get('/admin/inventory', { params: { low: 1, limit: 1 } })).data.meta.total || '' } catch {} }
})
</script>
