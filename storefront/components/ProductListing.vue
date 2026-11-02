<template>
  <div class="two">
    <aside>
      <div class="filter" v-if="mode !== 'category'">
        <h4>Danh mục</h4>
        <label v-for="c in categories" :key="c.id"><input type="radio" name="cat" :checked="q.category === c.slug" @change="set('category', c.slug)" /> {{ c.name }}</label>
        <a v-if="q.category" href="#" style="font-size:13px;color:var(--brand)" @click.prevent="set('category', null)">Bỏ chọn</a>
      </div>
      <div class="filter">
        <h4>Thương hiệu</h4>
        <label v-for="b in brands" :key="b.id"><input type="checkbox" :checked="list('brand').includes(b.slug)" @change="toggle('brand', b.slug)" /> {{ b.name }}</label>
      </div>
      <div class="filter">
        <h4>Giới tính</h4>
        <label v-for="g in genders" :key="g[0]"><input type="checkbox" :checked="list('gender').includes(g[0])" @change="toggle('gender', g[0])" /> {{ g[1] }}</label>
      </div>
      <div class="filter">
        <h4>Size (EU)</h4>
        <div class="chips"><span v-for="s in sizes" :key="s.id" class="chip" :class="{ on: list('size').includes(String(s.id)) }" @click="toggle('size', String(s.id))">{{ s.label }}</span></div>
      </div>
      <div class="filter">
        <h4>Màu sắc</h4>
        <div class="chips"><span v-for="c in colors" :key="c.id" class="chip" :class="{ on: list('color').includes(String(c.id)) }" @click="toggle('color', String(c.id))"><i class="dot" :style="{ background: c.hex }"></i> {{ c.name }}</span></div>
      </div>
      <div class="filter">
        <h4>Khoảng giá</h4>
        <label v-for="r in ranges" :key="r.label"><input type="radio" name="price" :checked="isRange(r)" @change="setRange(r)" /> {{ r.label }}</label>
      </div>
      <div class="filter" v-if="mode !== 'sale'"><label><input type="checkbox" :checked="q.on_sale === '1'" @change="set('on_sale', ($event.target as HTMLInputElement).checked ? '1' : null)" /> Đang giảm giá</label></div>
      <button class="btn ghost sm" style="margin-top:14px" @click="reset">Xóa bộ lọc</button>
    </aside>
    <section>
      <div class="toolbar">
        <span>{{ res?.meta.total ?? 0 }} sản phẩm</span>
        <select style="width:auto" :value="q.sort || 'newest'" @change="set('sort', ($event.target as HTMLSelectElement).value)">
          <option value="newest">Mới nhất</option><option value="best_seller">Bán chạy</option>
          <option value="price_asc">Giá tăng dần</option><option value="price_desc">Giá giảm dần</option>
        </select>
      </div>
      <ProductGrid :items="res?.data ?? []" :pending="pending" cols="3" />
      <Pager v-if="res" :page="res.meta.page" :total="res.meta.total" :limit="res.meta.limit" @go="(n) => set('page', n === 1 ? null : String(n), false)" />
    </section>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ mode: 'all' | 'category' | 'search' | 'sale'; category?: string }>()
const route = useRoute()
const { $api } = useNuxtApp()
const q = computed(() => route.query as Record<string, string>)
const genders: [string, string][] = [['men', 'Nam'], ['women', 'Nữ'], ['kids', 'Trẻ em'], ['unisex', 'Unisex']]
const ranges = [
  { label: 'Dưới 1.000.000đ', min: null, max: 1000000 }, { label: '1 – 2 triệu', min: 1000000, max: 2000000 },
  { label: '2 – 4 triệu', min: 2000000, max: 4000000 }, { label: 'Trên 4 triệu', min: 4000000, max: null },
]
const { data: meta } = await useAsyncData('listing-meta', async () => {
  const [a, b, c] = await Promise.all([$api('/attributes'), $api('/brands'), $api('/categories')])
  return { colors: a.data.colors, sizes: a.data.sizes.filter((s: any) => s.system === 'EU'), brands: b.data, categories: c.data }
})
const colors = computed(() => meta.value?.colors ?? [])
const sizes = computed(() => meta.value?.sizes ?? [])
const brands = computed(() => meta.value?.brands ?? [])
const categories = computed(() => meta.value?.categories ?? [])

const params = computed(() => {
  const p: Record<string, any> = { ...route.query, limit: 12 }
  if (props.mode === 'category') p.category = props.category
  if (props.mode === 'sale') p.on_sale = 1
  return p
})
const { data: res, pending } = await useAsyncData(() => `list-${props.mode}-${JSON.stringify(params.value)}`, () => $api('/products', { query: params.value }), { watch: [params] })

const list = (k: string) => (q.value[k] ? String(q.value[k]).split(',') : [])
function push(query: Record<string, any>) { return navigateTo({ path: route.path, query }) }
function set(k: string, v: string | null, resetPage = true) {
  const next: Record<string, any> = { ...route.query, [k]: v }
  if (resetPage) delete next.page
  Object.keys(next).forEach((x) => (next[x] == null || next[x] === '') && delete next[x])
  return push(next)
}
function toggle(k: string, v: string) {
  const cur = list(k)
  set(k, (cur.includes(v) ? cur.filter((x) => x !== v) : [...cur, v]).join(',') || null)
}
const isRange = (r: any) => String(r.min ?? '') === (q.value.price_min ?? '') && String(r.max ?? '') === (q.value.price_max ?? '')
function setRange(r: any) {
  const next: Record<string, any> = { ...route.query, price_min: r.min, price_max: r.max }
  delete next.page
  Object.keys(next).forEach((x) => next[x] == null && delete next[x])
  push(next)
}
const reset = () => push(props.mode === 'search' && q.value.q ? { q: q.value.q } : {})
</script>

<style scoped>
.dot { display:inline-block; width:10px; height:10px; border-radius:50%; border:1px solid var(--line); vertical-align:middle }
</style>
