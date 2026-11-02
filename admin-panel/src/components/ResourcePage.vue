<template>
  <div>
    <div class="ph" v-if="!embedded"><div><h1>{{ title }}</h1><p v-if="subtitle">{{ subtitle }}</p></div><div class="acts"><button v-if="canWrite" class="btn pri" @click="edit()">+ Thêm mới</button></div></div>
    <div v-else class="acts" style="margin-bottom:12px;justify-content:flex-end"><button v-if="canWrite" class="btn pri sm" @click="edit()">+ Thêm</button></div>
    <div class="card tcard">
      <div class="tbar" v-if="searchable"><input v-model="q" class="f" placeholder="Tìm kiếm..." @input="debounced" /></div>
      <div v-if="loading" class="loading">Đang tải...</div>
      <table v-else class="tb">
        <thead><tr><th v-for="c in columns" :key="c.key">{{ c.label }}</th><th></th></tr></thead>
        <tbody>
          <tr v-for="r in rows" :key="r.id">
            <td v-for="c in columns" :key="c.key" :class="c.align"><slot :name="c.key" :row="r" :value="r[c.key]"><template v-if="c.type === 'bool'"><span class="pill" :class="r[c.key] ? 'p-green' : 'p-gray'">{{ r[c.key] ? 'Bật' : 'Tắt' }}</span></template><template v-else-if="c.type === 'money'">{{ money(r[c.key]) }}</template><template v-else-if="c.type === 'date'">{{ dt(r[c.key]) }}</template><template v-else-if="c.type === 'color'"><span class="swatch" :style="{ background: r[c.key] }"></span>{{ r[c.key] }}</template><template v-else-if="c.type === 'img'"><img v-if="r[c.key]" :src="r[c.key]" style="height:36px;border-radius:6px" /></template><template v-else>{{ c.format ? c.format(r[c.key], r) : r[c.key] ?? '—' }}</template></slot></td>
            <td class="r" style="white-space:nowrap"><template v-if="canWrite"><button class="btn sm" @click="edit(r)">Sửa</button> <button class="btn sm danger" @click="remove(r)">Xóa</button></template></td>
          </tr>
          <tr v-if="!rows.length"><td :colspan="columns.length + 1" class="loading">Chưa có dữ liệu</td></tr>
        </tbody>
      </table>
      <Pager v-if="meta && meta.total > meta.limit" :meta="meta" @go="(n) => { page = n; load() }" />
    </div>

    <Modal v-if="form" :title="(form.id ? 'Sửa ' : 'Thêm ') + noun" @close="form = null">
      <form @submit.prevent="save">
        <div class="g2">
          <div v-for="f in fields" :key="f.key" class="fld" :style="f.full ? 'grid-column:1/-1' : ''">
            <label>{{ f.label }}</label>
            <select v-if="f.type === 'select'" v-model="form[f.key]" class="f"><option v-for="o in f.options" :key="o[0]" :value="o[0]">{{ o[1] }}</option></select>
            <textarea v-else-if="f.type === 'textarea'" v-model="form[f.key]" class="f" :style="f.rows ? `height:${f.rows * 24}px` : ''"></textarea>
            <label v-else-if="f.type === 'bool'" class="chk"><input v-model="form[f.key]" type="checkbox" /> {{ f.hint || 'Bật' }}</label>
            <input v-else-if="f.type === 'color'" v-model="form[f.key]" type="color" class="f" style="padding:2px" />
            <input v-else-if="f.type === 'datetime'" v-model="form[f.key]" type="datetime-local" class="f" />
            <div v-else-if="f.type === 'image'">
              <img v-if="form[f.key + '_url']" :src="form[f.key + '_url']" style="height:70px;border-radius:8px;margin-bottom:8px" />
              <ImageUpload :type="f.upload || 'banner'" label="Chọn ảnh" @uploaded="(u) => { form[f.key] = u.path; form[f.key + '_url'] = u.url }" />
            </div>
            <input v-else v-model="form[f.key]" class="f" :type="f.type === 'number' ? 'number' : 'text'" :placeholder="f.placeholder" />
            <small v-if="f.help" class="muted">{{ f.help }}</small><small v-if="fe[f.key]" style="color:var(--bad)">{{ fe[f.key] }}</small>
          </div>
        </div>
        <slot name="form-extra" :form="form" />
        <div v-if="err" class="errmsg">{{ err }}</div>
        <div class="acts" style="justify-content:flex-end;margin-top:8px"><button type="button" class="btn" @click="form = null">Hủy</button><button class="btn pri" :disabled="saving">Lưu</button></div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api, { money, dt, toInputDt, fromInputDt } from '../api'
import { useAuthStore } from '../stores/auth'
import { useUi } from '../stores/ui'
import Pager from './Pager.vue'
import Modal from './Modal.vue'
import ImageUpload from './ImageUpload.vue'

const props = defineProps({
  endpoint: String, title: String, subtitle: String, noun: { type: String, default: 'mục' }, columns: Array, fields: Array,
  writePerm: String, searchable: { type: Boolean, default: true }, embedded: Boolean, params: Object, defaults: Object,
  beforeSave: Function, afterLoad: Function, toForm: Function,
})
const auth = useAuthStore(); const ui = useUi()
const rows = ref([]); const meta = ref(null); const loading = ref(true); const q = ref(''); const page = ref(1)
const form = ref(null); const err = ref(''); const fe = ref({}); const saving = ref(false)
const canWrite = computed(() => !props.writePerm || auth.can(props.writePerm))
let t
const debounced = () => { clearTimeout(t); t = setTimeout(() => { page.value = 1; load() }, 300) }

async function load() {
  loading.value = true
  try {
    const res = (await api.get(`/admin/${props.endpoint}`, { params: { q: q.value || undefined, page: page.value, ...(props.params || {}) } })).data
    rows.value = props.afterLoad ? props.afterLoad(res.data) : res.data
    meta.value = res.meta
  } finally { loading.value = false }
}
function edit(r) {
  err.value = ''; fe.value = {}
  const base = r ? { ...r } : { ...(props.defaults || {}) }
  for (const f of props.fields) if (f.type === 'datetime') base[f.key] = toInputDt(base[f.key])
  form.value = props.toForm ? props.toForm(base, r) : base
}
async function save() {
  err.value = ''; fe.value = {}; saving.value = true
  try {
    const body = {}
    for (const f of props.fields) {
      let v = form.value[f.key]
      if (f.type === 'datetime') v = fromInputDt(v)
      if (f.type === 'number') v = v === '' || v == null ? (f.nullable ? null : 0) : Number(v)
      if (f.type === 'bool') v = !!v
      if (v === '' && f.nullable !== false && f.type !== 'number') v = null
      body[f.key] = v
    }
    const payload = props.beforeSave ? props.beforeSave(body, form.value) : body
    if (form.value.id) await api.put(`/admin/${props.endpoint}/${form.value.id}`, payload)
    else await api.post(`/admin/${props.endpoint}`, payload)
    form.value = null; ui.notify('Đã lưu'); await load()
  } catch (e) { err.value = e.message; fe.value = e.fields || {} } finally { saving.value = false }
}
async function remove(r) {
  if (!confirm('Xóa mục này?')) return
  try { await api.delete(`/admin/${props.endpoint}/${r.id}`); ui.notify('Đã xóa'); await load() } catch (e) { ui.notify(e.message) }
}
onMounted(load)
defineExpose({ load })
</script>
