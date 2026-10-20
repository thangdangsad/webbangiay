<?php
include('../db/connect.php');
?>

<?php
// Duyệt đơn hàng
    if(isset($_GET['xac_madonhang'])) {
        $maDonHang = $_GET['xac_madonhang'];
        
        $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 1
        WHERE mahang = '$maDonHang' ");

    }

    if(isset($_GET['huy_madonhang'])) { 
        $maDonHang = $_GET['huy_madonhang'];
        
        $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 2
        WHERE mahang = '$maDonHang' ");

    }

    
    if(isset($_GET['huy_xacnhan_madonhang'])) { 
        $maDonHang = $_GET['huy_xacnhan_madonhang'];
        
        $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 0
        WHERE mahang = '$maDonHang' ");

    }

    if(isset($_GET['huy_dahuy_madonhang'])) { 
        $maDonHang = $_GET['huy_dahuy_madonhang'];
        
        $sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 0
        WHERE mahang = '$maDonHang' ");

    }


?>



<?php
include('../db/connect.php');
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
        <?php
        include('./common/sidebar.php');
        ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php
                include('./common/topbar.php');
                ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <a href="index.php">Dashboard</a> 
                                <span class="text-dark">  / </span>
                                <a href="donhang.php"> Đơn hàng</a>
                                <span class="text-dark">  / Đơn hàng chờ xác nhận</span>
                            </h6>
                        </div>

                    </div>
                    <div class="row">
                        <?php

                        if (isset($_GET['quanli']) == 'xemdonhang') {
                            $makhachhang = $_GET['makh'];
                            $sql_chitiet = mysqli_query($mysqli, "SELECT * FROM donhang, product ,customer
                WHERE donhang.product_id = product.product_id AND customer.customer_id = donhang.customer_id
                AND  donhang.customer_id='$makhachhang'");

                        ?>
                            <!-- Xem chi tiết đơn hàng -->
                            <div class="col-md-12">
                                <div class="d-flex justify-content-center align-items-center">
                                    <a href="?donhang.php" class="mr-auto">Trở lại</a>
                                    <h4 class="py-1 mr-auto">Chi tiết đơn hàng</h4>
                                </div>
                                <table class="table table-bordered">

                                    <tr class="text-center">
                                        <th>Thứ tự</th>
                                        <th>Sản phẩm</th>
                                        <th>Tên sản phẩm</th>
                                        
                                        <th>Số lượng</th>
                                        <th>Giá</th>
                                        <th>Tổng tiền</th>
                                        <th>Ngày đặt</th>
                                        <th>Ghi chú</th>
                                        <!-- <th>Thao tác</th> -->

                                    </tr>
                                    <?php
                                    $i = 0;
                                    while ($row_chitiet = mysqli_fetch_array($sql_chitiet)) {
                                        $i++;
                                    ?>
                                        <tr class="text-center">    
                                            <td><?php echo $i ?></td> 
                                            <td>
                                                <img src="../images/<?php echo $row_chitiet['product_image'] ?> " alt="image" height="50px">
                                                
                                            </td>                          
                                            <td><?php echo $row_chitiet['product_name'] ?></td>
                                            
                                            <td><?php echo $row_chitiet['soluong'] ?></td>
                                            <td><?php echo number_format($row_chitiet['product_discount']) ?></td>
                                            <td><?php echo number_format($row_chitiet['soluong'] * $row_chitiet['product_discount']) ?></td>
                                            <td><?php echo $row_chitiet['ngayDatHang'] ?></td>
                                            <td><?php echo $row_chitiet['customer_note'] ?></td>

                                        </tr>
                                    <?php
                                    }
                                    ?>


                                </table>
                            </div>


                        <?php
                        } else {
                        ?>
                            <!-- Xem danh sách đơn hàng -->
                            <div class="col-md-12">
                                <h4 class="py-1 text-center">Đơn hàng chờ xác nhận</h4>
                                <table class="table table-bordered">
                                    <?php
                                    // paging
                                    $productsPerPage = 5;
                                    if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                                        $currentPage = max(1, $_GET['page']);
                                    } else {
                                        $currentPage = 1;
                                    }
                                    $begin = ($currentPage - 1) * $productsPerPage;

                                    $sql_select_donhang = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang 
                        WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id  
                        AND donhang.trangThai = 0 GROUP BY donhang.mahang
                        ORDER BY donhang.order_id DESC LIMIT $begin, $productsPerPage");
                                    ?>
                                    <tr class="text-center">
                                        <th>Thứ tự</th>
                                        <th>Mã đơn</th>
                                        <th>Tên khách hàng</th>
                                        <th>Ngày đặt</th>
                                        <th>Trang thái</th>
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
                                            <td><?php echo $row_donhang['ngayDatHang'] ?></td>
                                            <td>                                            
                                                    
                                                    <?php
                                                        $mahang = $row_donhang['mahang'];
                                                        if($row_donhang['trangThai'] == 0) {
                                                    ?> 
                                                        <a href="?donhang.php&xac_madonhang=<?php echo $mahang ?>" onclick=" return confirm('Bạn có muốn tiếp tục xác nhận đơn hàng ?\nChọn OK để xác nhận') "
                                                            data-target="#confirmOrder" class="btn btn-warning btn-sm">Xác nhận</a>
                                                        <a href="?donhang.php&huy_madonhang=<?php echo $mahang ?>" onclick=" return confirm('Bạn có muốn hủy đơn hàng ?\nChọn OK để xác nhận') "
                                                            data-target="#confirmOrder" class="btn btn-secondary btn-sm">Hủy đơn</a>    
                                                    <?php
                                                        }elseif($row_donhang['trangThai'] == 1) { 
                                                    ?>  
                                                        <a href="?donhang.php&huy_xacnhan_madonhang=<?php echo $mahang ?>"  onclick=" return confirm('Bạn có muốn hủy xác nhận đơn hàng ?\nChọn OK để xác nhận') "
                                                        class="btn btn-success btn-sm" >Đã xác nhận</a>
                                                    <?php
                                                        }elseif($row_donhang['trangThai'] == 2) { 
                                                    ?>  
                                                        <a href="?donhang.php&huy_dahuy_madonhang=<?php echo $mahang ?>" onclick=" return confirm('Bạn có muốn bỏ hủy đơn hàng ?\nChọn OK để xác nhận') "
                                                        class="btn btn-secondary btn-sm" >Đã hủy</a>
                                                    <?php
                                                        }
                                                    ?>  
                                            </td>
                                            <td>
                                                
                                                <a href="?quanli=xemdonhang&makh=<?php echo $row_donhang['customer_id'] ?>" class="btn btn-success">Xem</a>
                                            </td>
                                        </tr>
                                        
                                    <?php
                                    }
                                    ?>

                                </table>

                                <?php
                                $sql_page = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang 
                    WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id  GROUP BY donhang.mahang
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
                                    <?php
                                    for ($i = 1; $i <= $page; $i++) {
                                    ?>
                                        <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="unconfirmed_order.php?page=<?php echo $i ?>"><?php echo $i ?></a>
                                    <?php
                                    }
                                    ?>

                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>

                </div>
                <!-- /.container-fluid -->



            </div>
            <!-- End of Main Content -->

            <?php
            include('./common/footer.php')
            ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    

    


    <!-- Logout Modal logout-->
    <?php
        include('./common/modal.php')
    ?>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>