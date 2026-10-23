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
    </div>
  </RouterLink>
</template>
