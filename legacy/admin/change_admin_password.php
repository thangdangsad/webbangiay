<?php
include('../db/connect.php');
?>
<?php
session_start();
include('./common/checkLogin.php')
?>

<?php

if (isset($_SESSION['login_admin_id']) == false) {
    echo "<script> alert('Bạn vẫn chưa đăng nhập, Vui lòng đăng nhập'); window.location.href = 'index.php'; </script>";
    exit();
}

if (isset($_SESSION['login_email'])){
    $tendangnhap = $_SESSION['login_email'];
}

$errors = '';
$thongBao = '';

if (isset($_POST['doimatkhau'])) {
    $matkhaucu = $_POST['matkhaucu'];
    $matkhaumoi_1 = $_POST['matkhaumoi_1'];
    $matkhaumoi_2 = $_POST['matkhaumoi_2'];
    //Kiểm tra mật khẩu cũ có đúng không
    $sql = "SELECT * FROM quanli_user WHERE user_email = ? AND user_password = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $tendangnhap, $matkhaucu); // 2 giá trị tương đương 2 dấu ? ở trên select
    $stmt->execute();

    $result = $stmt->get_result();
    $row_count = $result->num_rows;


    if ($row_count == 0) {
        $errors .= "Mật khẩu cũ sai <br>";
    }

    if($matkhaucu == $matkhaumoi_1){
        $errors .= "Mật khẩu mới không được giống mật khẩu cũ <br>";
    }

    if (strlen($matkhaumoi_1) < 6) {
        $errors .= "Mật khẩu mới phải trên 6 ký tự <br>";
    }

    if ($matkhaumoi_1 != $matkhaumoi_2) {
        $errors .= "Nhập lại mật khẩu không giống nhau <br>";
    }

    if($errors == '') {
        $sql_update_password = "UPDATE quanli_user SET user_password = ? WHERE user_email = ?";
        $stmt_update = $mysqli->prepare($sql_update_password);
        $stmt_update -> bind_param("ss",$matkhaumoi_1 , $tendangnhap);
        $stmt_update ->execute();

        $thongBao = "Thay đổi mật khẩu thành công";

        
    }
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

                    <!-- Page Heading -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <a href="index.php">Dashboard</a>
                                <span class="text-dark"> / </span>
                                <a href="thongtinadmin.php">Hồ sơ</a>
                                <span class="text-dark"> / Đổi mật khẩu</span>
                            </h6>
                        </div>

                    </div>

                    <!-- Content Row -->
                    <div>
                        <div class="container">
                            <!-- Outer Row -->
                            <div class="row justify-content-center">
                                <div class="col-xl-6 col-lg-6 col-md-4">
                                    <div class="card o-hidden border-0 shadow-lg mb-5">
                                        <div class="card-body p-0">
                                            <!-- Nested Row within Card Body -->
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="p-5">
                                                        <div class="text-center">
                                                            <h1 class="h4 mb-2">Đổi mật khẩu</h1>
                                                        </div>

                                                        <?php
                                                        if (isset($_SESSION['admin_home'])) {
                                                        ?>
                                                            <p class="h6 text-dark mb-4 text-center">Mật khẩu admin chỉ có thể thay đổi ở trang quản trị</p>
                                                        <?php
                                                        } else {

                                                        ?>

                                                            <p class="h6 text-dark mb-4 text-center">Vui lòng nhập đầy đủ thông tin cần thay đổi</p>
                                                            <?php
                                                            if ($errors != '') {
                                                                echo '
                                                                    <div class="alert alert-danger p-2"> 
                                                                        ' . $errors . '
                                                                    </div> 
                                                                ';
                                                            } elseif ($thongBao != '') {
                                                                echo '
                                                                    <div class="alert alert-primary p-2"> 
                                                                        ' . $thongBao . '
                                                                    </div> 
                                                                ';
                                                            }
                                                            ?>

                                                            <form action="" method="post">
                                                                <div class="form-group">
                                                                    <label for="">Email</label>
                                                                    <input type="email" name="tendangnhap" value="<?php echo $tendangnhap ?>" disabled class="form-control">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="">Mật khẩu cũ</label>
                                                                    <input type="password" name="matkhaucu" value="<?php if (isset($matkhaucu)) echo $matkhaucu ?>" class="form-control form-control-user" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="">Mật khẩu mới</label>
                                                                    <input type="password" name="matkhaumoi_1" value="<?php if (isset($matkhaumoi_1)) echo $matkhaumoi_1 ?>" class="form-control form-control-user" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="">Nhập lại mật khẩu</label>
                                                                    <input type="password" name="matkhaumoi_2" value="<?php if (isset($matkhaumoi_2)) echo $matkhaumoi_2 ?>" class="form-control form-control-user" required>
                                                                </div>

                                                                <!-- <input type="submit" name="doimatkhau" class="btn btn-primary btn-user btn-block" value="Đổi mật khẩu"> -->
                                                                <?php
                                                                if ($thongBao != '') {
                                                                    echo '
                                                <a href="index.php" class="btn btn-primary btn-user btn-block">Đến trang đăng nhập</a>
                                                ';
                                                                } else {
                                                                    echo '
                                                <input type="submit" name="doimatkhau" class="btn btn-primary btn-user btn-block" value="Đổi mật khẩu">
                                                ';
                                                                }
                                                                ?>


                                                                <hr>

                                                            </form>
                                                            
                                                        <?php
                                                        }

                                                        ?>






                                                        <div class="text-center">
                                                            <a href="index.php">Trở lại </a>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

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

    <?php
    include('./common/modal.php')
    ?>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>