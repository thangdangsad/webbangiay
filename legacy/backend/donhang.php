<?php
include('../db/connect.php');
?>
<!-- <?php
        if (isset($_GET['xoadonhang'])) {
            $id_customer_xoa = $_GET['xoadonhang'];
            $sql_xoa = mysqli_query($mysqli, "DELETE FROM donhang WHERE customer_id = '$id_customer_xoa'");
            header('Location:donhang.php');
        }
        ?> -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng</title>
    <link href="../css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

    <div class="d-flex justify-content-center align-content-center bg-light fixed-top">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <h2>
                    <a href="../index.php" class="btn btn-success btn-rounded mr-2">
                        Trở lại shop
                    </a>
                </h2>
                <h2><a class="navbar-brand" href="#">Dashboard</a></h2>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link  text-danger font-weight-bold" aria-current="page" href="donhang.php">Đơn hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="xulydanhmuc.php?quanli=danhmuc">Danh mục</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" href="xulysanpham.php">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customer.php">Khách hàng</a>
                        </li>
                    </ul>
                </div>

            </div>
        </nav>
    </div><br>
    <div class="container mt-5">

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
                            <th>Tên khách hàng</th>
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
                                <td><?php echo $row_chitiet['customer_name'] ?></td>
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
                    <h4 class="py-1 text-center">Danh sách đơn hàng</h4>
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
                        WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id  GROUP BY donhang.mahang
                        ORDER BY donhang.order_id LIMIT $begin, $productsPerPage");
                        ?>
                        <tr class="text-center">
                            <th>Thứ tự</th>
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
                                <td><?php echo $row_donhang['customer_name'] ?></td>
                                <td><?php echo $row_donhang['ngayDatHang'] ?></td>
                                <td>Đã duyệt</td>
                                <td>
                                    <!-- echo ra id để xóa và cập nhật -->
                                    <a href="?xoadonhang=<?php echo $row_donhang['customer_id'] ?>" class="btn btn-danger">Xóa</a>
                                    <a href="?suadonhang" class="btn btn-warning">Sửa</a>
                                    <a href="?quanli=xemdonhang&makh=<?php echo $row_donhang['customer_id'] ?>" class="btn btn-primary">Xem</a>
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
                            <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="donhang.php?page=<?php echo $i ?>"><?php echo $i ?></a>
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
    <!-- Modal -->

</body>

</html>