<script setup>
import { onMounted, ref } from 'vue'
import { FwbButton, FwbSpinner } from 'flowbite-vue'

import { useCartStore } from '@/stores/cart'
import { formatPrice, productImageUrl } from '@/utils/format'

const cart = useCartStore()
const busyId = ref(null)

onMounted(() => {
  cart.fetchCart()
})

function onImgError(e) {
  e.target.src = '/placeholder.svg'
}

async function changeQuantity(item, newQuantity) {
  if (newQuantity < 1 || newQuantity === item.product_quantity) return
  busyId.value = item.cart_id
  try {
    await cart.updateItem(item.cart_id, newQuantity)
  } finally {
    busyId.value = null
  }
}

async function removeItem(item) {
  busyId.value = item.cart_id
  try {
    await cart.removeItem(item.cart_id)
  } finally {
    busyId.value = null
  }
}
</script>

<template>
  <div>
    <h2 class="mb-5 font-display text-2xl text-ink-900">Giỏ hàng của bạn</h2>

    <div v-if="cart.loading" class="flex justify-center py-16">
      <FwbSpinner size="8" />
    </div>

    <div v-else-if="cart.items.length === 0" class="py-16 text-center text-ink-400">
      <p>Giỏ hàng của bạn đang trống.</p>
      <RouterLink to="/" class="mt-2 inline-block font-semibold text-accent-600 hover:text-accent-700">
        Tiếp tục mua sắm
      </RouterLink>
    </div>

    <template v-else>
      <div class="divide-y divide-ink-200 rounded-xl border border-ink-200 bg-white">
        <div
          v-for="item in cart.items"
          :key="item.cart_id"
          class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center"
        >
          <img
            class="h-20 w-20 shrink-0 rounded-lg bg-ink-100 object-cover"
            :src="productImageUrl(item) || '/placeholder.svg'"
            :alt="item.product_name"
            @error="onImgError"
          />

          <div class="flex-1">
            <p class="font-semibold text-ink-800">{{ item.product_name }}</p>
            <p class="text-sm text-ink-500">{{ formatPrice(item.product_price) }} / sản phẩm</p>
          </div>

          <div class="flex items-center gap-4">
            <div class="flex items-center rounded-lg border border-ink-300">
              <button
                type="button"
                class="px-2.5 py-1.5 text-ink-600 hover:text-accent-600 disabled:opacity-40"
                :disabled="busyId === item.cart_id"
                @click="changeQuantity(item, item.product_quantity - 1)"
              >
                −
              </button>
              <input
                type="number"
                min="1"
                :value="item.product_quantity"
                :disabled="busyId === item.cart_id"
                class="w-12 border-x border-ink-300 py-1.5 text-center focus:outline-none"
                @change="changeQuantity(item, Number($event.target.value))"
              />
              <button
                type="button"
                class="px-2.5 py-1.5 text-ink-600 hover:text-accent-600 disabled:opacity-40"
                :disabled="busyId === item.cart_id"
                @click="changeQuantity(item, item.product_quantity + 1)"
              >
                +
              </button>
            </div>

            <span class="w-24 text-right font-display text-accent-600">
              {{ formatPrice(item.product_price * item.product_quantity) }}
            </span>

            <FwbButton
              color="red"
              size="sm"
              :disabled="busyId === item.cart_id"
              @click="removeItem(item)"
            >
              Xóa
            </FwbButton>
          </div>
        </div>
      </div>

      <div class="mt-6 flex flex-col items-end gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="text-lg">
          Tổng tiền cần thanh toán:
          <span class="font-display text-xl text-accent-600">{{ formatPrice(cart.total) }}</span>
        </div>
        <FwbButton
          tag="router-link"
          :to="{ name: 'checkout' }"
          size="lg"
          class="bg-accent-600 hover:bg-accent-700 focus:ring-accent-300"
        >
          Thanh toán
        </FwbButton>
      </div>
    </template>
  </div>
</template>
