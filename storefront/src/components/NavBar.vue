<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { FwbBadge, FwbButton, FwbDropdown, FwbNavbarCollapse, FwbNavbarLink } from 'flowbite-vue'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()

const categories = ref([])
const searchQuery = ref('')
const mobileMenuOpen = ref(false)

onMounted(async () => {
  try {
    const { data } = await http.get('/api/categories')
    categories.value = data.data
  } catch {
    categories.value = []
  }
})

function submitSearch() {
  const q = searchQuery.value.trim()
  if (!q) return
  mobileMenuOpen.value = false
  router.push({ name: 'search', query: { q } })
}

async function doLogout() {
  mobileMenuOpen.value = false
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <header class="sticky top-0 z-30 bg-ink-900 text-white shadow-md shadow-ink-900/10">
    <!-- Main row: logo, search, cart, account -->
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3 sm:gap-4">
      <RouterLink to="/" class="font-display text-2xl tracking-widest text-white">
        CZUKA<span class="text-accent-500">.</span>
      </RouterLink>

      <form class="order-last flex w-full flex-1 gap-2 sm:order-none sm:w-auto" @submit.prevent="submitSearch">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Tìm giày, thương hiệu..."
          class="w-full rounded-full border border-ink-600 bg-ink-800 px-4 py-2 text-sm text-white placeholder-ink-400 focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500"
        />
        <FwbButton type="submit" pill class="shrink-0 bg-accent-600 hover:bg-accent-700 focus:ring-accent-300">
          Tìm
        </FwbButton>
      </form>

      <div class="ml-auto flex items-center gap-3 sm:ml-0">
        <RouterLink to="/cart" class="relative flex items-center gap-1 rounded-lg p-2 hover:bg-ink-800">
          <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.51-5.45 1.658-7.633a.75.75 0 00-.746-.868H5.106M7.5 14.25L5.106 5.357M7.5 14.25l-1.5 2.25M9.75 18.75a.75.75 0 100 1.5.75.75 0 000-1.5zM18 18.75a.75.75 0 100 1.5.75.75 0 000-1.5z"
            />
          </svg>
          <FwbBadge
            v-if="cart.itemCount > 0"
            type="danger"
            pill
            class="absolute -right-1 -top-1 bg-accent-600 px-1.5"
          >
            {{ cart.itemCount }}
          </FwbBadge>
        </RouterLink>

        <template v-if="auth.isLoggedIn">
          <FwbDropdown :text="auth.customer?.user_name || 'Tài khoản'" close-inside class="hidden sm:block">
            <FwbNavbarLink component="router-link" link-attr="to" link="/account">Tài khoản</FwbNavbarLink>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" @click.prevent="doLogout">
              Đăng xuất
            </a>
          </FwbDropdown>
        </template>
        <template v-else>
          <RouterLink to="/login" class="hidden rounded-lg px-3 py-2 text-sm font-medium hover:bg-ink-800 sm:block">
            Đăng nhập
          </RouterLink>
          <FwbButton
            tag="router-link"
            :to="{ name: 'register' }"
            size="sm"
            class="hidden bg-accent-600 hover:bg-accent-700 focus:ring-accent-300 sm:block"
          >
            Đăng ký
          </FwbButton>
        </template>

        <button
          type="button"
          class="rounded-lg p-2 hover:bg-ink-800 md:hidden"
          aria-label="Mở menu"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Category navigation (also doubles as the mobile menu) -->
    <nav class="hidden border-t border-ink-700 bg-ink-800 md:block">
      <div class="mx-auto flex max-w-7xl flex-wrap gap-5 overflow-x-auto px-4 py-2.5 text-sm">
        <RouterLink
          v-for="c in categories"
          :key="c.category_id"
          :to="`/category/${c.category_id}`"
          class="whitespace-nowrap font-medium text-ink-200 hover:text-accent-400"
        >
          {{ c.category_name }}
        </RouterLink>
      </div>
    </nav>

    <FwbNavbarCollapse v-if="mobileMenuOpen" :is-show-menu="mobileMenuOpen" class="border-t border-ink-700 bg-ink-800 md:hidden">
      <FwbNavbarLink
        v-for="c in categories"
        :key="c.category_id"
        component="router-link"
        link-attr="to"
        :link="`/category/${c.category_id}`"
        class="!text-ink-100"
        @click="mobileMenuOpen = false"
      >
        {{ c.category_name }}
      </FwbNavbarLink>
      <template v-if="auth.isLoggedIn">
        <FwbNavbarLink
          component="router-link"
          link-attr="to"
          link="/account"
          class="!text-ink-100"
          @click="mobileMenuOpen = false"
        >
          Tài khoản ({{ auth.customer?.user_name }})
        </FwbNavbarLink>
        <li>
          <a href="#" class="block rounded py-2 pl-3 pr-4 !text-ink-100 hover:bg-ink-700" @click.prevent="doLogout">
            Đăng xuất
          </a>
        </li>
      </template>
      <template v-else>
        <FwbNavbarLink component="router-link" link-attr="to" link="/login" class="!text-ink-100" @click="mobileMenuOpen = false">
          Đăng nhập
        </FwbNavbarLink>
        <FwbNavbarLink component="router-link" link-attr="to" link="/register" class="!text-ink-100" @click="mobileMenuOpen = false">
          Đăng ký
        </FwbNavbarLink>
      </template>
    </FwbNavbarCollapse>
  </header>
</template>
