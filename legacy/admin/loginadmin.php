<?php
include('./connect.php'); 

session_start();

// Initialize error message
$errors = '';

// Process login request
if (isset($_POST['login_admin'])) {
    $taikhoan = $_POST['email_admin'];
    $matkhau = $_POST['password_admin'];

    // Perform the query
    $select_admin = mysqli_query($mysqli, "SELECT * FROM quanly_taikhoan WHERE user_email='$taikhoan' AND (role='1' OR role='2')");

    // Check if the query was successful and that at least one row was returned
    if ($select_admin && mysqli_num_rows($select_admin) > 0) {
        $row_admin = mysqli_fetch_array($select_admin);
        $row_email = $row_admin['user_email'];
        $row_password = $row_admin['user_password'];

        // Check if the email matches
        if ($taikhoan != $row_email) {
            $errors .= "Sai tên đăng nhập !<br>";
        }

        // MD5 hash the entered password
        $matkhau_hashed = md5($matkhau);

        // Check if the password is correct
        if ($matkhau_hashed != $row_password) {
            $errors .= "Sai mật khẩu admin ! <br>";
        }

        // If no input, prompt to fill in both fields
        if ($taikhoan == '' || $matkhau == '') {
            echo "<script> alert('Vui lòng nhập đầy đủ tài khoản và mật khẩu')  </script>";
        } else {
            // Check credentials
            if (($taikhoan == $row_email) && ($matkhau_hashed == $row_password)) {
                $_SESSION['admin'] = $row_admin['role'];
                $_SESSION['login_email'] = $row_admin['user_email'];
                $_SESSION['login_name'] = $row_admin['user_name'];
                $_SESSION['login_admin_id'] = $row_admin['user_id'];

                // Redirect based on role
                if ($row_admin['role'] == 1) {
                    header('location: index.php');  // Admin page
                } elseif ($row_admin['role'] == 2) {
                    header('location: index.php');  // Staff page
                }
                exit();
            }
        }
    } else {
        $errors .= "Tài khoản không tồn tại !<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Quản lý</title>
    <!-- Custom fonts for this template -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-3">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Đăng nhập ADMIN</h1>
                                    </div>
                                    <p class="h6 text-danger mb-4 text-center">Vui lòng đăng nhập để vào trang quản lí</p>

                                    <?php
                                    if ($errors != '') {
                                        echo '
                                            <div class="alert alert-danger p-2"> 
                                                ' . $errors . '
                                            </div> 
                                        ';
                                    }
                                    ?>

                                    <form class="user" method="post" action="">
                                        <div class="form-group">
                                            <input style="font-size: 1rem; border-radius: 0.35rem;" type="email" 
                                                   name="email_admin" value="<?php if(isset($taikhoan)) echo $taikhoan; ?>" 
                                                   class="form-control form-control-user" placeholder="Email" required>
                                        </div>

                                        <div class="form-group">
                                            <input style="font-size: 1rem; border-radius: 0.35rem;" type="password" 
                                                   name="password_admin" value="<?php if(isset($matkhau)) echo $matkhau; ?>" 
                                                   class="form-control form-control-user" placeholder="Mật khẩu" required>
                                        </div>

                                        <div>
                                            <input style="font-size: 1rem; border-radius: 0.35rem; width: 100%;" 
                                                   type="submit" name="login_admin" class="btn btn-primary btn-user btn-block" 
                                                   value="Đăng nhập">
                                        </div>

                                        <hr>

                                    </form>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Đăng Ký</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
