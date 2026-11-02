import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

// meta.perm: cần ít nhất một quyền trong danh sách (RBAC phía BE vẫn là nguồn quyết định thật)
const routes = [
  { path: '/login', name: 'login', component: () => import('./views/Login.vue'), meta: { public: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: () => import('./views/Dashboard.vue'), meta: { perm: ['dashboard:read'] } },
  { path: '/products', component: () => import('./views/Products.vue'), meta: { perm: ['product:read'] } },
  { path: '/products/new', component: () => import('./views/ProductForm.vue'), meta: { perm: ['product:write'] } },
  { path: '/products/:id', component: () => import('./views/ProductForm.vue'), meta: { perm: ['product:write'] } },
  { path: '/catalog', component: () => import('./views/Catalog.vue'), meta: { perm: ['product:read'] } },
  { path: '/inventory', component: () => import('./views/Inventory.vue'), meta: { perm: ['inventory:read'] } },
  { path: '/inventory/receipts', component: () => import('./views/StockReceipt.vue'), meta: { perm: ['inventory:read'] } },
  { path: '/inventory/movements', component: () => import('./views/Movements.vue'), meta: { perm: ['inventory:read'] } },
  { path: '/orders', component: () => import('./views/Orders.vue'), meta: { perm: ['order:read'] } },
  { path: '/orders/:id', component: () => import('./views/OrderDetail.vue'), meta: { perm: ['order:read'] } },
  { path: '/returns', component: () => import('./views/Returns.vue'), meta: { perm: ['return:read'] } },
  { path: '/customers', component: () => import('./views/Customers.vue'), meta: { perm: ['customer:read'] } },
  { path: '/customers/:id', component: () => import('./views/CustomerDetail.vue'), meta: { perm: ['customer:read'] } },
  { path: '/marketing/coupons', component: () => import('./views/Coupons.vue'), meta: { perm: ['marketing:manage'] } },
  { path: '/marketing/promotions', component: () => import('./views/Promotions.vue'), meta: { perm: ['marketing:manage'] } },
  { path: '/marketing/banners', component: () => import('./views/Banners.vue'), meta: { perm: ['marketing:manage'] } },
  { path: '/reviews', component: () => import('./views/Reviews.vue'), meta: { perm: ['content:manage'] } },
  { path: '/reports', component: () => import('./views/Reports.vue'), meta: { perm: ['report:read'] } },
  { path: '/cms', component: () => import('./views/Cms.vue'), meta: { perm: ['content:manage'] } },
  { path: '/settings', component: () => import('./views/Settings.vue'), meta: { perm: ['settings:manage'] } },
  { path: '/settings/shipping', component: () => import('./views/SettingsShipping.vue'), meta: { perm: ['settings:manage'] } },
  { path: '/settings/staff', component: () => import('./views/Staff.vue'), meta: { perm: ['staff:manage'] } },
  { path: '/settings/audit-log', component: () => import('./views/AuditLog.vue'), meta: { perm: ['audit:read'] } },
  { path: '/403', component: () => import('./views/Forbidden.vue') },
  { path: '/:pathMatch(.*)*', component: () => import('./views/NotFound.vue') },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.init()
  if (to.meta.public) return auth.loggedIn && to.name === 'login' ? '/' : true
  if (!auth.loggedIn) return { name: 'login', query: { redirect: to.fullPath } }
  if (to.meta.perm && !auth.canAny(to.meta.perm)) return '/403'
})

export default router
