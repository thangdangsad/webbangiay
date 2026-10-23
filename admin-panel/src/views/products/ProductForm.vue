<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import FileUpload from 'primevue/fileupload'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'

import { http } from '@/api/client'
import { productImageUrl } from '@/utils/format'

const props = defineProps({
  id: { type: [String, Number], default: null },
})

const router = useRouter()
const toast = useToast()
const isEdit = computed(() => props.id !== null && props.id !== undefined)

const categories = ref([])
const loading = ref(isEdit.value)
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  product_name: '',
  product_price: 0,
  product_discount: null,
  category_id: '',
  product_details: '',
  product_description: '',
})

// One row per shoe size (ProductVariant). `variant_id` is only set for a
// variant that already exists in the DB (loaded when editing); rows
// without one are brand new sizes being added. Removing an existing row
// (one with a variant_id) and submitting makes the backend soft-deactivate
// it (is_active = false) rather than deleting it — see submit() below.
const variants = reactive([])
const variantsError = ref('')

function addVariant() {
  variants.push({ variant_id: null, size: '', product_quantity: 0, is_active: true })
}

function removeVariant(index) {
  variants.splice(index, 1)
}

function validateVariants() {
  if (variants.length === 0) {
    return 'Vui lòng thêm ít nhất một size.'
  }
  for (const v of variants) {
    if (!String(v.size ?? '').trim()) {
      return 'Vui lòng nhập size cho tất cả các dòng.'
    }
    if (v.product_quantity === null || v.product_quantity === undefined || Number(v.product_quantity) < 0) {
      return 'Số lượng của mỗi size phải là số không âm.'
    }
  }
  return ''
}

const imageFile = ref(null)
const existingImageUrl = ref(null)
const previewUrl = ref(null)

function onFileSelect(e) {
  const file = e.files?.[0] || null
  imageFile.value = file
  previewUrl.value = file ? URL.createObjectURL(file) : null
}

function onFileClear() {
  imageFile.value = null
  previewUrl.value = null
}

async function loadCategories() {
  try {
    const { data } = await http.get('/api/categories')
    categories.value = data.data
  } catch {
    categories.value = []
  }
}

async function loadProduct() {
  try {
    const { data } = await http.get(`/api/admin/products/${props.id}`)
    const p = data.data
    form.product_name = p.product_name || ''
    form.product_price = p.product_price ?? 0
    form.product_discount = p.product_discount ?? null
    form.category_id = p.category_id ?? ''
    form.product_details = p.product_details || ''
    form.product_description = p.product_description || ''
    existingImageUrl.value = productImageUrl(p)

    variants.splice(0, variants.length, ...(p.variants || []).map((v) => ({
      variant_id: v.variant_id,
      size: v.size ?? '',
      product_quantity: v.product_quantity ?? 0,
      is_active: v.is_active ?? true,
    })))
  } catch {
    errorMessage.value = 'Không thể tải thông tin sản phẩm.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCategories()
  if (isEdit.value) {
    loadProduct()
  } else {
    addVariant()
  }
})

async function submit() {
  errorMessage.value = ''
  fieldErrors.value = {}
  variantsError.value = validateVariants()
  if (variantsError.value) {
    errorMessage.value = variantsError.value
    return
  }

  submitting.value = true

  const payload = new FormData()
  payload.append('product_name', form.product_name)
  payload.append('product_price', form.product_price)
  if (form.product_discount !== '' && form.product_discount !== null) {
    payload.append('product_discount', form.product_discount)
  }
  if (form.category_id !== '') {
    payload.append('category_id', form.category_id)
  }
  payload.append('product_details', form.product_details || '')
  payload.append('product_description', form.product_description || '')
  if (imageFile.value) {
    payload.append('image', imageFile.value)
  }
  variants.forEach((variant, index) => {
    if (variant.variant_id !== null && variant.variant_id !== undefined && variant.variant_id !== '') {
      payload.append(`variants[${index}][variant_id]`, variant.variant_id)
    }
    payload.append(`variants[${index}][size]`, variant.size)
    payload.append(`variants[${index}][product_quantity]`, variant.product_quantity)
  })

  try {
    if (isEdit.value) {
      // Laravel doesn't parse multipart bodies on PUT/PATCH, so send the
      // update as a POST with a _method override (standard Laravel
      // spoofing) — the route accepts PUT/PATCH but this works transparently.
      payload.append('_method', 'PUT')
      await http.post(`/api/admin/products/${props.id}`, payload)
    } else {
      await http.post('/api/admin/products', payload)
    }
    toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã lưu sản phẩm.', life: 3000 })
    router.push({ name: 'products' })
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      errorMessage.value = err.response.data.message || 'Vui lòng kiểm tra lại thông tin.'
    } else {
      errorMessage.value = 'Lưu sản phẩm không thành công.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm' }}</h1>
    </div>

    <p v-if="loading">Đang tải...</p>
    <Card v-else class="form-card">
      <template #content>
        <form class="product-form" @submit.prevent="submit">
          <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

          <div class="form-row">
            <div class="field">
              <label for="product_name">Tên sản phẩm *</label>
              <InputText id="product_name" v-model="form.product_name" required fluid />
              <small v-if="fieldErrors.product_name" class="field-error">{{ fieldErrors.product_name[0] }}</small>
            </div>
            <div class="field">
              <label for="category_id">Danh mục</label>
              <Select
                id="category_id"
                v-model="form.category_id"
                :options="[{ category_id: '', category_name: 'Không có danh mục' }, ...categories]"
                option-label="category_name"
                option-value="category_id"
                fluid
              />
              <small v-if="fieldErrors.category_id" class="field-error">{{ fieldErrors.category_id[0] }}</small>
            </div>
          </div>

          <div class="field">
            <div class="variants-header">
              <label>Các size *</label>
              <Button type="button" label="Thêm size" icon="pi pi-plus" size="small" outlined @click="addVariant" />
            </div>
            <small v-if="variantsError" class="field-error">{{ variantsError }}</small>
            <small v-if="fieldErrors.variants" class="field-error">{{ fieldErrors.variants[0] }}</small>
            <div class="variant-table">
              <div v-for="(variant, index) in variants" :key="index" class="variant-row" :class="{ 'variant-row--inactive': variant.is_active === false }">
                <InputText v-model="variant.size" placeholder="Size (ví dụ: 40)" class="variant-size" />
                <InputNumber v-model="variant.product_quantity" :min="0" show-buttons placeholder="Số lượng" class="variant-qty" />
                <Tag v-if="variant.is_active === false" severity="secondary" value="Ngừng bán" />
                <Button type="button" icon="pi pi-trash" text rounded severity="danger" aria-label="Xoá" @click="removeVariant(index)" />
              </div>
              <p v-if="variants.length === 0" class="variant-empty">Chưa có size nào. Nhấn "Thêm size" để thêm.</p>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="product_price">Giá *</label>
              <InputNumber
                id="product_price"
                v-model="form.product_price"
                :min="0"
                :step="1000"
                mode="currency"
                currency="VND"
                locale="vi-VN"
                required
                fluid
              />
              <small v-if="fieldErrors.product_price" class="field-error">{{ fieldErrors.product_price[0] }}</small>
            </div>
            <div class="field">
              <label for="product_discount">Giá khuyến mãi</label>
              <InputNumber
                id="product_discount"
                v-model="form.product_discount"
                :min="0"
                :step="1000"
                mode="currency"
                currency="VND"
                locale="vi-VN"
                fluid
              />
              <small v-if="fieldErrors.product_discount" class="field-error">{{ fieldErrors.product_discount[0] }}</small>
            </div>
          </div>

          <div class="field">
            <label for="product_description">Mô tả ngắn</label>
            <Textarea id="product_description" v-model="form.product_description" rows="2" fluid />
          </div>

          <div class="field">
            <label for="product_details">Chi tiết sản phẩm</label>
            <Textarea id="product_details" v-model="form.product_details" rows="4" fluid />
          </div>

          <div class="field">
            <label>Hình ảnh</label>
            <div class="image-row">
              <img :src="previewUrl || existingImageUrl || '/placeholder.svg'" class="image-preview" alt="Xem trước" />
              <FileUpload
                mode="basic"
                name="image"
                accept="image/jpeg,image/png,image/gif,image/webp"
                :auto="false"
                custom-upload
                choose-label="Chọn hình ảnh"
                @select="onFileSelect"
                @clear="onFileClear"
                @uploader="() => {}"
              />
            </div>
            <small v-if="fieldErrors.image" class="field-error">{{ fieldErrors.image[0] }}</small>
            <small v-else-if="isEdit">Bỏ qua nếu không muốn thay đổi hình ảnh hiện tại.</small>
          </div>

          <div class="form-actions">
            <Button type="submit" :loading="submitting" label="Lưu sản phẩm" icon="pi pi-check" />
            <RouterLink :to="{ name: 'products' }">
              <Button type="button" label="Hủy" severity="secondary" outlined />
            </RouterLink>
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.form-card {
  max-width: 760px;
}

.product-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-weight: 600;
  font-size: 14px;
}

.field-error {
  color: var(--p-red-500);
}

.image-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.image-preview {
  width: 120px;
  height: 120px;
  border-radius: 8px;
  object-fit: cover;
  background: var(--p-surface-100);
  border: 1px solid var(--p-content-border-color);
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 6px;
}

.variants-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.variant-table {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 8px;
}

.variant-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border: 1px solid var(--p-content-border-color);
  border-radius: 8px;
}

.variant-row--inactive {
  opacity: 0.6;
  background: var(--p-surface-50);
}

.variant-size {
  flex: 1;
  max-width: 180px;
}

.variant-qty {
  flex: 1;
  max-width: 180px;
}

.variant-empty {
  color: var(--p-text-muted-color);
  font-size: 14px;
  margin: 0;
}
</style>
