<script setup>
import { onMounted, ref, watch } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

import { http } from '@/api/client'
import { formatPrice, productImageUrl } from '@/utils/format'

const confirm = useConfirm()
const toast = useToast()

const products = ref([])
const categories = ref([])
const loading = ref(true)
const error = ref('')
const deletingId = ref(null)
const expandedRows = ref({})

/**
 * Aggregate "current sellable stock" for the list column: sum of
 * product_quantity across only the ACTIVE variants (a deactivated size is
 * no longer sellable, so it shouldn't count toward displayed stock).
 */
function activeStock(product) {
  return (product.variants || [])
    .filter((v) => v.is_active !== false)
    .reduce((sum, v) => sum + (Number(v.product_quantity) || 0), 0)
}

const search = ref('')
const categoryId = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

let searchDebounce = null

async function load(currentPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/api/admin/products', {
      params: {
        q: search.value || undefined,
        category_id: categoryId.value || undefined,
        per_page: 15,
        page: currentPage,
      },
    })
    products.value = data.data
    page.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    total.value = data.meta.total
  } catch {
    error.value = 'Không thể tải danh sách sản phẩm.'
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const { data } = await http.get('/api/categories')
    categories.value = data.data
  } catch {
    categories.value = []
  }
}

function onPage(event) {
  load(event.page + 1)
}

watch(search, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => load(1), 350)
})

watch(categoryId, () => load(1))

onMounted(() => {
  loadCategories()
  load(1)
})

function confirmRemove(product) {
  confirm.require({
    message: `Xóa sản phẩm "${product.product_name}" sẽ xóa VĨNH VIỄN sản phẩm này VÀ TẤT CẢ các size của nó (không phải chỉ một size). Hành động này không thể hoàn tác. Nếu bạn chỉ muốn ngừng bán một size, hãy xóa size đó trong màn hình sửa sản phẩm thay vì xóa cả sản phẩm ở đây.`,
    header: 'Xác nhận xóa toàn bộ sản phẩm',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Xóa toàn bộ',
    rejectLabel: 'Hủy',
    acceptProps: { severity: 'danger' },
    accept: () => remove(product),
  })
}

async function remove(product) {
  deletingId.value = product.product_id
  try {
    await http.delete(`/api/admin/products/${product.product_id}`)
    toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã xóa sản phẩm.', life: 3000 })
    await load(page.value)
  } catch {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Xóa sản phẩm không thành công.', life: 4000 })
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Sản phẩm</h1>
      <RouterLink :to="{ name: 'product-new' }">
        <Button label="Thêm sản phẩm" icon="pi pi-plus" />
      </RouterLink>
    </div>

    <div class="toolbar">
      <IconField class="search-field">
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Tìm theo tên sản phẩm" fluid />
      </IconField>
      <Select
        v-model="categoryId"
        :options="[{ category_id: '', category_name: 'Tất cả danh mục' }, ...categories]"
        option-label="category_name"
        option-value="category_id"
        class="category-filter"
      />
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <DataTable
      v-else
      v-model:expanded-rows="expandedRows"
      :value="products"
      :loading="loading"
      data-key="product_id"
      striped-rows
      lazy
      paginator
      :rows="15"
      :total-records="total"
      :first="(page - 1) * 15"
      @page="onPage"
    >
      <template #empty>Không có sản phẩm nào.</template>
      <Column expander style="width: 48px" />
      <Column header="" style="width: 64px">
        <template #body="{ data }">
          <img
            :src="productImageUrl(data) || '/placeholder.svg'"
            :alt="data.product_name"
            class="row-thumb"
            @error="$event.target.src = '/placeholder.svg'"
          />
        </template>
      </Column>
      <Column field="product_name" header="Tên sản phẩm" />
      <Column header="Danh mục">
        <template #body="{ data }">{{ data.category?.category_name || '—' }}</template>
      </Column>
      <Column header="Giá">
        <template #body="{ data }">{{ formatPrice(data.product_price) }}</template>
      </Column>
      <Column header="Giảm giá">
        <template #body="{ data }">{{ data.product_discount ? formatPrice(data.product_discount) : '—' }}</template>
      </Column>
      <Column header="Số lượng (đang bán)">
        <template #body="{ data }">{{ activeStock(data) }}</template>
      </Column>
      <Column header="" style="width: 140px">
        <template #body="{ data }">
          <div class="row-actions">
            <RouterLink :to="{ name: 'product-edit', params: { id: data.product_id } }">
              <Button icon="pi pi-pencil" text rounded severity="secondary" />
            </RouterLink>
            <Button
              icon="pi pi-trash"
              text
              rounded
              severity="danger"
              :loading="deletingId === data.product_id"
              @click="confirmRemove(data)"
            />
          </div>
        </template>
      </Column>

      <template #expansion="{ data }">
        <div class="variant-breakdown">
          <strong class="variant-breakdown-title">Chi tiết theo size — {{ data.product_name }}</strong>
          <DataTable :value="data.variants || []" data-key="variant_id" size="small">
            <template #empty>Không có size nào.</template>
            <Column field="size" header="Size" />
            <Column field="product_quantity" header="Số lượng" />
            <Column header="Trạng thái">
              <template #body="{ data: variant }">
                <Tag
                  :severity="variant.is_active === false ? 'secondary' : 'success'"
                  :value="variant.is_active === false ? 'Ngừng bán' : 'Đang bán'"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.search-field {
  flex: 1;
  max-width: 320px;
}

.category-filter {
  min-width: 220px;
}

.row-thumb {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  background: var(--p-surface-100);
  display: block;
}

.row-actions {
  display: flex;
  gap: 4px;
}

.variant-breakdown {
  padding: 12px 16px;
}

.variant-breakdown-title {
  display: block;
  margin-bottom: 10px;
  font-size: 14px;
}
</style>
