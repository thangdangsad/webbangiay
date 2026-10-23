<script setup>
import { onMounted, reactive, ref } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

import { http } from '@/api/client'

const confirm = useConfirm()
const toast = useToast()

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
    toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã lưu danh mục.', life: 3000 })
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

function confirmRemove(category) {
  confirm.require({
    message: `Xóa danh mục "${category.category_name}"?`,
    header: 'Xác nhận xóa',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Xóa',
    rejectLabel: 'Hủy',
    acceptProps: { severity: 'danger' },
    accept: () => remove(category),
  })
}

async function remove(category) {
  try {
    await http.delete(`/api/admin/categories/${category.category_id}`)
    toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã xóa danh mục.', life: 3000 })
    await load()
  } catch {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Xóa danh mục không thành công.', life: 4000 })
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Danh mục</h1>
      <Button label="Thêm danh mục" icon="pi pi-plus" @click="openCreate" />
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <DataTable
      v-else
      :value="categories"
      :loading="loading"
      data-key="category_id"
      striped-rows
      paginator
      :rows="15"
      responsive-layout="scroll"
    >
      <template #empty>Chưa có danh mục nào.</template>
      <Column field="category_id" header="ID" style="width: 90px" />
      <Column field="category_name" header="Tên danh mục" />
      <Column header="" style="width: 160px">
        <template #body="{ data }">
          <div class="row-actions">
            <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
            <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmRemove(data)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog
      v-model:visible="formVisible"
      modal
      :header="editingId ? 'Sửa danh mục' : 'Thêm danh mục'"
      :style="{ width: '420px' }"
    >
      <form class="dialog-form" @submit.prevent="submit">
        <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>
        <div class="field">
          <label for="category_name">Tên danh mục *</label>
          <InputText id="category_name" v-model="form.category_name" required fluid autofocus />
          <small v-if="fieldErrors.category_name" class="field-error">{{ fieldErrors.category_name[0] }}</small>
        </div>
      </form>
      <template #footer>
        <Button label="Hủy" text severity="secondary" @click="cancelForm" />
        <Button label="Lưu" icon="pi pi-check" :loading="submitting" @click="submit" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.row-actions {
  display: flex;
  gap: 4px;
}

.dialog-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
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
</style>
