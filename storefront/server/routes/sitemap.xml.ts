// sitemap.xml: trang tĩnh + toàn bộ sản phẩm/danh mục đang bán (lấy từ API)
export default defineEventHandler(async (event) => {
  const cfg = useRuntimeConfig()
  const site = cfg.public.siteUrl
  const get = async (p: string) => (await $fetch<any>(`${cfg.apiBaseServer}${p}`).catch(() => ({ data: [] }))).data
  const [products, cats] = await Promise.all([$fetch<any>(`${cfg.apiBaseServer}/products?limit=48`).then((r) => r.data).catch(() => []), get('/categories')])
  const urls = ['/', '/san-pham', '/khuyen-mai', '/huong-dan-chon-size', '/lien-he', '/gioi-thieu',
    ...cats.flatMap((c: any) => [`/danh-muc/${c.slug}`, ...(c.children || []).map((x: any) => `/danh-muc/${x.slug}`)]),
    ...products.map((p: any) => `/san-pham/${p.slug}`)]
  setHeader(event, 'Content-Type', 'application/xml')
  return `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${urls.map((u) => `<url><loc>${site}${u}</loc></url>`).join('')}</urlset>`
})
