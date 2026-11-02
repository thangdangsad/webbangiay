// F5: khôi phục phiên (refresh cookie) rồi nạp giỏ hàng + yêu thích.
// Không await ở đây để không chặn hydrate (tránh lệch HTML SSR); middleware `auth` tự await auth.init().
export default defineNuxtPlugin(() => {
  const auth = useAuthStore()
  auth.init().then(() => Promise.all([useCartStore().fetch(), useWishlistStore().fetch()]))
})
