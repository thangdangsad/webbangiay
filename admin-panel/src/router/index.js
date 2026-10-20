import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/Login.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/products',
    name: 'products',
    component: () => import('@/views/products/ProductList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/products/new',
    name: 'product-new',
    component: () => import('@/views/products/ProductForm.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/products/:id/edit',
    name: 'product-edit',
    component: () => import('@/views/products/ProductForm.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
  {
    path: '/categories',
    name: 'categories',
    component: () => import('@/views/categories/CategoryList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/orders',
    name: 'orders',
    component: () => import('@/views/orders/OrderList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/orders/:id',
    name: 'order-detail',
    component: () => import('@/views/orders/OrderDetail.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
  {
    path: '/customers',
    name: 'customers',
    component: () => import('@/views/customers/CustomerList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/customers/:id',
    name: 'customer-detail',
    component: () => import('@/views/customers/CustomerDetail.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
  {
    path: '/staff',
    name: 'staff',
    component: () => import('@/views/staff/StaffList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/staff/new',
    name: 'staff-new',
    component: () => import('@/views/staff/StaffForm.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/staff/:id/edit',
    name: 'staff-edit',
    component: () => import('@/views/staff/StaffForm.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
  {
    path: '/staff/:id/password',
    name: 'staff-password',
    component: () => import('@/views/staff/StaffPassword.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.ready) {
    await auth.fetchMe()
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isLoggedIn) {
    return { name: 'dashboard' }
  }

  // Admin-only route (e.g. creating staff). The API itself enforces this
  // with a 403; this is just UX so a staff (role 2) account isn't dropped
  // onto a form it can't submit.
  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'staff' }
  }

  return true
})

export default router
