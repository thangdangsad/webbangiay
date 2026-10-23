<script setup>
import { computed, ref, watch } from 'vue'
import { FwbAlert, FwbBadge, FwbButton, FwbSpinner } from 'flowbite-vue'

import { http } from '@/api/client'
import ProductCard from '@/components/ProductCard.vue'
import { useCartStore } from '@/stores/cart'
import { formatPrice, productImageUrl } from '@/utils/format'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const cart = useCartStore()

const product = ref(null)
const similar = ref([])
const loading = ref(true)
const error = ref('')
const quantity = ref(1)
const adding = ref(false)
const addMessage = ref('')

const imageUrl = computed(() => (product.value ? productImageUrl(product.value) : null))

const hasDiscount = computed(() => {
  if (!product.value) return false
  const discount = Number(product.value.product_discount)
  return discount > 0 && discount < Number(product.value.product_price)
})

const displayPrice = computed(() =>
  hasDiscount.value ? product.value.product_discount : product.value?.product_price,
)

async function load(productId) {
  loading.value = true
  error.value = ''
  addMessage.value = ''
  try {
    const [productRes, similarRes] = await Promise.all([
      http.get(`/api/products/${productId}`),
      http.get(`/api/products/${productId}/similar`),
    ])
    product.value = productRes.data.data
    similar.value = similarRes.data.data
  } catch {
    error.value = 'Không tìm thấy sản phẩm.'
  } finally {
    loading.value = false
  }
}

watch(() => props.id, load, { immediate: true })

function onImgError(e) {
  e.target.src = '/placeholder.svg'
}

function stepQuantity(delta) {
  quantity.value = Math.max(1, quantity.value + delta)
}

async function addToCart() {
  adding.value = true
  addMessage.value = ''
  try {
    await cart.addItem(product.value.product_id, quantity.value)
    addMessage.value = 'Đã thêm vào giỏ hàng.'
  } catch {
    addMessage.value = 'Không thể thêm vào giỏ hàng. Vui lòng thử lại.'
  } finally {
    adding.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="loading" class="flex justify-center py-16">
      <FwbSpinner size="8" />
    </div>
    <FwbAlert v-else-if="error" type="danger">{{ error }}</FwbAlert>

    <div v-else-if="product" class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-2">
      <div class="aspect-square overflow-hidden rounded-xl bg-ink-100">
        <img
          :src="imageUrl || '/placeholder.svg'"
          :alt="product.product_name"
          class="h-full w-full object-cover"
          @error="onImgError"
        />
      </div>

      <div>
        <h1 class="font-display text-2xl text-ink-900 sm:text-3xl">{{ product.product_name }}</h1>

        <div class="mt-3 flex items-baseline gap-3">
          <span class="font-display text-3xl text-accent-600">{{ formatPrice(displayPrice) }}</span>
          <span v-if="hasDiscount" class="text-lg text-ink-400 line-through">
            {{ formatPrice(product.product_price) }}
          </span>
          <FwbBadge v-if="hasDiscount" type="danger">Sale</FwbBadge>
        </div>

        <dl class="mt-5 space-y-1 text-sm text-ink-600">
          <div v-if="product.size" class="flex gap-2">
            <dt class="font-semibold text-ink-800">Size:</dt>
            <dd>{{ product.size }}</dd>
          </div>
          <div v-if="product.category" class="flex gap-2">
            <dt class="font-semibold text-ink-800">Danh mục:</dt>
            <dd>{{ product.category.category_name }}</dd>
          </div>
        </dl>

        <p v-if="product.product_details" class="mt-4 leading-relaxed text-ink-600">
          {{ product.product_details }}
        </p>
        <p v-if="product.product_description" class="mt-2 leading-relaxed text-ink-600">
          {{ product.product_description }}
        </p>

        <div class="mt-6 flex items-center gap-4">
          <div class="flex items-center rounded-lg border border-ink-300">
            <button
              type="button"
              class="px-3 py-2 text-lg text-ink-600 hover:text-accent-600"
              @click="stepQuantity(-1)"
            >
              −
            </button>
            <input
              v-model.number="quantity"
              type="number"
              min="1"
              class="w-12 border-x border-ink-300 py-2 text-center focus:outline-none"
            />
            <button
              type="button"
              class="px-3 py-2 text-lg text-ink-600 hover:text-accent-600"
              @click="stepQuantity(1)"
            >
              +
            </button>
          </div>
          <FwbButton
            size="lg"
            :disabled="adding"
            class="flex-1 bg-accent-600 hover:bg-accent-700 focus:ring-accent-300 sm:flex-none"
            @click="addToCart"
          >
            {{ adding ? 'Đang thêm...' : 'Thêm vào giỏ' }}
          </FwbButton>
        </div>

        <FwbAlert v-if="addMessage" type="success" class="mt-4">{{ addMessage }}</FwbAlert>
      </div>
    </div>

    <section v-if="similar.length">
      <h3 class="mb-4 font-display text-xl text-ink-900">Sản phẩm tương tự</h3>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        <ProductCard v-for="p in similar" :key="p.product_id" :product="p" />
      </div>
    </section>
  </div>
</template>
