<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { http } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@/utils/format'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()

const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
  note: '',
})

const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})
const result = ref(null)

onMounted(() => {
  if (auth.customer) {
    form.name = auth.customer.user_name || ''
    form.phone = auth.customer.user_phone || ''
    form.email = auth.customer.user_email || ''
    form.address = auth.customer.user_address || ''
  }
  if (cart.items.length === 0) {
    cart.fetchCart()
  }
})

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}
  try {
    const { data } = await http.post('/api/checkout', {
      name: form.name,
      phone: form.phone,
      email: form.email,
      address: form.address,
      note: form.note || null,
      payments: '0',
    })
    result.value = data.data
    cart.clearLocally()
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      errorMessage.value = err.response.data.message || 'Vui lòng kiểm tra lại thông tin.'
    } else {
      errorMessage.value = 'Đặt hàng không thành công. Vui lòng thử lại.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="checkout">
    <h2 class="page-title">Thanh toán</h2>

    <div v-if="result" class="card success-card">
      <h3>Đặt hàng thành công</h3>
      <p>Mã đơn hàng: <strong>{{ result.mahang }}</strong></p>
      <p>Tổng thanh toán: <strong>{{ formatPrice(result.total_payment) }}</strong></p>
      <RouterLink class="btn btn-primary" to="/">Về trang chủ</RouterLink>
    </div>

    <div v-else class="checkout-layout">
      <form class="card checkout-form" @submit.prevent="submit">
        <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>

        <div class="form-group">
          <label>Họ tên</label>
          <input v-model="form.name" type="text" required />
          <small v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name[0] }}</small>
        </div>
        <div class="form-group">
          <label>Số điện thoại</label>
          <input v-model="form.phone" type="text" required />
          <small v-if="fieldErrors.phone" class="field-error">{{ fieldErrors.phone[0] }}</small>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input v-model="form.email" type="email" required />
          <small v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email[0] }}</small>
        </div>
        <div class="form-group">
          <label>Địa chỉ</label>
          <input v-model="form.address" type="text" required />
          <small v-if="fieldErrors.address" class="field-error">{{ fieldErrors.address[0] }}</small>
        </div>
        <div class="form-group">
          <label>Ghi chú</label>
          <textarea v-model="form.note" rows="3" placeholder="Lưu ý cho người bán"></textarea>
        </div>

        <div class="form-group">
          <label>Hình thức thanh toán</label>
          <select disabled>
            <option>Thanh toán khi nhận hàng (COD)</option>
          </select>
          <small>Momo / VNPay / PayPal sẽ sớm được hỗ trợ.</small>
        </div>

        <button class="btn btn-primary" type="submit" :disabled="submitting || cart.items.length === 0">
          {{ submitting ? 'Đang xử lý...' : 'Hoàn tất thanh toán' }}
        </button>
        <p v-if="cart.items.length === 0" class="empty-state">Giỏ hàng của bạn đang trống.</p>
      </form>

      <div class="card order-summary">
        <h3>Sản phẩm</h3>
        <ul>
          <li v-for="item in cart.items" :key="item.cart_id">
            <span>x{{ item.product_quantity }} {{ item.product_name }}</span>
            <span>{{ formatPrice(item.product_price * item.product_quantity) }}</span>
          </li>
        </ul>
        <div class="summary-total">
          <span>Tổng thanh toán</span>
          <span class="price">{{ formatPrice(cart.total) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.checkout-layout {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 24px;
}

.checkout-form,
.order-summary {
  padding: 24px;
}

.field-error {
  color: var(--color-accent);
}

.order-summary ul {
  list-style: none;
  padding: 0;
  margin: 0 0 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.order-summary li {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  font-weight: 700;
  border-top: 1px solid var(--color-border);
  padding-top: 12px;
}

.success-card {
  padding: 32px;
  text-align: center;
}

@media (max-width: 720px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }
}
</style>
