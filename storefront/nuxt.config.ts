// Storefront Nuxt 3: SSR cho trang công khai; trang cần đăng nhập/giỏ hàng render phía trình duyệt
// (cookie refresh token thuộc origin của API nên server Nuxt không thể gọi API thay người dùng).
export default defineNuxtConfig({
  compatibilityDate: '2025-05-01',
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  css: ['~/assets/css/store.css'],
  runtimeConfig: {
    apiBaseServer: process.env.NUXT_API_BASE_SERVER || 'http://localhost:8000/api/v1',
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api/v1',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
    },
  },
  routeRules: {
    // SSR + cache SWR cho trang công khai (HTML không chứa dữ liệu theo người dùng)
    '/': { swr: 60 },
    '/san-pham/**': { swr: 60 },
    '/huong-dan-chon-size': { swr: 600 },
    '/chinh-sach/**': { swr: 600 },
    '/gio-hang': { ssr: false },
    '/thanh-toan/**': { ssr: false },
    '/thanh-toan': { ssr: false },
    '/dat-hang-thanh-cong/**': { ssr: false },
    '/tai-khoan/**': { ssr: false },
    '/dang-nhap': { ssr: false },
    '/dang-ky': { ssr: false },
    '/quen-mat-khau': { ssr: false },
    '/tra-cuu-don-hang': { ssr: false },
  },
  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      titleTemplate: (t) => (t ? `${t} · SoleHouse` : 'SoleHouse – Giày chính hãng'),
      meta: [
        { name: 'viewport', content: 'width=device-width,initial-scale=1' },
        { name: 'description', content: 'SoleHouse – giày chính hãng, giao nhanh toàn quốc, đổi size dễ dàng.' },
      ],
    },
  },
})
