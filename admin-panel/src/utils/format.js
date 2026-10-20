import { API_URL } from '@/api/client'

/** Format an integer VND amount the way legacy views did: number_format(x) + 'đ'. */
export function formatPrice(value) {
  const n = Number(value) || 0
  return `${n.toLocaleString('vi-VN')}đ`
}

/**
 * Best-effort image URL for a product's `product_image` filename.
 * Admin-uploaded images live under storage/products/<file> on the "public"
 * disk (served at /storage/products/<file>).
 */
export function productImageUrl(product) {
  if (!product?.product_image) {
    return null
  }
  return `${API_URL}/storage/products/${product.product_image}`
}

/** Vietnamese label for a donhang.trangThai order status code. */
export function orderStatusLabel(status) {
  const n = status === null || status === undefined ? 0 : Number(status)
  return { 0: 'Chờ xác nhận', 1: 'Đã xác nhận', 2: 'Đã hủy' }[n] ?? 'Không rõ'
}

export function formatDate(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString('vi-VN')
}
