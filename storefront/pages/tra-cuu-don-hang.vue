<template>
  <div class="container"><div class="center" style="max-width:720px">
    <h1>Tra cứu đơn hàng</h1><p style="color:var(--muted);margin:8px 0 20px">Nhập mã đơn và số điện thoại đặt hàng.</p>
    <form class="fgrid" style="text-align:left" @submit.prevent="submit">
      <div class="fld"><label>Mã đơn hàng</label><input v-model="code" placeholder="SH-261102-0001" required /></div>
      <div class="fld"><label>Số điện thoại</label><input v-model="phone" required /></div>
      <div class="full"><button class="btn brand block" :disabled="busy">Tra cứu</button></div>
    </form>
    <div v-if="err" class="errmsg">{{ err }}</div>
    <div v-if="o" class="box" style="margin-top:22px;text-align:left">
      <div style="display:flex;justify-content:space-between"><b>{{ o.code }}</b><span class="status" :class="statusClass(o.status)">{{ statusLabel(o.status) }}</span></div>
      <div class="tl-v" style="margin:18px 0"><div v-for="(t, i) in o.timeline" :key="i" class="on"><b>{{ t.label }}</b><small>{{ dateTime(t.at) }}<template v-if="t.note"> · {{ t.note }}</template></small></div></div>
      <div v-if="o.shipment?.tracking_code" class="callout" style="color:#1e40af;background:#eff6ff;border-color:#bfdbfe">Vận đơn {{ o.shipment.carrier }}: <b>{{ o.shipment.tracking_code }}</b></div>
      <div v-for="i in o.items" :key="i.id" class="row"><span>{{ i.name }} ({{ i.size }}) × {{ i.qty }}</span><span>{{ money(i.line_total) }}</span></div>
      <div class="row total"><span>Tổng</span><span>{{ money(o.grand_total) }}</span></div>
    </div>
  </div></div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp(); const route = useRoute()
const code = ref(String(route.query.code ?? '')); const phone = ref(''); const o = ref<any>(null); const err = ref(''); const busy = ref(false)
async function submit() { err.value = ''; o.value = null; busy.value = true
  try { o.value = (await $api('/orders/track', { query: { code: code.value, phone: phone.value } })).data } catch (e: any) { err.value = e.message } finally { busy.value = false } }
useSeoMeta({ title: 'Tra cứu đơn hàng', robots: 'noindex' })
</script>
