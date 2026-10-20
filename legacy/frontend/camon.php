<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$checkoutData = $_SESSION['checkout'] ?? [];

$name = $checkoutData['name'] ?? '';
$phone = $checkoutData['phone'] ?? '';
$email = $checkoutData['email'] ?? '';
$address = $checkoutData['address'] ?? '';
$note = $checkoutData['note'] ?? '';

$checkout_cart = $_SESSION['checkout_cart'] ?? [];
$product_ids = $checkout_cart['product_id'] ?? [];
$soluongs = $checkout_cart['soluong'] ?? [];

if (isset($_GET['vnp_Amount'])) {
    // -------------------- VNPAY --------------------
    $vnp_Amount       = $_GET['vnp_Amount'];
    $vnp_BankCode     = $_GET['vnp_BankCode'];
    $vnp_BankTranNo   = $_GET['vnp_BankTranNo'];
    $vnp_OrderInfo    = $_GET['vnp_OrderInfo'];
    $vnp_PayDate      = $_GET['vnp_PayDate'];
    $vnp_TmnCode      = $_GET['vnp_TmnCode'];
    $vnp_TransactionNo= $_GET['vnp_TransactionNo'];
    $vnp_CardType     = $_GET['vnp_CardType'];
    $mahang           = rand(1000,9999);

    // Insert đơn hàng
    foreach($_SESSION['cart'] as $item){
        $product_id = $item['id'];
        $soluong    = $item['soluong'];
        $tongtien   = $item['soluong'] * $item['gia'];
        $insert_order = "INSERT INTO donhang(product_id, customer_id, mahang, soluong, tongDoanhThu, trangThai, ngayDatHang, payments)
            VALUES('$product_id','$id_khachhang','$mahang','$soluong','$tongtien','1','$now','1')";
        mysqli_query($mysqli, $insert_order);
    }

    // Insert giao dịch
    $insert_gd = "INSERT INTO giaodich(khachhang_id, sanpham_id, soluong, magiaodich, ngayThangNam)
                  VALUES('$id_khachhang','0','0','$mahang',NOW())";
    mysqli_query($mysqli, $insert_gd);

    echo '<h3>Thanh toán VNPAY thành công</h3>';
    echo '<p>Xem lịch sử đơn hàng trong trang cá nhân</p>';
    unset($_SESSION['cart']);

} elseif (isset($_GET['partnerCode'])) {
    // -------------------- MOMO --------------------
    $partnerCode = $_GET['partnerCode'];
    $orderId     = $_GET['orderId'];
    $amount      = $_GET['amount'];
    $orderInfo   = $_GET['orderInfo'];
    $orderType   = $_GET['orderType'];
    $transId     = $_GET['transId'];
    $payType     = $_GET['payType'];

    $mahang = $orderId;

    // Insert MOMO log
    $insert_momo = "INSERT INTO tbl_momo(partner_Code, order_Id, amount, order_Info, order_Type, trans_Id, pay_type, code_cart)
                    VALUES('$partnerCode','$orderId','$amount','$orderInfo','$orderType','$transId','$payType','$orderId')";
    mysqli_query($mysqli, $insert_momo);

    // Insert đơn hàng


      if (isset($_SESSION['login_id'])) {
            $user_id = $_SESSION['login_id'];
            if (isset($_SESSION['user_code']) && ($_SESSION['user_code'] == 0)) {
                $user_code = rand(1000, 9999);
            } elseif (isset($_SESSION['user_code']) && ($_SESSION['user_code'] != 0)) {
                $user_code = $_SESSION['user_code'];
            }
        } else {
            $user_id = '';
            $user_code = '';
        }

        $sql_khachhang = mysqli_query($mysqli, "INSERT INTO customer(customer_name, user_code ,customer_phone,customer_email , customer_address, payments, customer_note) 
        values ('$name', '$user_code', '$phone', '$email', '$address', '2','$note')");

        $sql_quanli_user = mysqli_query($mysqli, "UPDATE quanli_user SET user_code = '$user_code' 
        WHERE user_id = '$user_id'");

        $sql_select_cart = mysqli_query($mysqli, "SELECT * FROM cart ORDER BY cart_id ASC ");
        $totalPayment = 0;
        while ($row_usercart = mysqli_fetch_array($sql_select_cart)) {
            $totalMoneyPayment = $row_usercart['product_quantity'] * $row_usercart['product_price'];
            $totalPayment += $totalMoneyPayment;
        }

        if ($sql_khachhang && $sql_quanli_user) {
            $sql_select = mysqli_query($mysqli, "SELECT * FROM customer ORDER BY customer_id DESC LIMIT 1");
            $row_khachhang = mysqli_fetch_array($sql_select);
            $ngayDatHang = date('Y-m-d');
            $khachhang_id = $row_khachhang['customer_id'];
            $mahang = rand(1000, 9999);

            for ($i = 0; $i < count($product_ids); $i++) {
                $sanpham_id = $product_ids[$i];    
                $soluong = $soluongs[$i];        

                $sql_donhang = mysqli_query($mysqli, "INSERT INTO donhang(product_id, customer_id, soluong, tongDoanhThu, mahang, ngayDatHang, payments) 
                VALUES ('$sanpham_id', '$khachhang_id', '$soluong', '$totalPayment', '$mahang', '$ngayDatHang', '2')");

                $sql_giaodich = mysqli_query($mysqli, "INSERT INTO giaodich(khachhang_id, sanpham_id, soluong, magiaodich, ngayThangNam) 
                VALUES ('$khachhang_id', '$sanpham_id', '$soluong', '$mahang', '$ngayDatHang')");


                $sql_delete_giohang = mysqli_query($mysqli, "DELETE FROM cart WHERE product_id = '$sanpham_id'");
            }

// Tạo thông báo thành công
    $thongBao = 'Đặt hàng thành công';

// (Nếu muốn, có thể xóa session checkout_cart sau khi thanh toán)
    unset($_SESSION['checkout_cart']);
            // --- Bắt đầu phần tạo voucher ---
            // $totalItems = array_sum($_POST['thanhtoan_soluong']); // tổng số sản phẩm khách mua

            // if ($totalItems >= 5) { // điều kiện tạo voucher
            //     $voucher_code = "SALE" . rand(1000, 9999); // mã voucher ngẫu nhiên
            //     $discount = 100000; // giảm 100k
            //     $expiry_date = date('Y-m-d', strtotime('+30 days')); // hết hạn 30 ngày
            //     $sql_voucher = mysqli_query($mysqli, "INSERT INTO vouchers(customer_id, voucher_code, discount, expiry_date) 
            //                              VALUES('$khachhang_id', '$voucher_code', '$discount', '$expiry_date')");
            // }
            // --- Kết thúc phần tạo voucher ---


            if (isset($_SESSION['login_id'])) {
                $sql_select_user_code = mysqli_query($mysqli, "SELECT user_code FROM quanli_user WHERE user_id = '$user_id'");
                $row_user_code = mysqli_fetch_array($sql_select_user_code);
                $latest_user_code = $row_user_code['user_code'];
                $_SESSION['user_code'] = $latest_user_code;
            }
        } else {
            $thongBao = 'Đặt hàng không thành công';
        }

    
    unset($_SESSION['cart']);


} else {
    // -------------------- PAYPAL --------------------
    if (isset($_GET['thanhtoan']) && $_GET['thanhtoan'] == 'paypal') {
        $mahang = rand(1000,9999);

        foreach($_SESSION['cart'] as $item){
            $product_id = $item['id'];
            $soluong    = $item['soluong'];
            $tongtien   = $item['soluong'] * $item['gia'];
            $insert_order = "INSERT INTO donhang(product_id, customer_id, mahang, soluong, tongDoanhThu, trangThai, ngayDatHang, payments)
                VALUES('$product_id','$id_khachhang','$mahang','$soluong','$tongtien','1','$now','3')";
            mysqli_query($mysqli, $insert_order);
        }

        $insert_gd = "INSERT INTO giaodich(khachhang_id, sanpham_id, soluong, magiaodich, ngayThangNam)
                      VALUES('$id_khachhang','0','0','$mahang',NOW())";
        mysqli_query($mysqli, $insert_gd);

        echo '<h3>Thanh toán Paypal thành công</h3>';
        unset($_SESSION['cart']);
    }
}
?>

<style>
    .f-family {
        font-family: 'Roboto', sans-serif;
    }

    .product-list-payment {
        max-height: 200px;
        overflow-y: auto;
    }

    .fw-500 {
        font-weight: 500;
    }
</style>
<section style="background-color: #eee;">

    <div class="container py-5">
        <div class="row">
            <div class="col">
                <nav aria-label="breadcrumb" class="bg-light rounded-3 p-3 mb-4">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Thanh toán</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="card">
            <?php
            $id_user = $_SESSION['login_id'];
            $sql_select_user = mysqli_query($mysqli, "SELECT * FROM quanli_user WHERE user_id = '$id_user' ");
            $row_user = mysqli_fetch_array($sql_select_user);
            ?>
            <div class="card-body">
                <div class="row d-flex justify-content-center pb-5">
                    <div class="col-md-6 col-xl-5 mb-4 mb-md-0">
                        <div class="py-4 d-flex flex-row">
                            <h5><span class="far fa-check-square pe-2"></span> <b>Thanh toán</b> </h5>
                        </div>

                        <p class="text-left">
                            <span class="text-danger">Tên: </span><span class="text-dark"><?php echo $row_user['user_name'] ?></span><br>
                            <span class="text-danger"> Địa chỉ:</span><span class="text-dark"> <?php echo $row_user['user_address'] ?></span><br>
                            <span class="text-danger">Phone: </span><span class="text-dark"><?php echo $row_user['user_phone'] ?></span>
                        </p>
                        <p class="text-left"><i class="fas fa-plus-circle text-primary pe-1"></i>
                            <a href="index.php?quanli=suathongtin">Thay đổi</a>
                        </p>

                        <hr />
                        <div class="pt-2">
                            <form action="" method="post" class="pb-3">
                                <input type="hidden" name="name" value="<?php echo $row_user['user_name'] ?>">
                                <input type="hidden" name="phone" value="<?php echo $row_user['user_phone'] ?>">
                                <input type="hidden" name="email" value="<?php echo $row_user['user_email'] ?>">
                                <input type="hidden" name="address" value="<?php echo $row_user['user_address'] ?>">
                                <div class="controls form-group">
                                    <select class="form-control rounded" name="payments">
                                        <option>Chọn loại hình thức thanh toán</option>
                                        <option value="2">Thanh toán MOMO ATM</option>
                                        <option value="1">Thanh toán MOMO</option>
                                        <option value="0">Thanh toán khi nhận hàng</option>
                                    </select>
                                </div>
                                <div class="input-group my-3">
                                    <input type="text" name="note" class="form-control" placeholder="Lưu ý người bán">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon1">Ghi chú</span>
                                    </div>
                                </div>
                                <?php
                                $sql_lay_giohang = mysqli_query($mysqli, "SELECT cart.*, product.size FROM cart 
                                                                            JOIN product ON cart.product_id = product.product_id 
                                                                            ORDER BY cart.cart_id DESC");
                                while ($row_thanhtoan = mysqli_fetch_array($sql_lay_giohang)) {
                                ?>
                                    <input type="hidden" name="thanhtoan_soluong[]" value="<?php echo $row_thanhtoan['product_quantity'] ?>">
                                    <input type="hidden" name="thanhtoan_product_id[]" value="<?php echo $row_thanhtoan['product_id'] ?>">
                                <?php
                                }
                                ?>
                                <?php
                                if ($thongBao != '') {
                                    echo '
                                            <div class="alert alert-success p-2"> 
                                                ' . $thongBao . '
                                            </div> 
                                        ';
                                }
                                ?>
                                <?php
                                if ($thongBao != '') {
                                ?>
                                    <a href="index.php?quanli=dondamua" class="btn btn-danger">Đến trang đơn đã mua</a>
                                <?php
                                } else {
                                ?>
                                    <input type="submit" onclick="return confirm('Bạn có chắc chắn xác nhận đặt hàng không ?');" name="thanhtoan" value="Hoàn tất thanh toán" class="btn btn-primary btn-block btn-lg" />
                                <?php
                                }
                                ?>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-6 offset-xl-1 rounded">
                        <div class="py-4 d-flex justify-content-end">
                            <h6 class="f-family">
                                <a href="index.php?quanli=giohang">Trở về giỏ hàng</a>
                            </h6>
                        </div>
                        <?php
                        $sql_select_cart = mysqli_query($mysqli, "SELECT cart.*, product.size FROM cart 
                                                                     JOIN product ON cart.product_id = product.product_id 
                                                                     ORDER BY cart.cart_id ASC ");
                        ?>
                        <div class="rounded d-flex flex-column p-2" style="background-color: #f8f9fa;">
                            <div class="p-2 me-3">
                                <h4 class="ml-3 f-family">Sản phẩm</h4>
                            </div>
                            <div class="product-list-payment">
                                <?php
                                $totalPayment = 0;
                                while ($row_usercart = mysqli_fetch_array($sql_select_cart)) {
                                    $totalMoneyPayment = $row_usercart['product_quantity'] * $row_usercart['product_price'];
                                    $totalPayment += $totalMoneyPayment;
                                ?>
                                    <div class="p-2 d-flex">
                                        <div class="col-9">
                                            <span class="text-danger"><?php echo "x" . $row_usercart['product_quantity'] ?></span>
                                            <?php echo $row_usercart['product_name'] ?> - Size <?php echo $row_usercart['size'] ?>
                                        </div>
                                        <div class="ms-auto text-danger fw-500"><?php echo number_format($row_usercart['product_price'] * $row_usercart['product_quantity']) . ".đ" ?></div>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                            <div class="border-top px-2 mx-2"></div>
                            <div class="p-2 d-flex">
                                <div class="col-9">Khuyến mãi(0)</div>
                                <div class="ms-auto">0đ</div>
                            </div>
                            <div class="p-2 d-flex">
                                <div class="col-9">Phí vận chuyển:</div>
                                <div class="ms-auto">Miễn phí</div>
                            </div>
                            <div class="border-top px-2 mx-2"></div>
                            <div class="p-2 d-flex pt-3 form-control-lg">
                                <div class="col-9"><a class="fw-500">Tổng thanh toán</a></div>
                                <div class="ms-auto"><a class="text-danger fw-500"><?php echo number_format($totalPayment) . ".đ" ?></a></div>
                            </div>
                        </div>
                        <div class="py-4 d-flex justify-content-end">
                            <h6 class="f-family"><a href="index.php?quanli=giohang">Trở về giỏ hàng</a></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>