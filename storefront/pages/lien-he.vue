<template>
  <div class="container"><div class="crumb"><NuxtLink to="/">Trang chủ</NuxtLink> › Liên hệ</div>
    <div class="two" style="grid-template-columns:1fr 1fr">
      <div><h1>Liên hệ</h1><p style="color:var(--muted);margin:8px 0 18px">Chúng tôi phản hồi trong vòng 24 giờ.</p>
        <dl class="kv"><dt>Hotline</dt><dd>{{ info?.phone }}</dd><dt>Email</dt><dd>{{ info?.email }}</dd><dt>Địa chỉ</dt><dd>{{ info?.address }}</dd></dl></div>
      <form class="box" @submit.prevent="send"><div class="fld" style="margin-bottom:12px"><label>Họ tên</label><input v-model="f.name" required /></div>
        <div class="fld" style="margin-bottom:12px"><label>Email</label><input v-model="f.email" type="email" required /></div>
        <div class="fld" style="margin-bottom:12px"><label>SĐT</label><input v-model="f.phone" /></div>
        <div class="fld" style="margin-bottom:12px"><label>Nội dung</label><textarea v-model="f.message" required></textarea></div>
        <div v-if="ok" class="okmsg">Đã gửi! Chúng tôi sẽ liên hệ sớm.</div><div v-if="err" class="errmsg">{{ err }}</div>
        <button class="btn brand block">Gửi liên hệ</button></form></div></div>
</template>
<script setup lang="ts">
const { $api } = useNuxtApp()
const { data: info } = await useAsyncData('contact-info', async () => (await $api('/store-info')).data)
const f = reactive({ name: '', email: '', phone: '', message: '' }); const ok = ref(false); const err = ref('')
async function send() { err.value = ''; try { await $api('/contact', { method: 'POST', body: { ...f, phone: f.phone || null } }); ok.value = true; Object.assign(f, { name: '', email: '', phone: '', message: '' }) } catch (e: any) { err.value = e.message } }
useSeoMeta({ title: 'Liên hệ' })
</script>
