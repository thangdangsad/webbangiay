<script setup>
import { computed, ref, watch } from 'vue'

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
    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <div v-else-if="product" class="product-detail">
      <div class="gallery">
        <img :src="imageUrl || '/placeholder.svg'" :alt="product.product_name" @error="onImgError" />
      </div>

      <div class="details">
        <h2>{{ product.product_name }}</h2>
        <div class="prices">
          <span class="price price-lg">{{ formatPrice(displayPrice) }}</span>
          <span v-if="hasDiscount" class="price-strike">{{ formatPrice(product.product_price) }}</span>
        </div>
        <p v-if="product.size">Size: {{ product.size }}</p>
        <p v-if="product.category">Danh mục: {{ product.category.category_name }}</p>

        <p v-if="product.product_details" class="description">{{ product.product_details }}</p>
        <p v-if="product.product_description" class="description">{{ product.product_description }}</p>

        <div class="add-to-cart">
          <input v-model.number="quantity" type="number" min="1" />
          <button class="btn btn-primary" :disabled="adding" @click="addToCart">
            {{ adding ? 'Đang thêm...' : 'Thêm vào giỏ' }}
          </button>
        </div>
        <p v-if="addMessage" class="alert alert-success">{{ addMessage }}</p>
      </div>
    </div>

    <section v-if="similar.length" class="similar-section">
      <h3>Sản phẩm tương tự</h3>
      <div class="product-grid">
        <ProductCard v-for="p in similar" :key="p.product_id" :product="p" />
      </div>
    </section>
  </div>
</template>

<style scoped>
.product-detail {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 32px;
  margin-bottom: 40px;
}

.gallery {
  background: #f1f3f5;
  border-radius: 8px;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.gallery img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.prices {
  margin: 10px 0;
}

.price-lg {
  font-size: 22px;
}

.description {
  color: var(--color-muted);
  line-height: 1.6;
}

.add-to-cart {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.add-to-cart input {
  width: 70px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  padding: 9px;
}

.similar-section {
  margin-top: 30px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}

@media (max-width: 720px) {
  .product-detail {
    grid-template-columns: 1fr;
  }
}
</style>
