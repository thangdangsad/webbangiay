<script setup>
import { onMounted, ref } from 'vue'

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
  if (newQuantity === item.product_quantity) return
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
    <h2 class="page-title">Giỏ hàng của bạn</h2>

    <p v-if="cart.loading">Đang tải...</p>
    <p v-else-if="cart.items.length === 0" class="empty-state">
      Giỏ hàng của bạn đang trống. <RouterLink to="/">Tiếp tục mua sắm</RouterLink>
    </p>

    <template v-else>
      <table class="cart-table">
        <thead>
          <tr>
            <th></th>
            <th>Sản phẩm</th>
            <th>Số lượng</th>
            <th>Giá</th>
            <th>Tổng tiền</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in cart.items" :key="item.cart_id">
            <td>
              <img
                class="thumb"
                :src="productImageUrl(item) || '/placeholder.svg'"
                :alt="item.product_name"
                @error="onImgError"
              />
            </td>
            <td>{{ item.product_name }}</td>
            <td>
              <input
                type="number"
                min="1"
                :value="item.product_quantity"
                :disabled="busyId === item.cart_id"
                @change="changeQuantity(item, Number($event.target.value))"
              />
            </td>
            <td>{{ formatPrice(item.product_price) }}</td>
            <td>{{ formatPrice(item.product_price * item.product_quantity) }}</td>
            <td>
              <button
                class="btn btn-danger"
                :disabled="busyId === item.cart_id"
                @click="removeItem(item)"
              >
                Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="cart-summary">
        <div class="total">
          Tổng tiền cần thanh toán: <span class="price">{{ formatPrice(cart.total) }}</span>
        </div>
        <RouterLink to="/checkout" class="btn btn-primary">Thanh toán</RouterLink>
      </div>
    </template>
  </div>
</template>

<style scoped>
.cart-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-surface);
}

.cart-table th,
.cart-table td {
  padding: 12px;
  border-bottom: 1px solid var(--color-border);
  text-align: left;
}

.thumb {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 6px;
  background: #f1f3f5;
}

.cart-table input[type='number'] {
  width: 60px;
  padding: 6px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
}

.cart-summary {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 24px;
  margin-top: 24px;
}

.total {
  font-size: 18px;
}
</style>
