<template>
  <NuxtLink class="card" :to="`/san-pham/${p.slug}`">
    <span v-if="p.labels.includes('out_of_stock')" class="tagl out">Hết hàng</span>
    <span v-else-if="p.labels.includes('sale')" class="tagl">-{{ p.discount_percent }}%</span>
    <span v-else-if="p.labels.includes('new')" class="tagl new">Mới</span>
    <button class="fav" :class="{ on: wl.has(p.id) }" type="button" :aria-label="'Yêu thích'" @click.prevent.stop="wl.toggle(p.id)">{{ wl.has(p.id) ? '♥' : '♡' }}</button>
    <div class="img" :style="{ background: tint }">
      <img v-if="p.image" :src="p.image.md" :alt="p.name" loading="lazy" />
      <span v-else style="font-size:84px">👟</span>
      <img v-if="p.hover_image" class="h2" :src="p.hover_image.md" :alt="p.name" loading="lazy" />
    </div>
    <div class="brand-n">{{ p.brand?.name }}</div>
    <h3>{{ p.name }}</h3>
    <div class="price">{{ money(p.price) }}<s v-if="p.compare_at_price">{{ money(p.compare_at_price) }}</s></div>
    <div class="dots"><i v-for="c in p.colors" :key="c.id" :style="{ background: c.hex }" :title="c.name"></i></div>
  </NuxtLink>
</template>

<script setup lang="ts">
const props = defineProps<{ p: any }>()
const wl = useWishlistStore()
const tints = ['#ffe8e1', '#e6efff', '#f1f1ee', '#e9f7ee', '#fff3d6', '#ececf4', '#fbe9f0', '#e4f4f6']
const tint = computed(() => tints[props.p.id % tints.length])
</script>
