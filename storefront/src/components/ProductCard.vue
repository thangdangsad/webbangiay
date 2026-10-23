<script setup>
import { computed } from 'vue'
import { FwbBadge } from 'flowbite-vue'

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

/**
 * Stock hint from the variants the list/detail endpoints eager-load
 * (already filtered to is_active=true). Only rendered when the response
 * actually included a `variants` array — some endpoints (e.g. the
 * "similar products" block) don't load it, and we skip the hint there
 * rather than firing an extra request per card.
 */
const inStockVariantCount = computed(() => {
  if (!Array.isArray(props.product.variants)) return null
  return props.product.variants.filter((v) => Number(v.product_quantity) > 0).length
})

function onImgError(e) {
  e.target.src = '/placeholder.svg'
}
</script>

<template>
  <RouterLink
    :to="`/product/${product.product_id}`"
    class="group block overflow-hidden rounded-xl border border-ink-200 bg-white transition hover:-translate-y-1 hover:shadow-lg hover:shadow-ink-900/10"
  >
    <div class="relative aspect-square overflow-hidden bg-ink-100">
      <img
        :src="imageUrl || '/placeholder.svg'"
        :alt="product.product_name"
        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
        @error="onImgError"
      />
      <FwbBadge v-if="hasDiscount" type="danger" class="absolute left-2 top-2">Sale</FwbBadge>
    </div>
    <div class="p-3">
      <h4 class="mb-2 line-clamp-2 h-10 text-sm font-semibold text-ink-800">
        {{ product.product_name }}
      </h4>
      <div class="flex items-baseline gap-2">
        <span class="font-display text-accent-600">{{ formatPrice(displayPrice) }}</span>
        <span v-if="hasDiscount" class="text-sm text-ink-400 line-through">
          {{ formatPrice(product.product_price) }}
        </span>
      </div>
      <p v-if="inStockVariantCount === 0" class="mt-1 text-xs font-semibold text-red-600">Hết hàng</p>
      <p v-else-if="inStockVariantCount !== null" class="mt-1 text-xs text-ink-400">
        {{ inStockVariantCount }} size còn hàng
      </p>
    </div>
  </RouterLink>
</template>
