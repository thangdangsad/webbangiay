<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { http } from '@/api/client'
import { productImageUrl } from '@/utils/format'

const props = defineProps({
  id: { type: [String, Number], default: null },
})

const router = useRouter()
const isEdit = computed(() => props.id !== null && props.id !== undefined)

const categories = ref([])
const loading = ref(isEdit.value)
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  product_name: '',
  size: '',
  product_quantity: 0,
  product_price: 0,
  product_discount: '',
  category_id: '',
  product_details: '',
  product_description: '',
})

const imageFile = ref(null)
const existingImageUrl = ref(null)
const previewUrl = ref(null)

function onFileChange(e) {
  const file = e.target.files[0] || null
  imageFile.value = file
  previewUrl.value = file ? URL.createObjectURL(file) : null
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
    const { data } = await http.get(`/api/products/${props.id}`)
    const p = data.data
    form.product_name = p.product_name || ''
    form.size = p.size || ''
    form.product_quantity = p.product_quantity ?? 0
    form.product_price = p.product_price ?? 0
    form.product_discount = p.product_discount ?? ''
    form.category_id = p.category_id ?? ''
    form.product_details = p.product_details || ''
    form.product_description = p.product_description || ''
    existingImageUrl.value = productImageUrl(p)
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
  }
})

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}

  const payload = new FormData()
  payload.append('product_name', form.product_name)
  payload.append('size', form.size || '')
  payload.append('product_quantity', form.product_quantity)
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
    <form v-else class="card form-card" @submit.prevent="submit">
      <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>

      <div class="form-row">
        <div class="form-group">
          <label>Tên sản phẩm *</label>
          <input v-model="form.product_name" type="text" required />
          <small v-if="fieldErrors.product_name" class="field-error">{{ fieldErrors.product_name[0] }}</small>
        </div>
        <div class="form-group">
          <label>Danh mục</label>
          <select v-model="form.category_id">
            <option value="">Không có danh mục</option>
            <option v-for="c in categories" :key="c.category_id" :value="c.category_id">
              {{ c.category_name }}
            </option>
          </select>
          <small v-if="fieldErrors.category_id" class="field-error">{{ fieldErrors.category_id[0] }}</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Kích thước (size)</label>
          <input v-model="form.size" type="text" />
        </div>
        <div class="form-group">
          <label>Số lượng *</label>
          <input v-model.number="form.product_quantity" type="number" min="0" required />
          <small v-if="fieldErrors.product_quantity" class="field-error">{{ fieldErrors.product_quantity[0] }}</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Giá *</label>
          <input v-model.number="form.product_price" type="number" min="0" step="1000" required />
          <small v-if="fieldErrors.product_price" class="field-error">{{ fieldErrors.product_price[0] }}</small>
        </div>
        <div class="form-group">
          <label>Giá khuyến mãi</label>
          <input v-model.number="form.product_discount" type="number" min="0" step="1000" />
          <small v-if="fieldErrors.product_discount" class="field-error">{{ fieldErrors.product_discount[0] }}</small>
        </div>
      </div>

      <div class="form-group">
        <label>Mô tả ngắn</label>
        <textarea v-model="form.product_description" rows="2"></textarea>
      </div>

      <div class="form-group">
        <label>Chi tiết sản phẩm</label>
        <textarea v-model="form.product_details" rows="4"></textarea>
      </div>

      <div class="form-group">
        <label>Hình ảnh</label>
        <div class="image-row">
          <img :src="previewUrl || existingImageUrl || '/placeholder.svg'" class="image-preview" alt="Xem trước" />
          <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" @change="onFileChange" />
        </div>
        <small v-if="fieldErrors.image" class="field-error">{{ fieldErrors.image[0] }}</small>
        <small v-else-if="isEdit">Bỏ qua nếu không muốn thay đổi hình ảnh hiện tại.</small>
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit" :disabled="submitting">
          {{ submitting ? 'Đang lưu...' : 'Lưu sản phẩm' }}
        </button>
        <RouterLink :to="{ name: 'products' }" class="btn btn-outline">Hủy</RouterLink>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form-card {
  padding: 24px;
  max-width: 720px;
}

.image-row {
  display: flex;
  align-items: center;
  gap: 16px;
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}
</style>
