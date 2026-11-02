# Tài liệu BA – Website bán giày

> Phiên bản 1.0 · 02/11/2026
> Phạm vi: **(A) Web bán hàng (Storefront)** và **(B) Web quản trị (Admin)**.

---

## 1. Tổng quan

### 1.1 Mục tiêu kinh doanh
- Bán giày online (sneaker, giày thể thao, giày da, sandal…) cho khách lẻ.
- Giảm thao tác thủ công của chủ shop: quản lý sản phẩm, tồn kho, đơn hàng, khuyến mãi trên một hệ thống.
- Theo dõi doanh thu, sản phẩm bán chạy, tồn kho thấp để ra quyết định.

### 1.2 Các nhóm người dùng

| Vai trò | Mô tả | Hệ thống |
|---|---|---|
| Khách vãng lai (Guest) | Xem, tìm kiếm, mua không cần tài khoản | Storefront |
| Khách hàng (Customer) | Có tài khoản, xem lịch sử đơn, wishlist, sổ địa chỉ | Storefront |
| Chủ shop / Admin | Toàn quyền | Admin |
| Quản lý (Manager) | Sản phẩm, đơn hàng, khuyến mãi, báo cáo | Admin |
| Nhân viên kho (Warehouse) | Tồn kho, nhập hàng, xử lý đóng gói | Admin |
| Chăm sóc khách hàng (CSKH) | Xem/cập nhật đơn, đổi trả, khách hàng | Admin |

### 1.3 Đặc thù ngành giày (ảnh hưởng thiết kế)
- Sản phẩm có **biến thể = Màu × Size**; tồn kho quản lý theo từng biến thể (SKU).
- Cần **bảng quy đổi size** (EU/US/UK/cm) và gợi ý chọn size.
- Tỉ lệ **đổi size / đổi trả** cao → cần quy trình đổi trả rõ ràng.
- Hình ảnh nhiều góc, ảnh theo màu.

### 1.4 Ngoài phạm vi phiên bản đầu (gợi ý v2)
App mobile native, marketplace (Shopee/Lazada) đồng bộ, loyalty/điểm thưởng, đa ngôn ngữ, đa kho.

---

## 2. Sitemap

### 2.1 Storefront

```
/                         Trang chủ
├── /san-pham             Danh sách sản phẩm (lọc, sắp xếp, phân trang)
│   └── /san-pham/:slug   Chi tiết sản phẩm
├── /danh-muc/:slug       Danh mục / thương hiệu
├── /tim-kiem?q=          Kết quả tìm kiếm
├── /khuyen-mai           Khuyến mãi / Sale
├── /gio-hang             Giỏ hàng
├── /thanh-toan           Thanh toán (checkout)
│   └── /dat-hang-thanh-cong/:orderCode
├── /dang-nhap · /dang-ky · /quen-mat-khau
├── /tai-khoan            Tài khoản (yêu cầu đăng nhập)
│   ├── /tai-khoan/thong-tin
│   ├── /tai-khoan/don-hang
│   │   └── /tai-khoan/don-hang/:code
│   ├── /tai-khoan/dia-chi
│   └── /tai-khoan/yeu-thich
├── /tra-cuu-don-hang     Tra cứu đơn (guest: mã đơn + SĐT)
├── /huong-dan-chon-size  Bảng size
├── /chinh-sach/*         Đổi trả, bảo hành, vận chuyển, bảo mật
└── /lien-he · /gioi-thieu
```

### 2.2 Admin

```
/admin/login
/admin
├── Dashboard
├── Sản phẩm
│   ├── Danh sách
│   ├── Thêm / Sửa sản phẩm (biến thể, ảnh, SEO)
│   ├── Danh mục
│   ├── Thương hiệu
│   └── Thuộc tính (màu, size)
├── Kho hàng
│   ├── Tồn kho theo SKU
│   ├── Phiếu nhập / điều chỉnh
│   └── Lịch sử biến động
├── Đơn hàng
│   ├── Danh sách
│   ├── Chi tiết đơn
│   └── Đổi trả / hoàn tiền
├── Khách hàng
│   ├── Danh sách
│   └── Chi tiết khách
├── Marketing
│   ├── Mã giảm giá (coupon)
│   ├── Chương trình khuyến mãi
│   └── Banner trang chủ
├── Báo cáo
│   ├── Doanh thu
│   ├── Sản phẩm bán chạy
│   └── Tồn kho
├── Nội dung (trang tĩnh, chính sách)
└── Cài đặt
    ├── Thông tin cửa hàng
    ├── Vận chuyển (phí, đơn vị)
    ├── Thanh toán (COD, Ví MoMo)
    ├── Nhân viên & phân quyền
    └── Nhật ký hoạt động
```

---

## 3. Mô tả chi tiết các trang – Storefront

### S01 · Trang chủ
**Mục đích:** Thu hút, dẫn khách vào sản phẩm.
**Thành phần:** Header (logo, menu danh mục, tìm kiếm, tài khoản, giỏ hàng); banner hero (do admin cấu hình); danh mục nổi bật; Hàng mới về; Bán chạy; khối khuyến mãi; thương hiệu; cam kết (giao nhanh, đổi trả 7 ngày…); đăng ký nhận tin; footer.
**Quy tắc:** Banner/khối sản phẩm lấy từ cấu hình Admin; chỉ hiển thị sản phẩm còn hàng và đang bán.

### S02 · Danh sách sản phẩm
**Thành phần:** Breadcrumb, bộ lọc (thương hiệu, danh mục, giới tính, size, màu, khoảng giá, đang giảm giá), sắp xếp (mới nhất, giá tăng/giảm, bán chạy), lưới sản phẩm, phân trang.
**Thẻ sản phẩm:** ảnh (đổi ảnh khi hover), tên, giá (giá gốc gạch ngang nếu giảm), nhãn (Mới/Sale/Hết hàng), chấm màu, nút yêu thích.
**Quy tắc:** Lọc theo size chỉ hiện sản phẩm có tồn kho > 0 ở size đó; URL giữ trạng thái lọc để chia sẻ được.

### S03 · Chi tiết sản phẩm
**Thành phần:** Gallery ảnh (theo màu), tên, mã SKU, đánh giá, giá, chọn màu, chọn size (size hết hàng bị làm mờ), link "Hướng dẫn chọn size", số lượng, nút **Thêm vào giỏ** / **Mua ngay**, yêu thích, thông tin giao hàng & đổi trả, tab mô tả/chất liệu/đánh giá, sản phẩm liên quan.
**Quy tắc:** Phải chọn đủ màu + size mới thêm giỏ; số lượng tối đa = tồn kho của biến thể; giá hiển thị theo giá khuyến mãi đang hiệu lực.

### S04 · Giỏ hàng
**Thành phần:** Danh sách dòng hàng (ảnh, tên, màu/size, đơn giá, số lượng, thành tiền, xóa), ô nhập mã giảm giá, tóm tắt (tạm tính, giảm giá, phí ship ước tính, tổng), nút Thanh toán.
**Quy tắc:** Giỏ lưu theo tài khoản (đã đăng nhập) hoặc session/localStorage (guest) và gộp khi đăng nhập; kiểm tra lại tồn kho và giá khi vào trang; cảnh báo nếu hàng đã hết/đổi giá.

### S05 · Thanh toán
**Thành phần:** Thông tin người nhận (họ tên, SĐT, email), địa chỉ (Tỉnh/Quận/Phường + số nhà), chọn địa chỉ đã lưu, phương thức vận chuyển, phương thức thanh toán (**COD** / **Ví MoMo**), ghi chú, tóm tắt đơn, nút **Đặt hàng**.
**Quy tắc:** Cho phép mua không cần tài khoản; validate SĐT VN; tính phí ship theo địa chỉ + trọng lượng; giữ tồn kho khi tạo đơn; chống đặt trùng (idempotency).

### S06 · Đặt hàng thành công
Mã đơn, tóm tắt, mã QR / nút mở app MoMo nếu đơn chờ thanh toán (tự cập nhật trạng thái), link theo dõi đơn, gợi ý tạo tài khoản (guest).

### S07 · Đăng nhập / Đăng ký / Quên mật khẩu
Email hoặc SĐT + mật khẩu; đăng nhập Google (tùy chọn); xác thực email; đặt lại mật khẩu qua email.

### S08 · Tài khoản
- **Thông tin cá nhân:** sửa tên, SĐT, đổi mật khẩu.
- **Đơn hàng:** danh sách lọc theo trạng thái; chi tiết đơn có timeline; **Hủy đơn** (khi chưa giao), **Yêu cầu đổi/trả** (trong thời hạn), **Mua lại**.
- **Sổ địa chỉ:** thêm/sửa/xóa, đặt mặc định.
- **Yêu thích:** danh sách sản phẩm đã lưu.

### S09 · Tra cứu đơn hàng (guest)
Nhập mã đơn + SĐT → xem trạng thái và timeline.

### S10 · Hướng dẫn chọn size & Trang chính sách / Liên hệ
Bảng size theo thương hiệu, cách đo chân; nội dung tĩnh do admin chỉnh sửa; form liên hệ.

---

## 4. Mô tả chi tiết các trang – Admin

### A01 · Đăng nhập Admin
Email + mật khẩu, giới hạn số lần sai, (khuyến nghị) 2FA cho Admin/Manager.

### A02 · Dashboard
KPI hôm nay/7 ngày/30 ngày: doanh thu, số đơn, đơn chờ xử lý, khách mới; biểu đồ doanh thu theo ngày; top sản phẩm bán chạy; cảnh báo tồn kho thấp; đơn mới nhất.

### A03 · Danh sách sản phẩm
Bảng: ảnh, tên, SKU, danh mục, thương hiệu, giá, tồn kho tổng, trạng thái (Đang bán/Ẩn/Nháp). Tìm kiếm, lọc, thao tác hàng loạt (ẩn/hiện, xóa), nhập/xuất Excel.

### A04 · Thêm/Sửa sản phẩm
**Khối:** Thông tin cơ bản (tên, slug, mô tả, chất liệu, giới tính); phân loại (danh mục, thương hiệu, tag); **ảnh** (nhiều ảnh, gán theo màu, kéo thả sắp xếp); **biến thể** (chọn màu + dải size → sinh bảng SKU: mã SKU, giá, giá so sánh, tồn kho, barcode); giá; SEO (title, meta, slug); trạng thái hiển thị.
**Quy tắc:** SKU duy nhất; không xóa cứng sản phẩm đã có đơn (chỉ ẩn).

### A05 · Danh mục / Thương hiệu / Thuộc tính
CRUD với cây danh mục 2 cấp, logo thương hiệu, danh sách màu (tên + mã hex) và size (hệ EU/US/cm).

### A06 · Kho hàng
Tồn kho theo SKU (có thể tồn / đang giữ / khả dụng), lọc tồn thấp, **phiếu nhập hàng**, **điều chỉnh kho** (kèm lý do), lịch sử biến động (ai, khi nào, lý do, tham chiếu đơn).

### A07 · Danh sách đơn hàng
Tabs theo trạng thái (Chờ xác nhận, Đã xác nhận, Đang đóng gói, Đang giao, Hoàn thành, Đã hủy, Đổi trả); tìm theo mã/SĐT/tên; lọc theo ngày, thanh toán, vận chuyển; in phiếu hàng loạt; xuất Excel.

### A08 · Chi tiết đơn hàng
Thông tin khách & địa chỉ, các dòng hàng, thanh toán, vận chuyển + mã vận đơn, **timeline & ghi chú nội bộ**, nút chuyển trạng thái hợp lệ, in hóa đơn/phiếu giao, hủy đơn (hoàn tồn kho), tạo yêu cầu hoàn tiền.
**Luồng trạng thái:** `Chờ xác nhận → Đã xác nhận → Đang đóng gói → Đang giao → Hoàn thành`; nhánh `Đã hủy` (trước khi giao) và `Đổi trả` (sau khi giao).

### A09 · Đổi trả / Hoàn tiền
Danh sách yêu cầu, lý do, ảnh đính kèm, duyệt/từ chối, nhập lại kho hay hủy hàng lỗi, ghi nhận hoàn tiền.

### A10 · Khách hàng
Danh sách (tên, SĐT, email, số đơn, tổng chi tiêu, ngày tham gia), chi tiết (thông tin, địa chỉ, lịch sử đơn, ghi chú), khóa/mở tài khoản.

### A11 · Marketing
- **Mã giảm giá:** mã, loại (% / số tiền / miễn phí ship), điều kiện (đơn tối thiểu, giới hạn lượt, mỗi khách 1 lần), thời gian, trạng thái.
- **Khuyến mãi theo sản phẩm/danh mục:** giảm giá theo thời gian (flash sale).
- **Banner:** ảnh, link, vị trí, thứ tự, lịch hiển thị.

### A12 · Báo cáo
Doanh thu theo thời gian/kênh/thanh toán; top sản phẩm/danh mục/thương hiệu; tỉ lệ hủy/đổi trả; giá trị tồn kho & hàng tồn lâu. Xuất CSV/Excel.

### A13 · Nội dung & Cài đặt
Soạn trang tĩnh/chính sách; thông tin cửa hàng; phí vận chuyển & vùng giao; cấu hình & bật/tắt thanh toán (COD, MoMo); quản lý nhân viên & vai trò; nhật ký hoạt động (audit log).

---

## 5. Ma trận phân quyền (Admin)

| Chức năng | Admin | Manager | Kho | CSKH |
|---|:-:|:-:|:-:|:-:|
| Dashboard / Báo cáo | ✔ | ✔ | – | – |
| Sản phẩm (xem) | ✔ | ✔ | ✔ | ✔ |
| Sản phẩm (thêm/sửa/xóa) | ✔ | ✔ | – | – |
| Kho (nhập/điều chỉnh) | ✔ | ✔ | ✔ | – |
| Đơn hàng (xem) | ✔ | ✔ | ✔ | ✔ |
| Đơn hàng (đổi trạng thái) | ✔ | ✔ | ✔ (đóng gói/giao) | ✔ (xác nhận/hủy) |
| Đổi trả / hoàn tiền | ✔ | ✔ | ✔ (nhập kho) | ✔ |
| Khách hàng | ✔ | ✔ | – | ✔ |
| Marketing | ✔ | ✔ | – | – |
| Nội dung | ✔ | ✔ | – | – |
| Cài đặt & nhân viên | ✔ | – | – | – |

---

## 6. Quy tắc nghiệp vụ chính

1. **Tồn kho:** `khả dụng = tồn thực tế − đang giữ`. Tạo đơn → tăng "đang giữ"; giao/hoàn thành → trừ tồn thực tế; hủy đơn → trả lại phần giữ.
2. **Giá:** giá bán = giá khuyến mãi đang hiệu lực (nếu có) nếu không thì giá niêm yết; **giá chốt tại thời điểm đặt** và lưu vào dòng đơn (không đổi theo sản phẩm sau này).
3. **Mã giảm giá:** kiểm tra hiệu lực, đơn tối thiểu, giới hạn lượt dùng, tổng cộng không âm; chỉ dùng 1 mã/đơn (v1).
4. **Phí ship:** theo bảng phí theo vùng + ngưỡng miễn phí (ví dụ đơn ≥ 1.000.000đ).
5. **Hủy đơn:** khách tự hủy khi trạng thái ≤ "Đã xác nhận"; sau đó phải qua CSKH.
6. **Đổi trả:** trong 7 ngày kể từ khi nhận, giày chưa qua sử dụng, còn hộp; (cấu hình được ở Cài đặt).
7. **Mã đơn:** định dạng `SH-YYMMDD-XXXX`, duy nhất.
8. **Thanh toán:** COD → thu khi giao; MoMo → chuyển sang MoMo thanh toán, đơn chờ 15 phút; quá hạn hoặc thất bại thì tự hủy / cho thử lại / đổi COD.

---

## 7. Yêu cầu phi chức năng

| Nhóm | Yêu cầu |
|---|---|
| Hiệu năng | Trang danh sách/chi tiết < 2.5s (LCP) trên 4G; ảnh WebP + lazy-load (lưu local khi phát triển, có thể thêm CDN khi deploy) |
| Responsive | Mobile-first (≥ 70% traffic bán giày từ mobile) |
| SEO | SSR/SSG cho trang công khai, slug thân thiện, schema.org Product, sitemap.xml |
| Bảo mật | HTTPS, băm mật khẩu (argon2/bcrypt), chống CSRF/XSS/SQLi, rate-limit đăng nhập, không lưu thông tin thẻ |
| Sẵn sàng | Uptime mục tiêu 99.5%; sao lưu DB hằng ngày |
| Quan sát | Log có cấu trúc, giám sát lỗi, audit log cho thao tác admin |
| Trình duyệt | Chrome, Safari, Edge, Firefox bản mới nhất 2 năm gần đây |

---

## 8. Danh sách mockup đi kèm

Mở `mockup/index.html` để xem toàn bộ. Thanh toán online dùng **MoMo**.

| Mã | Trang | File |
|---|---|---|
| S01 | Trang chủ | `mockup/store/index.html` |
| S02 | Danh sách sản phẩm | `mockup/store/products.html` |
| S02b | Kết quả tìm kiếm | `mockup/store/search.html` |
| S02c | Khuyến mãi / Flash sale | `mockup/store/sale.html` |
| S03 | Chi tiết sản phẩm | `mockup/store/product-detail.html` |
| S04 | Giỏ hàng | `mockup/store/cart.html` |
| S05 | Thanh toán (COD / MoMo) | `mockup/store/checkout.html` |
| S06 | Đặt hàng thành công | `mockup/store/order-success.html` |
| S07 | Đăng nhập | `mockup/store/login.html` |
| S07b | Đăng ký | `mockup/store/register.html` |
| S07c | Quên / đặt lại mật khẩu | `mockup/store/forgot-password.html` |
| S08a | Tài khoản – Thông tin | `mockup/store/account-profile.html` |
| S08b | Tài khoản – Đơn hàng | `mockup/store/account-orders.html` |
| S08c | Tài khoản – Chi tiết đơn | `mockup/store/account-order-detail.html` |
| S08d | Tài khoản – Sổ địa chỉ | `mockup/store/account-addresses.html` |
| S08e | Tài khoản – Yêu thích | `mockup/store/account-wishlist.html` |
| S09 | Tra cứu đơn hàng (guest) | `mockup/store/track-order.html` |
| S10a | Hướng dẫn chọn size | `mockup/store/size-guide.html` |
| S10b | Chính sách | `mockup/store/policy.html` |
| S10c | Liên hệ | `mockup/store/contact.html` |
| — | Trang 404 | `mockup/store/404.html` |
| A01 | Đăng nhập | `mockup/admin/login.html` |
| A02 | Dashboard | `mockup/admin/dashboard.html` |
| A03 | Danh sách sản phẩm | `mockup/admin/products.html` |
| A04 | Thêm / Sửa sản phẩm | `mockup/admin/product-form.html` |
| A05 | Danh mục · Thương hiệu · Thuộc tính | `mockup/admin/catalog.html` |
| A06a | Tồn kho | `mockup/admin/inventory.html` |
| A06b | Phiếu nhập hàng | `mockup/admin/stock-receipt.html` |
| A06c | Lịch sử biến động kho | `mockup/admin/movements.html` |
| A07 | Danh sách đơn hàng | `mockup/admin/orders.html` |
| A08 | Chi tiết đơn hàng | `mockup/admin/order-detail.html` |
| A09 | Đổi trả / hoàn tiền (MoMo) | `mockup/admin/returns.html` |
| A10a | Khách hàng | `mockup/admin/customers.html` |
| A10b | Chi tiết khách hàng | `mockup/admin/customer-detail.html` |
| A11a | Mã giảm giá | `mockup/admin/coupons.html` |
| A11b | Khuyến mãi / Flash sale | `mockup/admin/promotions.html` |
| A11c | Banner | `mockup/admin/banners.html` |
| A12 | Báo cáo | `mockup/admin/reports.html` |
| A13a | Trang nội dung | `mockup/admin/cms.html` |
| A13b | Cài đặt cửa hàng & thanh toán MoMo | `mockup/admin/settings.html` |
| A13c | Cài đặt vận chuyển | `mockup/admin/settings-shipping.html` |
| A13d | Nhân viên & phân quyền | `mockup/admin/staff.html` |
| A13e | Nhật ký hoạt động | `mockup/admin/audit-log.html` |
