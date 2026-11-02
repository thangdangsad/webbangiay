export const money = (n) => (n == null ? '' : new Intl.NumberFormat('vi-VN').format(n) + 'đ')
export const dt = (s) => (s ? new Date(s).toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', hour12: false, day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—')
export const dOnly = (s) => (s ? new Date(s).toLocaleDateString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' }) : '—')
export const toInputDt = (s) => (s ? new Date(new Date(s).getTime() + 7 * 3600e3).toISOString().slice(0, 16) : '')
export const fromInputDt = (s) => (s ? new Date(s + ':00+07:00').toISOString() : null)

export const ORDER_STATUS = {
  pending: ['Chờ xác nhận', 'p-yellow'], confirmed: ['Đã xác nhận', 'p-blue'], packing: ['Đang đóng gói', 'p-blue'], shipping: ['Đang giao', 'p-purple'],
  completed: ['Hoàn thành', 'p-green'], cancelled: ['Đã hủy', 'p-red'], returned: ['Đổi trả', 'p-gray'],
}
export const PAY_STATUS = { unpaid: ['Chưa thanh toán', 'p-gray'], pending: ['Chờ thanh toán', 'p-yellow'], paid: ['Đã thanh toán', 'p-green'], refunded: ['Đã hoàn tiền', 'p-gray'], partial_refund: ['Hoàn một phần', 'p-gray'] }
export const payMethod = (m) => (m === 'momo' ? 'MoMo' : 'COD')
