<?php
$thongBao = '';
if (isset($_POST['thanhtoan'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $payments = $_POST['payments'];
    $note = $_POST['note'];

    $_SESSION['checkout'] = [
        'name' => $_POST['name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? '',
        'payments' => $_POST['payments'] ?? '',
        'note' => $_POST['note'] ?? '',
    ];

    $_SESSION['checkout_cart'] = [
        'product_id' => $_POST['thanhtoan_product_id'] ?? [],
        'soluong' => $_POST['thanhtoan_soluong'] ?? []
    ];

    //thanh toan vnpay
    if ($payments == "1") {
        // Lấy tổng tiền trong giỏ hàng để truyền sang trang thanh toán
        $sql_select_cart = mysqli_query($mysqli, "SELECT * FROM cart");
        $totalPayment = 0;
        while ($row_usercart = mysqli_fetch_array($sql_select_cart)) {
            $totalMoneyPayment = $row_usercart['product_quantity'] * $row_usercart['product_price'];
            $totalPayment += $totalMoneyPayment;
        }

        // Redirect sang trang thanhtoan_atm.php và truyền tổng tiền
        header("Location: frontend/thanhtoan_momo.php?amount=" . $totalPayment);
        exit();
    } elseif ($payments == "2") {
        // Lấy tổng tiền trong giỏ hàng
        $sql_select_cart = mysqli_query($mysqli, "SELECT * FROM cart");
        $totalPayment = 0;
        while ($row_usercart = mysqli_fetch_array($sql_select_cart)) {
            $totalPayment += $row_usercart['product_quantity'] * $row_usercart['product_price'];
        }

        // Redirect sang thanh toán momo ATM
        header("Location: frontend/thanhtoan_momo_atm.php?amount=" . $totalPayment);
        exit();
    } else {

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
        values ('$name', '$user_code', '$phone', '$email', '$address', '$payments','$note')");

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

            for ($i = 0; $i < count($_POST['thanhtoan_product_id']); $i++) {
                $sanpham_id = $_POST['thanhtoan_product_id'][$i];
                $soluong = $_POST['thanhtoan_soluong'][$i];
                $sql_donhang = mysqli_query($mysqli, "INSERT INTO donhang(product_id , customer_id, soluong ,tongDoanhThu, mahang, ngayDatHang) 
                values ('$sanpham_id', '$khachhang_id', '$soluong', '$totalPayment', '$mahang' , '$ngayDatHang')");
                $sql_giaodich = mysqli_query($mysqli, "INSERT INTO giaodich(khachhang_id, sanpham_id, soluong , magiaodich, ngayThangNam) 
                values ('$khachhang_id','$sanpham_id', '$soluong', '$mahang', '$ngayDatHang')");
                $sql_delete_giohang = mysqli_query($mysqli, "DELETE FROM cart WHERE product_id = '$sanpham_id'");
                $thongBao = 'Đặt hàng thành công';
            }
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
                                $total_product = 0;
                                while ($row_usercart = mysqli_fetch_array($sql_select_cart)) {
                                    $totalMoneyPayment = $row_usercart['product_quantity'] * $row_usercart['product_price'];
                                    $total_product += $row_usercart['product_quantity'];
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
                                    $discountPercent = 0;
                                    $discountAmount = 0;

                                    if ($totalPayment > 10000000) {        
                                        $discountPercent = 30;
                                    } elseif ($totalPayment > 5000000) {    
                                        $discountPercent = 20;
                                    } elseif ($totalPayment > 3000000) {   
                                        $discountPercent = 10;
                                    }

                                    $discountAmount = $totalPayment * $discountPercent / 100;
                                    $totalAfterDiscount = $totalPayment - $discountAmount;
                                ?>
                            </div>
                            <div class="border-top px-2 mx-2"></div>
                            <div class="p-2 d-flex">
                                <div class="col-9">Khuyến mãi (<?= $discountPercent ?>%)</div>
                                <div class="ms-auto text-danger"><?= number_format($discountAmount) ?>đ</div>
                            </div>
                            <div class="p-2 d-flex">
                                <div class="col-9">Phí vận chuyển:</div>
                                <div class="ms-auto">Miễn phí</div>
                            </div>
                            <div class="border-top px-2 mx-2"></div>
                            <div class="p-2 d-flex pt-3 form-control-lg">
                                <div class="col-9"><a class="fw-500">Tổng thanh toán</a></div>
                                <div class="ms-auto"><a class="text-danger fw-500"><?= number_format($totalAfterDiscount) ?>đ</a></div>
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