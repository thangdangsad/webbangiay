<template>
  <div v-if="clientId">
    <div class="divider">hoặc</div>
    <div ref="btn" style="display:flex;justify-content:center"></div>
    <div v-if="err" class="errmsg">{{ err }}</div>
  </div>
</template>

<script setup lang="ts">
// Google Identity Services: chỉ hiện khi API cấu hình GOOGLE_CLIENT_ID
const { $api } = useNuxtApp()
const auth = useAuthStore(); const route = useRoute()
const { data: info } = await useAsyncData('store-info', async () => (await $api('/store-info')).data, { default: () => null })
const clientId = computed(() => info.value?.google_client_id)
const btn = ref<HTMLElement | null>(null); const err = ref('')

async function onCredential(resp: { credential: string }) {
  err.value = ''
  try { await auth.loginGoogle(resp.credential); await navigateTo(String(route.query.redirect || '/tai-khoan')) } catch (e: any) { err.value = e.message }
}
onMounted(() => {
  if (!clientId.value) return
  const init = () => {
    const g = (window as any).google
    g.accounts.id.initialize({ client_id: clientId.value, callback: onCredential })
    g.accounts.id.renderButton(btn.value, { theme: 'outline', size: 'large', text: 'continue_with', shape: 'pill', width: 360 })
  }
  if ((window as any).google?.accounts) return init()
  const s = document.createElement('script'); s.src = 'https://accounts.google.com/gsi/client'; s.async = true; s.onload = init; document.head.appendChild(s)
})
</script>
