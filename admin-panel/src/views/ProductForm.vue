<template>
  <div v-if="p">
    <div class="ph"><div class="crumb"><router-link to="/products">Sản phẩm</router-link> › {{ id ? 'Sửa' : 'Thêm mới' }}<h1>{{ id ? p.name || 'Sửa sản phẩm' : 'Thêm sản phẩm' }}</h1></div>
      <div class="acts"><router-link class="btn" to="/products">Hủy</router-link><button class="btn pri" :disabled="saving" @click="save">{{ saving ? 'Đang lưu...' : 'Lưu sản phẩm' }}</button></div></div>
    <div v-if="err" class="errmsg">{{ err }}</div>
    <div class="cols side">
      <div>
        <div class="card"><h3>Thông tin cơ bản</h3>
          <div class="fld"><label>Tên sản phẩm *</label><input v-model="p.name" class="f" /><small v-if="fe.name" style="color:var(--bad)">{{ fe.name }}</small></div>
          <div class="g2"><div class="fld"><label>Slug</label><input v-model="p.slug" class="f" placeholder="Tự sinh từ tên" /></div>
            <div class="fld"><label>Chất liệu</label><input v-model="p.material" class="f" /></div></div>
          <div class="fld"><label>Mô tả (HTML cơ bản)</label><textarea v-model="p.description" class="f" style="height:160px"></textarea></div></div>

        <div class="card"><h3>Hình ảnh</h3>
          <ImageUpload type="product" multiple label="Kéo thả hoặc bấm để thêm ảnh" @uploaded="addImage" />
          <div class="imgs"><div v-for="(im, i) in p.images" :key="im.path" :class="{ pri: i === 0 }">
            <img :src="im.sm || im.url" /><button class="x" @click="p.images.splice(i, 1)">✕</button>
            <select v-model="im.color_id" class="f pr" style="height:24px;font-size:11px;padding:0 4px;width:70%"><option :value="null">Mọi màu</option><option v-for="c in colors" :key="c.id" :value="c.id">{{ c.name }}</option></select></div></div>
          <small class="muted">Ảnh đầu tiên là ảnh chính. Gán ảnh theo màu để đổi ảnh khi khách chọn màu.</small></div>

        <div class="card"><h3>Biến thể (Màu × Size)</h3>
          <div class="fld"><label>Chọn màu</label><span v-for="c in colors" :key="c.id" class="tagi" :class="{ on: pickColors.includes(c.id) }" style="cursor:pointer" @click="toggle(pickColors, c.id)"><span class="swatch" :style="{ background: c.hex }"></span>{{ c.name }}</span></div>
          <div class="fld"><label>Chọn size (EU)</label><span v-for="s in sizes" :key="s.id" class="tagi" :class="{ on: pickSizes.includes(s.id) }" style="cursor:pointer" @click="toggle(pickSizes, s.id)">{{ s.label }}</span></div>
          <div class="g3"><div class="fld"><label>Giá bán</label><input v-model.number="gen.price" class="f" type="number" /></div><div class="fld"><label>Giá gốc (gạch)</label><input v-model.number="gen.compare" class="f" type="number" /></div><div class="fld"><label>Tồn đầu</label><input v-model.number="gen.stock" class="f" type="number" /></div></div>
          <button class="btn dark" type="button" @click="generate">⚙ Sinh bảng SKU</button>
          <table v-if="p.variants.length" class="tb vtable" style="margin-top:14px"><thead><tr><th>SKU</th><th>Màu</th><th>Size</th><th>Giá</th><th>Giá gốc</th><th>Tồn</th><th>Bán</th><th></th></tr></thead>
            <tbody><tr v-for="(v, i) in p.variants" :key="v.key"><td><input v-model="v.sku" class="f" style="min-width:150px" /></td><td>{{ colorName(v.color_id) }}</td><td>{{ sizeLabel(v.size_id) }}</td>
              <td><input v-model.number="v.price" class="f" type="number" style="width:110px" /></td><td><input v-model.number="v.compare_at_price" class="f" type="number" style="width:110px" /></td>
              <td><span v-if="v.id" class="muted">{{ v.on_hand }}</span><input v-else v-model.number="v.stock" class="f" type="number" style="width:70px" /></td>
              <td><input v-model="v.is_active" type="checkbox" /></td><td><button class="btn sm danger" type="button" @click="p.variants.splice(i, 1)">✕</button></td></tr></tbody></table>
          <small v-if="fe.variants" style="color:var(--bad)">{{ fe.variants }}</small></div>

        <div class="card"><h3>SEO</h3><div class="fld"><label>Tiêu đề SEO</label><input v-model="p.seo_title" class="f" /></div><div class="fld"><label>Mô tả SEO</label><textarea v-model="p.seo_description" class="f" style="height:70px"></textarea></div></div>
      </div>
      <div>
        <div class="card"><h3>Trạng thái</h3><select v-model="p.status" class="f"><option value="active">Đang bán</option><option value="hidden">Ẩn</option><option value="draft">Nháp</option></select></div>
        <div class="card"><h3>Phân loại</h3>
          <div class="fld"><label>Danh mục</label><select v-model="p.category_id" class="f"><option :value="null">—</option><template v-for="c in cats" :key="c.id"><option :value="c.id">{{ c.parent_id ? '— ' : '' }}{{ c.name }}</option></template></select></div>
          <div class="fld"><label>Thương hiệu</label><select v-model="p.brand_id" class="f"><option :value="null">—</option><option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option></select></div>
          <div class="fld"><label>Giới tính</label><select v-model="p.gender" class="f"><option value="men">Nam</option><option value="women">Nữ</option><option value="unisex">Unisex</option><option value="kids">Trẻ em</option></select></div></div>
      </div>
    </div>
  </div>
  <div v-else class="loading">Đang tải...</div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'
import { useUi } from '../stores/ui'
import ImageUpload from '../components/ImageUpload.vue'
const route = useRoute(); const router = useRouter(); const ui = useUi()
const id = route.params.id; const p = ref(null); const err = ref(''); const fe = ref({}); const saving = ref(false)
const colors = ref([]); const sizes = ref([]); const brands = ref([]); const cats = ref([])
const pickColors = ref([]); const pickSizes = ref([]); const gen = reactive({ price: 0, compare: null, stock: 0 })
let keySeq = 0
const toggle = (arr, v) => { const i = arr.indexOf(v); i >= 0 ? arr.splice(i, 1) : arr.push(v) }
const colorName = (cid) => colors.value.find((c) => c.id === cid)?.name
const sizeLabel = (sid) => sizes.value.find((s) => s.id === sid)?.label
const slugKey = (s) => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toUpperCase().replace(/[^A-Z0-9]+/g, '')

function addImage(u) { p.value.images.push({ path: u.path, url: u.url, sm: u.url.replace(/\.(\w+)$/, '_sm.$1'), color_id: null }) }
function generate() {
  if (!pickColors.value.length || !pickSizes.value.length) return (err.value = 'Chọn ít nhất 1 màu và 1 size')
  err.value = ''
  const base = slugKey(p.value.name).slice(0, 14) || 'SP'
  for (const cid of pickColors.value) for (const sid of pickSizes.value) {
    if (p.value.variants.some((v) => v.color_id === cid && v.size_id === sid)) continue
    p.value.variants.push({ key: ++keySeq, sku: `${base}-${slugKey(colorName(cid))}-${sizeLabel(sid)}`, color_id: cid, size_id: sid, price: gen.price, compare_at_price: gen.compare || null, stock: gen.stock, is_active: true })
  }
}
async function save() {
  err.value = ''; fe.value = {}; saving.value = true
  const v = p.value
  const body = { name: v.name, slug: v.slug || null, brand_id: v.brand_id, category_id: v.category_id, gender: v.gender, description: v.description, material: v.material, status: v.status,
    seo_title: v.seo_title, seo_description: v.seo_description,
    variants: v.variants.map((x) => ({ id: x.id, sku: x.sku, color_id: x.color_id, size_id: x.size_id, price: x.price || 0, compare_at_price: x.compare_at_price || null, barcode: x.barcode, weight_g: x.weight_g || 800, is_active: !!x.is_active, stock: x.id ? undefined : x.stock || 0 })),
    images: v.images.map((i, n) => ({ path: i.path, color_id: i.color_id, alt: i.alt, is_primary: n === 0 })) }
  try {
    const r = id ? await api.put(`/admin/products/${id}`, body) : await api.post('/admin/products', body)
    ui.notify('Đã lưu sản phẩm'); if (!id) router.replace(`/products/${r.data.data.id}`); else p.value = prepare(r.data.data)
  } catch (e) { err.value = e.message; fe.value = e.fields || {} } finally { saving.value = false }
}
const prepare = (d) => ({ ...d, variants: d.variants.map((x) => ({ ...x, key: ++keySeq })), images: d.images.map((i) => ({ ...i, sm: i.sm })) })
onMounted(async () => {
  const [c, s, b, ct] = await Promise.all(['colors', 'sizes', 'brands', 'categories'].map((x) => api.get(`/admin/${x}`, { params: { all: 1 } })))
  colors.value = c.data.data; sizes.value = s.data.data.filter((x) => x.system === 'EU'); brands.value = b.data.data; cats.value = ct.data.data
  p.value = id ? prepare((await api.get(`/admin/products/${id}`)).data.data) : { name: '', slug: '', brand_id: null, category_id: null, gender: 'unisex', description: '', material: '', status: 'draft', seo_title: '', seo_description: '', variants: [], images: [] }
})
</script>
