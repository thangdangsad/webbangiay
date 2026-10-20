<?php
include('../db/connect.php');
?>
<?php
session_start();
include('./common/checkLogin.php')
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
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <a href="index.php">Dashboard</a><span class="text-dark"> / Tài khoản / Khách hàng</span>
                            </h6>
                        </div>

                    </div>

                    <div class="d-flex justify-content-center align-items-center mb-2">
                        
                        <h4 class="text-center">Tài khoản khách hàng</h4>

                    </div>

                    <!-- Content Row -->
                    <div class="row">


                        <!-- Xem danh tài khoản -->
                        <div class="col-md-12">
                            
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

                                $sql_select_acount = mysqli_query($mysqli, "SELECT * FROM quanli_user WHERE role = 0
                                    ORDER BY user_id ASC LIMIT $begin, $productsPerPage");
                                ?>
                                <tr class="text-center">
                                    <th>Thứ tự</th>
                                    <th>Email</th>
                                    <th>Mật khẩu</th>
                                    <th>Họ tên</th>
                                    <th>Vai trò</th>
                                    <th>Địa chỉ</th>
                                    <th>Số điện thoại</th>
                                    <!-- <th>Quản lí</th> -->

                                </tr>
                                <?php
                                $i = 0;
                                while ($row_acount = mysqli_fetch_array($sql_select_acount)) {
                                    $i++;
                                    $orderNumber = $begin + $i;
                                ?>
                                    <tr class="text-center">
                                        <td><?php echo $orderNumber ?></td>
                                        <td><?php echo $row_acount['user_email'] ?></td>
                                        <td><?php echo $row_acount['user_password'] ?></td>
                                        <td><?php echo $row_acount['user_name'] ?></td>

                                        <td>
                                            <?php
                                            if ($row_acount['role'] == 1) {
                                                echo '<a href="" class="text-danger font-weight-bold">Quản trị viên</a>';
                                            } elseif ($row_acount['role'] == 0) {
                                                echo "Khách hàng";
                                            } else {
                                                echo '<a href="" class="text-success font-weight-bold">Nhân viên</a>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $row_acount['user_address'] ?></td>
                                        </td>
                                        <td>
                                            <?php echo $row_acount['user_phone'] ?></td>
                                        </td>
                                        <!-- <td>

                                            <a href="#1" class="btn btn-warning">Sửa</a>
                                            <a href="#1" class="btn btn-danger">Xóa</a>
                                        </td> -->
                                    </tr>
                                    <!-- Modal edit don hang-->
                                    <div class="modal fade" id="confirmOrder" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">Cập nhật trạng thái đơn hàng?</h5>
                                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">×</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">Chọn "Xác nhận" bên dưới nếu bạn muốn tiếp tục xác nhận.</div>
                                                <div class="modal-footer">
                                                    <form action="" method="post">
                                                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Dừng</button>
                                                        <input type="hidden" name="ma_don_hang" value="<?php echo $row_donhang['mahang'] ?>">
                                                        <input type="submit" name="cancel_order" class="btn btn-danger" value="Hủy đơn">
                                                        <input type="submit" name="confirm_order" class="btn btn-success" value="Xác nhận">
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>

                            </table>

                            <?php
                            $sql_page = mysqli_query($mysqli, "SELECT * FROM quanli_user WHERE role = 0 ");
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
                                    <a <?php if ($i == $currentPage) echo 'style="background-color: #4e73df; border-color: #4e73df; color:white;"' ?> href="taikhoan_khachhang.php?page=<?php echo $i ?>"><?php echo $i ?></a>
                                <?php
                                }
                                ?>

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

    <?php
    include('./common/modal.php')
    ?>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>