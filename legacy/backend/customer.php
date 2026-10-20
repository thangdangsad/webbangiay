<?php
include('../db/connect.php');
?>
<!-- <?php
if (isset($_GET['xoakhang'])) {
    $id_customer_xoa = $_GET['xoakhang'];
    $sql_delete_customer = mysqli_query($mysqli, "DELETE FROM customer WHERE customer_id = '$id_customer_xoa'");
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
                <h2><a class="navbar-brand" href="#">Dashboard</a></h2>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link " aria-current="page" href="donhang.php">Đơn hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="xulydanhmuc.php?quanli=danhmuc">Danh mục</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled"  href="xulysanpham.php">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger font-weight-bold" href="customer.php">Khách hàng</a>
                        </li>
                    </ul>
                </div>
                
            </div>
        </nav>
    </div><br>

    <div class="container mt-5">

        <div class="row">
                <?php
                    if (isset($_GET['quanli'])=='xemlichsu') {
                        $maGiaoDich = $_GET['magiaodich'];
                        $sql_lichSuMuaHang = mysqli_query($mysqli, "SELECT * FROM giaodich, customer, product
                        WHERE giaodich.sanpham_id = product.product_id
                        AND  customer.customer_id = giaodich.khachhang_id
                        AND giaodich.magiaodich = '$maGiaoDich' ORDER BY giaodich.giaodich_id DESC");
                ?>
                    <!-- Xem lịch sử mua hàng -->
                    <div class="col-md-12">
                    <div class="d-flex justify-content-center align-items-center">
                        <a href="?customer.php" class="mr-auto">Trở lại</a>
                        <h4 class="py-1 mr-auto">Lịch sử mua hàng</h4>
                    </div>
                    <table class="table table-bordered">
                        
                        <tr class="text-center">
                            <th>ID</th>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Ngày đặt</th>
                            <th>Giá</th>
                            <th>Tổng tiền</th>
                            <!-- <th>Thao tác</th> -->

                        </tr>
                        <?php
                        $i = 0;
                        while ($row_chitiet = mysqli_fetch_array($sql_lichSuMuaHang)) {
                            $i++;
                        ?>
                            <tr class="text-center">
                                <td><?php echo $i ?></td>
                                <td><?php echo $row_chitiet['product_name'] ?></td>  
                                <td><?php echo $row_chitiet['soluong'] ?></td>
                                <td><?php echo $row_chitiet['ngayThangNam'] ?></td>
                                <td><?php echo number_format($row_chitiet['product_discount']) ?></td>
                                <td><?php echo number_format($row_chitiet['product_discount'] * $row_chitiet['soluong']) ?></td>
                                
                            </tr>
                        <?php
                        }
                        ?>


                    </table>
                </div>
                <?php
                } else {
                ?>
            
                <!-- Xem danh sách khách hàng -->
                <div class="col-md-12">
                    <h4 class="py-1 text-center">Danh sách khách hàng</h4>
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

                        $sql_select_customer = mysqli_query($mysqli, "SELECT * FROM customer, giaodich 
                        WHERE customer.customer_id = giaodich.khachhang_id
                        GROUP BY giaodich.magiaodich
                        ORDER BY customer.customer_id DESC LIMIT $begin, $productsPerPage");
                        ?>
                        <tr class="text-center">
                            <th>Thứ tự</th>
                            <th>Tên khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Email</th>
                            <th>Thao tác</th>
                        </tr>
                        <?php
                        $i = 0;
                        while ($row_customer = mysqli_fetch_array($sql_select_customer)) {
                            $i++;
                            // Thứ tự sản phẩm = $begin + $i
                            $orderNumber = $begin + $i;
                        ?>
                            <tr class="text-center">
                                <td><?php echo $orderNumber ?></td>
                                <td><?php echo $row_customer['customer_name'] ?></td>
                                <td><?php echo $row_customer['customer_phone'] ?></td>
                                <td><?php echo $row_customer['customer_address'] ?></td>
                                <td><?php echo $row_customer['customer_email'] ?></td>
                                <td>
                                    <!-- echo ra id để xóa và cập nhật -->
                                    <a href="?xoakhang=<?php echo $row_customer['customer_id'] ?>" class="btn btn-danger">Xóa</a>
                                    <a href="?quanli=xemlichsu&magiaodich=<?php echo $row_customer['magiaodich'] ?>" class="btn btn-primary">Xem lịch sử</a>
                                </td>
                            </tr>
                            
                        <?php
                        }
                        ?>


                    </table>
                    <?php
                    $sql_page = mysqli_query($mysqli, "SELECT * FROM customer, giaodich 
                    WHERE customer.customer_id = giaodich.khachhang_id
                    GROUP BY giaodich.magiaodich
                    ORDER BY customer.customer_id DESC");
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
                            <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="customer.php?page=<?php echo $i ?>"><?php echo $i ?></a>
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