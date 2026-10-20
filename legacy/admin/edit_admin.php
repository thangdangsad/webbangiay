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

$id_admin = $_SESSION['login_admin_id'];
$errors = '';
$thongBao = '';

if (isset($_POST['doithongtin'])) {
    $name = $_POST['name_admin'];
    $phone = $_POST['phone_admin'];
    $address = $_POST['address_admin'];



    if($errors == '') {
        $sql_update_password = "UPDATE quanli_user SET user_name = ?, user_address = ?, user_phone = ? 
        WHERE user_id = ?";

        $stmt_update = $mysqli->prepare($sql_update_password);
        $stmt_update -> bind_param("ssss",$name , $address, $phone, $id_admin);
        $stmt_update ->execute();

        if ($stmt_update->error) {
            $thongBao = "Thay đổi thông tin không thành công";
        }else {
            
            $thongBao = "Thay đổi thông tin thành công";
        }
        

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
                                <span class="text-dark">  / </span>
                                <a href="thongtinadmin.php">Hồ sơ</a>
                                <span class="text-dark">  / Chỉnh sửa hồ sơ</span>
                            </h6>
                        </div>

                    </div>

                    <!-- Content Row -->
                    <div >
                        <div class="container">
                            <!-- Outer Row -->
                            <div class="row justify-content-center">
                                <div class="col-xl-6 col-lg-6 col-md-4">
                                    <div class="card o-hidden border-1 mb-5">
                                        <div class="card-body p-0">
                                            <!-- Nested Row within Card Body -->
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="p-5">
                                                        <div class="text-center">
                                                            
                                                            <h1 class="h4 mb-2">Thay đổi thông tin</h1>
                                                        </div>
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
                                                        <?php
                                                        $sql = "SELECT * FROM quanli_user WHERE user_email = ? AND user_id = ?";
                                                        $stmt = $mysqli->prepare($sql);
                                                        $stmt->bind_param("ss", $tendangnhap, $id_admin); // 2 giá trị tương đương 2 dấu ? ở trên select
                                                        $stmt->execute();

                                                        $result = $stmt->get_result();
                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                        ?>
                                                                <form action="" method="post">
                                                                    <div class="form-group">
                                                                        <label for="">Email</label>
                                                                        <input type="email" name="tendangnhap" value="<?php echo $tendangnhap ?>" disabled class="form-control">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="">Họ tên</label>
                                                                        <input type="text" name="name_admin" value="<?php echo $row['user_name'] ?>" class="form-control">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="">Địa chỉ</label>
                                                                        <input type="text" name="address_admin" value="<?php echo $row['user_address'] ?>" class="form-control form-control-user" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="">Số điện thoại</label>
                                                                        <input type="text" name="phone_admin" value="<?php echo $row['user_phone'] ?>" class="form-control form-control-user" required>
                                                                    </div>



                                                                    <?php
                                                                    if ($thongBao != '') {
                                                                        echo '
                                                                        <a href="thongtinadmin.php" class="btn btn-primary btn-user btn-block">Đến trang hồ sơ</a>
                                                                        ';
                                                                    } else {
                                                                        echo '
                                                                        <input type="submit" name="doithongtin" class="btn btn-primary btn-user btn-block" value="Đổi thông tin">
                                                                        ';
                                                                    }
                                                                    ?>

                                                                    <hr>

                                                                </form>
                                                        <?php
                                                            }
                                                        }
                                                        ?>


                                                        <div class="d-flex justify-content-between">
                                                            <a href="thongtinadmin.php">Trở lại hồ sơ</a>
                                                            
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