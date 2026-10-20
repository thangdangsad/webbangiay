<?php
include('../db/connect.php');
?>
<?php
    session_start();
    include('./common/checkLogin.php')
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
                            <h6 class="m-0 font-weight-bold text-primary">Quản lí danh mục</h6>
                        </div>
                        
                    </div>
                    <div class="card-body">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <h4 class="py-1 text-center">Thêm danh mục</h4>

                                <form action="" method="post">
                                    <input type="text" class="form-control" name="tendanhmuc" placeholder="Tên danh mục">
                                    <input type="submit" name="themdanhmuc" onclick="return confirm('Bạn có muốn tiếp tục thêm danh mục')"
                                    value="Thêm mới" class="btn btn-success my-1">
                                </form>
                            </div>
                        <?php
                        }
                        ?>


                        <div class="col-md-8">
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
                                $orderNumber = 0;
                                while ($row_category = mysqli_fetch_array($sql_select)) {
                                    $i++;
                                    $orderNumber = $begin + $i;
                                ?>
                                    <tr class="text-center">
                                        <td><?php echo $orderNumber ?></td>
                                        <td><?php echo $row_category['category_name'] ?></td>
                                        <td>
                                            <!-- echo ra id để xóa và cập nhật -->
                                            <a href="?xoa=<?php echo $row_category['category_id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa danh mục không')" class="btn btn-danger">Xóa</a>
                                            <a href="?capnhat=<?php echo $row_category['category_id'] ?>" class="btn btn-warning">Sửa</a>
                                        </td>
                                        
                                    </tr>
                                <?php
                                }
                                ?>

                                <script>  </script>
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

    <!-- Logout Modal-->
    <?php
        include('./common/modal.php')
    ?>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>