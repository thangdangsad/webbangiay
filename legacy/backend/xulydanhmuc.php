<?php
include('../db/connect.php');
?>
<?php
// Thêm
if (isset($_POST['themdanhmuc'])) {
    $tendanhmuc = $_POST['tendanhmuc'];
    if ($tendanhmuc !== '') {
        $sql_insert = mysqli_query($mysqli, "INSERT INTO tbl_category(category_name) values('$tendanhmuc')");
    }else {
        echo "<script> alert('Vui lòng nhập tên danh mục') </script>";
    }
    
}
// Cập nhật
elseif (isset($_POST['capnhat'])) {
    $tendanhmuc = $_POST['tendanhmuc'];
    $id_danhmuc = $_POST['id_danhmuc'];
    $sql_update = mysqli_query($mysqli, "UPDATE tbl_category SET category_name = '$tendanhmuc' WHERE category_id = '$id_danhmuc'");
    header('Location:xulydanhmuc.php');
}
// Xóa
if (isset($_GET['xoa'])) {
    $id_xoa = $_GET['xoa'];
    $sql_xoa = mysqli_query($mysqli, "DELETE FROM tbl_category WHERE category_id = '$id_xoa'");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục</title>
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
                            <a class="nav-link text-danger font-weight-bold" href="xulydanhmuc.php?quanli=danhmuc">Danh mục</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled"  href="xulysanpham.php">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customer.php">Khách hàng</a>
                        </li>
                    </ul>
                </div>
                
            </div>
        </nav>
    </div><br>
    
    <div class="container">

        <div class="row">
            <?php
            if (isset($_GET['capnhat'])) {
                $capnhat = $_GET['capnhat'];
            } else {
                $capnhat = '';
            }

            if ($capnhat) {
                $id_capnhat = $_GET['capnhat'];
                $sql_capnhat = mysqli_query($mysqli, "SELECT * FROM tbl_category WHERE category_id='$id_capnhat'");
                $row_capnhat = mysqli_fetch_array($sql_capnhat);
            ?>
                <div class="col-md-5">
                    <h4 class="py-1 text-center">Cập nhật danh mục</h4>
                    
                    <form action="" method="post">
                        <input type="text" class="form-control" name="tendanhmuc" value="<?php echo $row_capnhat['category_name'] ?>" required>
                        <input type="hidden" class="form-control" name="id_danhmuc" value="<?php echo $row_capnhat['category_id'] ?>">
                        <input type="submit" name="capnhat" value="Cập nhật" class="btn btn-success my-1">
                    </form>
                </div>
            <?php
            } else {
            ?>
                <div class="col-md-5">
                    <h4 class="py-1 text-center">Thêm danh mục</h4>
                    
                    <form action="" method="post">
                        <input type="text" class="form-control" name="tendanhmuc" placeholder="Tên danh mục">
                        <input type="submit" name="themdanhmuc" value="Thêm mới" class="btn btn-success my-1">
                    </form>
                </div>
            <?php
            }
            ?>


            <div class="col-md-7">
                <h4 class="py-1 text-center">Danh mục</h4>
                <table class="table table-bordered">
                    <?php
                    $productsPerPage = 5;
                    if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                        $currentPage = max(1, $_GET['page']);
                    } else {
                        $currentPage = 1;
                    }
                    $begin = ($currentPage - 1) * $productsPerPage;


                    $sql_select = mysqli_query($mysqli, "SELECT * FROM tbl_category ORDER BY category_id ASC LIMIT $begin, $productsPerPage");
                    ?>
                    <tr class="text-center">
                        <th>Thứ tự</th>
                        <th>Tên danh mục</th>
                        <th>Thao tác</th>
                    </tr>
                    <?php
                    $i = 0;
                    $orderNumber =0;
                    while ($row_category = mysqli_fetch_array($sql_select)) {
                        $i++;
                        $orderNumber = $begin +$i;
                    ?>
                        <tr class="text-center">
                            <td><?php echo $orderNumber ?></td>
                            <td><?php echo $row_category['category_name'] ?></td>
                            <td>
                                <!-- echo ra id để xóa và cập nhật -->
                                <a href="?xoa=<?php echo $row_category['category_id'] ?>" class="btn btn-danger" >Xóa</a>
                                <a href="?capnhat=<?php echo $row_category['category_id'] ?>" class="btn btn-success">Cập nhật</a>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>


                </table>
                    <?php

                    $sql_page = mysqli_query($mysqli, "SELECT * FROM tbl_category");
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
                            <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="xulydanhmuc.php?page=<?php echo $i ?>"><?php echo $i ?></a>
                        <?php
                        }
                        ?>

                    </div>
                    

            </div>
        </div>

    </div>

    <!-- Button trigger modal -->
    <!-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#confirmDeleteCategory">
        Launch demo modal
    </button> -->

    <!-- Modal -->
    <div class="modal fade" id="confirmDeleteCategory" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            Bạn có chắc muốn xóa, sau khi xóa dữ liệu sẽ không thể khôi phục!
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            <button type="button" class="btn btn-danger">Xóa</button>
        </div>
        </div>
    </div>
    </div>
    <!-- ... Your existing HTML code ... -->


                    

    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    
</body>

</html>