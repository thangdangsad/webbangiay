<script setup>
import { computed } from 'vue'

import { formatPrice, productImageUrl } from '@/utils/format'

const props = defineProps({
  product: { type: Object, required: true },
})

const imageUrl = computed(() => productImageUrl(props.product))

const hasDiscount = computed(() => {
  const discount = Number(props.product.product_discount)
  return discount > 0 && discount < Number(props.product.product_price)
})

const displayPrice = computed(() =>
  hasDiscount.value ? props.product.product_discount : props.product.product_price,
)

function onImgError(e) {
  e.target.src = '/placeholder.svg'
}
</script>

<template>
  <RouterLink :to="`/product/${product.product_id}`" class="card product-card">
    <div class="thumb">
      <img :src="imageUrl || '/placeholder.svg'" :alt="product.product_name" @error="onImgError" />
    </div>
    <div class="info">
      <h4 class="name">{{ product.product_name }}</h4>
      <div class="prices">
        <span class="price">{{ formatPrice(displayPrice) }}</span>
        <span v-if="hasDiscount" class="price-strike">{{ formatPrice(product.product_price) }}</span>
      </div>
    </div>
  </RouterLink>
</template>

<style scoped>
.product-card {
  display: block;
  color: inherit;
  text-decoration: none;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
}

.product-card:hover {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
  text-decoration: none;
}

.thumb {
  background: #f1f3f5;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info {
  padding: 12px;
}

.name {
  margin: 0 0 8px;
  font-size: 15px;
  font-weight: 600;
  height: 40px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.prices {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
}
</style>
