import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/Home.vue') },
  {
    path: '/category/:id',
    name: 'category',
    component: () => import('@/views/Category.vue'),
    props: true,
  },
  {
    path: '/product/:id',
    name: 'product',
    component: () => import('@/views/ProductDetail.vue'),
    props: true,
  },
  { path: '/search', name: 'search', component: () => import('@/views/Search.vue') },
  { path: '/cart', name: 'cart', component: () => import('@/views/Cart.vue') },
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('@/views/Checkout.vue'),
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/Login.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/Register.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/account',
    name: 'account',
    component: () => import('@/views/Account.vue'),
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
    return { name: 'account' }
  }

  return true
})

export default router
