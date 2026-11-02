<template>
  <ResourcePage endpoint="coupons" title="Mã giảm giá" subtitle="Giảm theo %, số tiền hoặc miễn phí vận chuyển" noun="mã giảm giá" write-perm="marketing:manage"
    :columns="[{ key: 'code', label: 'Mã' }, { key: 'type', label: 'Loại', format: (v) => TYPES[v] }, { key: 'value', label: 'Giá trị', format: val }, { key: 'min_order', label: 'Đơn tối thiểu', type: 'money' }, { key: 'used', label: 'Đã dùng', format: (v, r) => `${v}${r.usage_limit ? ' / ' + r.usage_limit : ''}` }, { key: 'ends_at', label: 'Hết hạn', type: 'date' }, { key: 'is_active', label: 'Trạng thái', type: 'bool' }]"
    :fields="[{ key: 'code', label: 'Mã *' }, { key: 'type', label: 'Loại', type: 'select', options: Object.entries(TYPES) }, { key: 'value', label: 'Giá trị (% hoặc đ)', type: 'number', help: 'Free ship: để 0' }, { key: 'min_order', label: 'Đơn tối thiểu', type: 'number' },
      { key: 'max_discount', label: 'Giảm tối đa', type: 'number', nullable: true }, { key: 'usage_limit', label: 'Giới hạn lượt', type: 'number', nullable: true }, { key: 'per_user_limit', label: 'Lượt / khách', type: 'number', nullable: true },
      { key: 'starts_at', label: 'Bắt đầu', type: 'datetime' }, { key: 'ends_at', label: 'Kết thúc', type: 'datetime' }, { key: 'is_active', label: 'Kích hoạt', type: 'bool' }]"
    :defaults="{ type: 'percent', value: 10, min_order: 0, is_active: true }" />
</template>
<script setup>
import ResourcePage from '../components/ResourcePage.vue'
import { money } from '../api'
const TYPES = { percent: 'Phần trăm', fixed: 'Số tiền', free_ship: 'Miễn phí ship' }
const val = (v, r) => (r.type === 'percent' ? v + '%' : r.type === 'fixed' ? money(v) : '—')
</script>
