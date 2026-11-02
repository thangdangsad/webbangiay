import { chromium } from 'playwright'
const SH = process.env.SHOTS || new URL('./shots', import.meta.url).pathname
import fs from 'fs'; fs.mkdirSync(SH, { recursive: true })
const b = await chromium.launch()
const errs = []
const log = (ok, n, x = '') => console.log((ok ? 'PASS ' : 'FAIL ') + n + (ok ? '' : ' ' + x))
async function ctxPage() { const c = await b.newContext({ viewport: { width: 1280, height: 900 } }); const p = await c.newPage(); p.on('pageerror', (e) => errs.push('pageerror ' + e.message)); p.on('console', (m) => m.type() === 'error' && errs.push('console ' + m.text())); return p }
// ---- Storefront
let p = await ctxPage()
await p.goto('http://localhost:3000/'); await p.waitForSelector('.card'); log(await p.locator('.card').count() > 4, 'home shows products')
await p.screenshot({ path: SH + '/store-home.png', fullPage: false })
await p.goto('http://localhost:3000/san-pham/air-runner-pro-2'); await p.waitForSelector('.pdp'); await p.waitForLoadState('networkidle')
await p.locator('.sw i').first().click(); await p.locator('.chips .chip:not(.off)').first().click()
await p.locator('text=Thêm vào giỏ').click(); await p.waitForSelector('.toast'); log(true, 'add to cart toast')
await p.screenshot({ path: SH + '/store-pdp.png' })
await p.goto('http://localhost:3000/gio-hang'); await p.waitForSelector('.line'); log(await p.locator('.line').count() === 1, 'cart has line')
await p.fill('input[placeholder="Mã giảm giá"]', 'SALE10'); await p.click('text=Áp dụng'); await p.waitForSelector('.okmsg'); log(true, 'coupon applied')
await p.click('text=Tiến hành thanh toán'); await p.waitForURL('**/thanh-toan')
await p.waitForSelector('select'); await p.waitForTimeout(800)
const f = p.locator('form.cols')
await f.locator('input').nth(0).fill('Nguyen Test'); await f.locator('input').nth(1).fill('0912345678')
const sels = f.locator('select'); await sels.nth(0).selectOption('01'); await p.waitForTimeout(600); await sels.nth(1).selectOption({ index: 1 }); await p.waitForTimeout(600); await sels.nth(2).selectOption({ index: 1 })
await f.locator('input').nth(3).fill('12 Test'); await p.waitForTimeout(500)
await p.screenshot({ path: SH + '/store-checkout.png' })
await p.click('button:has-text("Đặt hàng")'); await p.waitForURL('**/dat-hang-thanh-cong/**', { timeout: 10000 }); await p.waitForSelector('.okc')
log(await p.locator('h1').first().innerText() === 'Đặt hàng thành công!', 'COD order success page')
await p.screenshot({ path: SH + '/store-success.png' })
// login + account
await p.goto('http://localhost:3000/dang-nhap'); await p.fill('input[autocomplete=username]', 'khach@solehouse.vn'); await p.fill('input[type=password]', 'Khach@123'); await p.click('button:has-text("Đăng nhập")')
await p.waitForURL('**/tai-khoan/**'); log(true, 'login → account')
await p.reload(); await p.waitForSelector('.menu'); log(await p.locator('.menu').count() === 1, 'F5 keeps session (refresh cookie)')
await p.goto('http://localhost:3000/tai-khoan/don-hang'); await p.waitForSelector('.ord, .empty'); await p.screenshot({ path: SH + '/store-orders.png' })
// momo flow with fake gateway
await p.goto('http://localhost:3000/san-pham/stan-smith'); await p.waitForSelector('.pdp'); await p.waitForLoadState('networkidle'); await p.locator('.chips .chip:not(.off)').first().click(); await p.locator('text=Thêm vào giỏ').click(); await p.waitForSelector('.toast')
await p.goto('http://localhost:3000/thanh-toan'); await p.waitForTimeout(1500)
{ const f2 = p.locator('form.cols'); const s2 = f2.locator('select'); await f2.locator('input').nth(0).fill('Nguyen Test'); await f2.locator('input').nth(1).fill('0912345678'); await s2.nth(0).selectOption('01'); await p.waitForTimeout(600); await s2.nth(1).selectOption({ index: 1 }); await p.waitForTimeout(600); await s2.nth(2).selectOption({ index: 1 }); await f2.locator('input').nth(3).fill('12 Test') }
await p.locator('label.opt-r:has-text("MoMo")').click(); await p.click('button:has-text("Đặt hàng")')
await p.waitForURL('**/payments/momo/fake/**', { timeout: 10000 }); log(true, 'redirect to MoMo (fake)')
await p.click('text=Xác nhận thanh toán'); await p.waitForURL('**/dat-hang-thanh-cong/**'); await p.waitForSelector('.okc'); log((await p.locator('.box').first().innerText()).includes('Đã thanh toán'), 'MoMo paid shown')
// ---- Admin
p = await ctxPage()
await p.goto('http://localhost:5173/'); await p.waitForSelector('input[type=email]'); await p.fill('input[type=email]', 'admin@solehouse.vn'); await p.fill('input[type=password]', 'Admin@123'); await p.click('button:has-text("Đăng nhập")')
await p.waitForSelector('.kpis'); log(true, 'admin login → dashboard'); await p.screenshot({ path: SH + '/admin-dashboard.png' })
for (const [path, sel, name] of [['/products', '.tb tbody tr', 'products'], ['/orders', '.tb tbody tr', 'orders'], ['/inventory', '.tb tbody tr', 'inventory'], ['/inventory/movements', '.tb tbody tr', 'movements'], ['/customers', '.tb tbody tr', 'customers'], ['/marketing/coupons', '.tb tbody tr', 'coupons'], ['/marketing/promotions', '.tb tbody tr', 'promotions'], ['/marketing/banners', '.tb tbody tr', 'banners'], ['/cms', '.tb tbody tr', 'cms'], ['/catalog', '.tb tbody tr', 'catalog'], ['/reports', '.kpis', 'reports'], ['/settings', '.card', 'settings'], ['/settings/shipping', '.tb tbody tr', 'shipping'], ['/settings/staff', '.matrix', 'staff'], ['/settings/audit-log', '.tb tbody tr', 'audit'], ['/returns', '.tb', 'returns'], ['/inventory/receipts', '.card', 'receipts']]) {
  await p.goto('http://localhost:5173' + path); try { await p.waitForSelector(sel, { timeout: 6000 }); log(true, 'admin ' + name) } catch { log(false, 'admin ' + name) }
}
await p.goto('http://localhost:5173/orders'); await p.waitForSelector('.tb tbody tr'); await p.locator('.tb tbody tr a.lnk').first().click(); await p.waitForSelector('.flow'); await p.screenshot({ path: SH + '/admin-order.png' })
await p.goto('http://localhost:5173/products/1'); await p.waitForSelector('.vtable'); await p.screenshot({ path: SH + '/admin-product.png' })
await p.locator('button:has-text("Lưu sản phẩm")').click(); await p.waitForSelector('.toast', { timeout: 8000 }); log(true, 'admin save product')
await p.goto('http://localhost:5173/products'); await p.waitForSelector('.tb tbody tr'); await p.screenshot({ path: SH + '/admin-products.png' })
await p.reload(); await p.waitForSelector('.tb tbody tr'); log(true, 'admin F5 keeps session')
console.log('--- errors:', [...new Set(errs)].slice(0, 15))
await b.close()
