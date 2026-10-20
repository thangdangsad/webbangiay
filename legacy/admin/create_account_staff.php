<?php
include('../db/connect.php');
?>
<?php
session_start();
include('./common/checkLogin.php')
?>

<?php
$errors = "";
$result = "";
if(isset($_POST['create_acount'])) {
    $name = $_POST['name_admin'];
	$email = $_POST['email_admin'];
	$phone = $_POST['phone_admin'];
	$address = $_POST['address_admin'];
	$password = $_POST['password_admin'];
	

	if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($password)) {
		$errors .= "Vui lòng nhập đầy đủ thông tin <br>";
	} else {
		// Nếu dữ liệu hợp lệ, thực hiện câu truy vấn để thêm vào cơ sở dữ liệu
		$sql_user_insert = mysqli_query($mysqli, "INSERT INTO quanli_user(role ,user_name, user_email, user_phone, user_address, user_password) 
			values (2, '$name', '$email', '$phone', '$address', '$password')");

		if ($sql_user_insert) {
			$result .= "Tạo tài khoản thành công <br>";
		} else {
			$result .= "Tạo tài khoản không thành công <br>";
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
                                <a href="taikhoan_admin.php">Tài khoản Admin</a>
                            
                                <span class="text-dark">  / Tạo tài khoản</span>
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
                                                            
                                                            <h1 class="h4 mb-2">Tạo tài khoản khách hàng</h1>
                                                        </div>
                                                        <p class="h6 text-primary mb-4 text-center">Vui lòng nhập đầy đủ thông tin để tạo tài khoản</p>

                                                        <?php
                                                        if ($errors != '') {
                                                            echo '
                                                            <div class="alert alert-danger p-2"> 
                                                                ' . $errors . '
                                                            </div> 
                                                            ';
                                                        } elseif ($result != '') {
                                                            echo '
                                                            <div class="alert alert-primary p-2"> 
                                                                ' . $result . '
                                                            </div> 
                                                            ';
                                                        }
                                                        ?>

                                                        
                                                                <form action="" method="post">
                                                                    <div class="form-group">
                                                                        <label for="">Họ tên</label>
                                                                        <input type="text" name="name_admin"  class="form-control" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="">Email</label>
                                                                        <input type="email" name="email_admin"  class="form-control">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="">Số điện thoại</label>
                                                                        <input type="text" name="phone_admin" class="form-control" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="">Địa chỉ</label>
                                                                        <input type="text" name="address_admin" class="form-control form-control-user" required>
                                                                    </div>

                                                                    <div class="form-group">
                                                                        <label for="">Vai trò</label>
                                                                        <input type="text" class="form-control form-control-user" value="Khách Hàng" disabled>
                                                                    </div>    
                                                                    <div class="form-group">
                                                                        <label for="">Mật khẩu</label>
                                                                        <input type="text" name="password_admin" class="form-control form-control-user" required>
                                                                    </div>

                                                                    <input type="submit" name="create_acount" class="btn btn-primary btn-user btn-block" value="Tạo tài khoản">                                                                                                                                    
                                                                    <hr>

                                                                </form>

                                                              
                                                        <div class="d-flex justify-content-between">
                                                            <a href="taikhoan_admin.php">Đến trang tài khoản</a>
                                                            
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