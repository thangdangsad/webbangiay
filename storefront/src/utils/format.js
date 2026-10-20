import { API_URL } from '@/api/client'

/** Format an integer VND amount the way legacy views did: number_format(x) + 'đ'. */
export function formatPrice(value) {
  const n = Number(value) || 0
  return `${n.toLocaleString('vi-VN')}đ`
}

/**
 * Best-effort image URL for a product's `product_image` filename.
 * New admin-uploaded images live under storage/products/<file> on the
 * "public" disk (served at /storage/products/<file>); there is no real
 * image data yet, so this is a best effort — the <img onerror> fallback in
 * ProductCard/ProductDetail swaps in a local placeholder if this 404s.
 */
export function productImageUrl(product) {
  if (!product?.product_image) {
    return null
  }
  return `${API_URL}/storage/products/${product.product_image}`
}
