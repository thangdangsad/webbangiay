<?php
include('../db/connect.php');
?>

<?php
// Duyệt đơn hàng
if (isset($_GET['xac_madonhang'])) {
    $maDonHang = $_GET['xac_madonhang'];
    $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 1
        WHERE mahang = '$maDonHang' ");
}

if (isset($_GET['huy_madonhang'])) {
    $maDonHang = $_GET['huy_madonhang'];
    $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 2
        WHERE mahang = '$maDonHang' ");
}

if (isset($_GET['huy_xacnhan_madonhang'])) {
    $maDonHang = $_GET['huy_xacnhan_madonhang'];
    $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 0
        WHERE mahang = '$maDonHang' ");
}

if (isset($_GET['huy_dahuy_madonhang'])) {
    $maDonHang = $_GET['huy_dahuy_madonhang'];
    $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 0
        WHERE mahang = '$maDonHang' ");
}
?>

<?php
include('../db/connect.php');
session_start();
include('./common/checkLogin.php')
?>

<?php
if (isset($_GET['xoadonhang'])) {
    // $id_customer_xoa = $_GET['xoadonhang'];
    // $sql_xoa = mysqli_query($mysqli, "DELETE FROM donhang WHERE customer_id = '$id_customer_xoa'");
    // header('Location:donhang.php');
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
include('./common/head.php')
?>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- sidebar -->
        <?php include('./common/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include('./common/topbar.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <a href="index.php">Dashboard</a><span class="text-dark"> / Quản lí đơn hàng</span>
                            </h6>
                        </div>
                    </div>
                    <div class="row">
                        <?php
                        if (isset($_GET['quanli']) == 'xemdonhang') {
                            $makhachhang = $_GET['makh'];
                            $sql_chitiet = mysqli_query($mysqli, "SELECT * FROM donhang, product ,customer
                                WHERE donhang.product_id = product.product_id 
                                AND customer.customer_id = donhang.customer_id
                                AND  donhang.customer_id='$makhachhang'");

                            // Thông tin khách hàng + đơn
                            $sql_thongtin = mysqli_query($mysqli, "SELECT * FROM donhang, product ,customer
                                WHERE donhang.product_id = product.product_id 
                                AND customer.customer_id = donhang.customer_id
                                AND  donhang.customer_id='$makhachhang' LIMIT 1");

                            while ($row_thongtin = mysqli_fetch_array($sql_thongtin)) {
                        ?>
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="?donhang.php" class="mr-auto">Trở lại</a>
                                        <h4 class="py-1 mr-auto">Chi tiết đơn hàng</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Họ tên</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0"><?php echo $row_thongtin['customer_name'] ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Số điện thoại</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0"><?php echo $row_thongtin['customer_phone'] ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Địa chỉ</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0"><?php echo $row_thongtin['customer_address'] ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Ngày đặt hàng</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0"><?php echo $row_thongtin['ngayDatHang'] ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold text-danger">Ghi chú </p>
                                                        </div>
                                                        <div class="col-sm-8">
                                                            <p class="mb-0 font-weight-bold"><?php echo $row_thongtin['customer_note'] ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Mã đơn</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0"><?php echo $row_thongtin['mahang'] ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Tổng tiền hàng</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0"><?php echo number_format($row_thongtin['tongDoanhThu']) . ",đ" ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Hình Thức vận chuyển</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0">
                                                                <?php
                                                                if ($row_thongtin['payments'] == 0) {
                                                                    echo "Thanh toán khi nhận hàng";
                                                                } elseif ($row_thongtin['payments'] == 1) {
                                                                    echo "Thanh toán MOMO";
                                                                } elseif ($row_thongtin['payments'] == 2) {
                                                                    echo "Thanh toán MOMO ATM";
                                                                } else {
                                                                    echo "Chưa xác định";
                                                                }
                                                                ?>
                                                            </p>
                                                        </div>

                                                    </div>

                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Phí vận chuyển</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0">Miễn phí vận chuyển</p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <p class="mb-0 font-weight-bold">Thành tiền</p>
                                                        </div>
                                                        <div class="col-sm-7">
                                                            <p class="mb-0 font-weight-bold h5 text-danger"><?php echo number_format($row_thongtin['tongDoanhThu']) . ",đ" ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php } ?>

                                <div class="d-flex justify-content-center align-items-center">
                                    <h4 class="py-1 text-center">Sản phẩm</h4>
                                </div>
                                <table class="table table-bordered">
                                    <tr class="text-center">
                                        <th>Thứ tự</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Size</th> <!-- ✅ thêm cột Size -->
                                        <th>Số lượng</th>
                                        <th>Giá</th>
                                        <th>Tổng tiền</th>
                                    </tr>
                                    <?php
                                    $i = 0;
                                    while ($row_chitiet = mysqli_fetch_array($sql_chitiet)) {
                                        $i++;
                                    ?>
                                        <tr class="text-center">
                                            <td><?php echo $i ?></td>
                                            <td><img src="../images/<?php echo $row_chitiet['product_image'] ?> " alt="image" height="50px"></td>
                                            <td><?php echo $row_chitiet['product_name'] ?></td>
                                            <td><?php echo $row_chitiet['size'] ?></td> <!-- ✅ hiển thị size -->
                                            <td><?php echo $row_chitiet['soluong'] ?></td>
                                            <td><?php echo number_format($row_chitiet['product_discount']) ?></td>
                                            <td><?php echo number_format($row_chitiet['soluong'] * $row_chitiet['product_discount']) ?></td>
                                        </tr>
                                    <?php } ?>
                                </table>
                                <div class="d-flex justify-content-center align-items-center m">
                                    <a href="?donhang.php" class="mr-auto">Trở lại</a>
                                </div>
                                </div>

                            <?php
                        } else {
                            ?>
                                <!-- Xem danh sách đơn hàng -->
                                <div class="col-md-12">
                                    <h4 class="py-1 text-center">Danh sách đơn hàng</h4>
                                    <table class="table table-bordered">
                                        <?php
                                        // paging
                                        $productsPerPage = 5;
                                        $currentPage = (isset($_GET['page']) && is_numeric($_GET['page'])) ? max(1, $_GET['page']) : 1;
                                        $begin = ($currentPage - 1) * $productsPerPage;

                                        $sql_select_donhang = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang 
                                            WHERE donhang.product_id = product.product_id 
                                            AND donhang.customer_id = customer.customer_id  
                                            GROUP BY donhang.mahang
                                            ORDER BY donhang.order_id DESC LIMIT $begin, $productsPerPage");
                                        ?>
                                        <tr class="text-center">
                                            <th>Thứ tự</th>
                                            <th>Mã đơn</th>
                                            <th>Tên khách hàng</th>
                                            <th>Doanh thu</th>
                                            <th>Ngày đặt</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                        <?php
                                        $i = 0;
                                        while ($row_donhang = mysqli_fetch_array($sql_select_donhang)) {
                                            $i++;
                                            $orderNumber = $begin + $i;
                                        ?>
                                            <tr class="text-center">
                                                <td><?php echo $orderNumber ?></td>
                                                <td><?php echo $row_donhang['mahang'] ?></td>
                                                <td><?php echo $row_donhang['customer_name'] ?></td>
                                                <td class="text-danger font-weight-bold"><?php echo number_format($row_donhang['tongDoanhThu']) . ",đ" ?></td>
                                                <td><?php echo $row_donhang['ngayDatHang'] ?></td>
                                                <td>
                                                    <?php
                                                    $mahang = $row_donhang['mahang'];
                                                    if ($row_donhang['trangThai'] == 0) {
                                                    ?>
                                                        <a href="?donhang.php&xac_madonhang=<?php echo $mahang ?>" class="btn btn-warning btn-sm">Xác nhận</a>
                                                        <a href="?donhang.php&huy_madonhang=<?php echo $mahang ?>" class="btn btn-secondary btn-sm">Hủy đơn</a>
                                                    <?php } elseif ($row_donhang['trangThai'] == 1) { ?>
                                                        <a href="?donhang.php&huy_xacnhan_madonhang=<?php echo $mahang ?>" class="btn btn-success btn-sm">Đã xác nhận</a>
                                                    <?php } elseif ($row_donhang['trangThai'] == 2) { ?>
                                                        <a href="?donhang.php&huy_dahuy_madonhang=<?php echo $mahang ?>" class="btn btn-secondary btn-sm">Đã hủy</a>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="?quanli=xemdonhang&makh=<?php echo $row_donhang['customer_id'] ?>" class="btn btn-success">Xem</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </table>

                                    <?php
                                    $sql_page = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang 
                                        WHERE donhang.product_id = product.product_id 
                                        AND donhang.customer_id = customer.customer_id  
                                        GROUP BY donhang.mahang
                                        ORDER BY donhang.order_id");
                                    $count_row_page = mysqli_num_rows($sql_page);
                                    $page = ceil($count_row_page / 5);
                                    ?>
                                    <style>
                                        .paging a {
                                            border: 1px solid #4e73df;
                                            padding: 5px 13px;
                                            list-style: none;
                                            background: #fff;
                                            margin: 0 4px;
                                            border-radius: 4px;
                                            color: #4e73df;
                                            text-decoration: none;
                                        }
                                    </style>

                                    <div class="paging d-flex justify-content-center">
                                        <?php for ($i = 1; $i <= $page; $i++) { ?>
                                            <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="donhang.php?page=<?php echo $i ?>"><?php echo $i ?></a>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                    </div>
                </div>
            </div>
            <!-- End of Main Content -->

            <?php include('./common/footer.php') ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <!-- Logout Modal-->
    <?php include('./common/modal.php') ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>