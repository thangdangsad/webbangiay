<script setup>
import { onMounted, reactive, ref } from 'vue'

import { http } from '@/api/client'

const categories = ref([])
const loading = ref(true)
const error = ref('')

const editingId = ref(null)
const formVisible = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})
const form = reactive({ category_name: '' })

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/categories')
    categories.value = data.data
  } catch {
    error.value = 'Không thể tải danh mục.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openCreate() {
  editingId.value = null
  form.category_name = ''
  fieldErrors.value = {}
  errorMessage.value = ''
  formVisible.value = true
}

function openEdit(category) {
  editingId.value = category.category_id
  form.category_name = category.category_name
  fieldErrors.value = {}
  errorMessage.value = ''
  formVisible.value = true
}

function cancelForm() {
  formVisible.value = false
}

async function submit() {
  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}
  try {
    if (editingId.value) {
      await http.patch(`/api/admin/categories/${editingId.value}`, { category_name: form.category_name })
    } else {
      await http.post('/api/admin/categories', { category_name: form.category_name })
    }
    formVisible.value = false
    await load()
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      errorMessage.value = err.response.data.message || 'Vui lòng kiểm tra lại thông tin.'
    } else {
      errorMessage.value = 'Lưu danh mục không thành công.'
    }
  } finally {
    submitting.value = false
  }
}

async function remove(category) {
  if (!confirm(`Xóa danh mục "${category.category_name}"?`)) return
  try {
    await http.delete(`/api/admin/categories/${category.category_id}`)
    await load()
  } catch {
    alert('Xóa danh mục không thành công.')
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Danh mục</h1>
      <button class="btn btn-primary" @click="openCreate">+ Thêm danh mục</button>
    </div>

    <div v-if="formVisible" class="card form-panel">
      <h3>{{ editingId ? 'Sửa danh mục' : 'Thêm danh mục' }}</h3>
      <p v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</p>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>Tên danh mục *</label>
          <input v-model="form.category_name" type="text" required />
          <small v-if="fieldErrors.category_name" class="field-error">{{ fieldErrors.category_name[0] }}</small>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" type="submit" :disabled="submitting">
            {{ submitting ? 'Đang lưu...' : 'Lưu' }}
          </button>
          <button class="btn btn-outline" type="button" @click="cancelForm">Hủy</button>
        </div>
      </form>
    </div>

    <p v-if="loading">Đang tải...</p>
    <p v-else-if="error" class="alert alert-error">{{ error }}</p>
    <p v-else-if="categories.length === 0" class="empty-state">Chưa có danh mục nào.</p>
    <div v-else class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in categories" :key="c.category_id">
            <td>{{ c.category_id }}</td>
            <td>{{ c.category_name }}</td>
            <td>
              <div class="row-actions">
                <button class="btn btn-outline btn-sm" @click="openEdit(c)">Sửa</button>
                <button class="btn btn-danger btn-sm" @click="remove(c)">Xóa</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.form-panel {
  padding: 20px 24px;
  margin-bottom: 20px;
  max-width: 480px;
}

.form-panel h3 {
  margin-top: 0;
}

.form-actions {
  display: flex;
  gap: 10px;
}
</style>
