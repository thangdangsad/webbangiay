export default defineNuxtRouteMiddleware(async (to) => {
  if (import.meta.server) return
  const auth = useAuthStore()
  await auth.init()
  if (!auth.loggedIn) return navigateTo({ path: '/dang-nhap', query: { redirect: to.fullPath } })
})
