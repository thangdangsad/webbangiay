<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { FwbAlert, FwbButton, FwbInput, FwbTextarea } from 'flowbite-vue'

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
  <div>
    <h2 class="mb-5 font-display text-2xl text-ink-900">Thanh toán</h2>

    <div v-if="result" class="rounded-xl border border-ink-200 bg-white p-8 text-center">
      <h3 class="font-display text-xl text-ink-900">Đặt hàng thành công</h3>
      <p class="mt-3 text-ink-600">
        Mã đơn hàng: <strong class="text-ink-900">{{ result.mahang }}</strong>
      </p>
      <p class="text-ink-600">
        Tổng thanh toán:
        <strong class="text-accent-600">{{ formatPrice(result.total_payment) }}</strong>
      </p>
      <FwbButton
        tag="router-link"
        :to="{ name: 'home' }"
        class="mt-5 bg-accent-600 hover:bg-accent-700 focus:ring-accent-300"
      >
        Về trang chủ
      </FwbButton>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-[1.3fr_1fr]">
      <form class="rounded-xl border border-ink-200 bg-white p-6" @submit.prevent="submit">
        <FwbAlert v-if="errorMessage" type="danger" class="mb-4">{{ errorMessage }}</FwbAlert>

        <div class="space-y-4">
          <FwbInput
            v-model="form.name"
            label="Họ tên"
            required
            :validation-status="fieldErrors.name ? 'error' : undefined"
            :validation-message="fieldErrors.name?.[0]"
          />
          <FwbInput
            v-model="form.phone"
            label="Số điện thoại"
            required
            :validation-status="fieldErrors.phone ? 'error' : undefined"
            :validation-message="fieldErrors.phone?.[0]"
          />
          <FwbInput
            v-model="form.email"
            type="email"
            label="Email"
            required
            :validation-status="fieldErrors.email ? 'error' : undefined"
            :validation-message="fieldErrors.email?.[0]"
          />
          <FwbInput
            v-model="form.address"
            label="Địa chỉ"
            required
            :validation-status="fieldErrors.address ? 'error' : undefined"
            :validation-message="fieldErrors.address?.[0]"
          />
          <FwbTextarea v-model="form.note" label="Ghi chú" rows="3" placeholder="Lưu ý cho người bán" />

          <div>
            <label class="mb-1 block text-sm font-medium text-ink-800">Hình thức thanh toán</label>
            <select disabled class="w-full rounded-lg border border-ink-300 bg-ink-100 px-3 py-2.5 text-sm text-ink-500">
              <option>Thanh toán khi nhận hàng (COD)</option>
            </select>
            <p class="mt-1 text-xs text-ink-400">Momo / VNPay / PayPal sẽ sớm được hỗ trợ.</p>
          </div>
        </div>

        <FwbButton
          type="submit"
          size="lg"
          class="mt-6 w-full bg-accent-600 hover:bg-accent-700 focus:ring-accent-300"
          :disabled="submitting || cart.items.length === 0"
        >
          {{ submitting ? 'Đang xử lý...' : 'Hoàn tất thanh toán' }}
        </FwbButton>
        <p v-if="cart.items.length === 0" class="mt-3 text-center text-sm text-ink-400">
          Giỏ hàng của bạn đang trống.
        </p>
      </form>

      <div class="h-fit rounded-xl border border-ink-200 bg-white p-6">
        <h3 class="mb-4 font-display text-lg text-ink-900">Sản phẩm</h3>
        <ul class="flex flex-col gap-2">
          <li
            v-for="item in cart.items"
            :key="item.cart_id"
            class="flex justify-between text-sm text-ink-600"
          >
            <span>x{{ item.product_quantity }} {{ item.product_name }}<span v-if="item.size"> · Size {{ item.size }}</span></span>
            <span class="font-medium text-ink-800">{{ formatPrice(item.product_price * item.product_quantity) }}</span>
          </li>
        </ul>
        <div class="mt-4 flex justify-between border-t border-ink-200 pt-3 font-semibold">
          <span>Tổng thanh toán</span>
          <span class="text-accent-600">{{ formatPrice(cart.total) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
