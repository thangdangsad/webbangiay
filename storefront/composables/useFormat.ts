export const money = (n: number | null | undefined) => (n == null ? '' : new Intl.NumberFormat('vi-VN').format(n) + 'đ')
export const dateTime = (s?: string | null) =>
  s ? new Date(s).toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', hour12: false, day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''
export const dateOnly = (s?: string | null) => (s ? new Date(s).toLocaleDateString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' }) : '')
export const statusClass = (s: string) =>
  ({ pending: 'st-pend', confirmed: 'st-pend', packing: 'st-ship', shipping: 'st-ship', completed: 'st-done', cancelled: 'st-cancel', returned: 'st-cancel' } as Record<string, string>)[s] || 'st-pend'
export const statusLabel = (s: string) =>
  ({ pending: 'Chờ xác nhận', confirmed: 'Đã xác nhận', packing: 'Đang đóng gói', shipping: 'Đang giao', completed: 'Hoàn thành', cancelled: 'Đã hủy', returned: 'Đổi trả' } as Record<string, string>)[s] || s
export const payLabel = (m: string) => (m === 'momo' ? 'Ví MoMo' : 'COD')
export const newKey = () => (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random())
