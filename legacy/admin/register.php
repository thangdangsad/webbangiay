<?php
session_start();

include('./connect.php'); 

// Biến thông báo
$message = "";

// Check if form is submitted
if (
    isset($_POST['dangky']) &&
    !empty($_POST['user_name']) &&
    !empty($_POST['user_email']) &&
    !empty($_POST['user_phone']) &&
    !empty($_POST['user_address']) &&
    !empty($_POST['password']) &&
    !empty($_POST['repassword'])
) {
    // Escape special characters to prevent SQL injection
    $user_name = mysqli_real_escape_string($mysqli, htmlspecialchars($_POST['user_name']));
    $user_email = mysqli_real_escape_string($mysqli, htmlspecialchars($_POST['user_email']));
    $user_phone = mysqli_real_escape_string($mysqli, htmlspecialchars($_POST['user_phone']));
    $user_address = mysqli_real_escape_string($mysqli, htmlspecialchars($_POST['user_address']));
    $password = htmlspecialchars($_POST['password']);
    $repassword = htmlspecialchars($_POST['repassword']);

    // Check if email already exists
    $check_email_sql = "SELECT * FROM `quanly_taikhoan` WHERE `user_email` = '$user_email'";
    $result_email = $mysqli->query($check_email_sql);

    if ($result_email->num_rows > 0) {
        // Email already exists
        $message = "Email này đã được sử dụng. Vui lòng chọn email khác.";
    } else {
        // Check if phone number already exists
        $check_phone_sql = "SELECT * FROM `quanly_taikhoan` WHERE `user_phone` = '$user_phone'";
        $result_phone = $mysqli->query($check_phone_sql);

        if ($result_phone->num_rows > 0) {
            // Phone number already exists
            $message = "Số điện thoại này đã được sử dụng. Vui lòng chọn số điện thoại khác.";
        } else {
            // Generate a unique user code
            $user_code = "UC" . time();
            $role = 1; // Default role for admin

            // Validate password
            if (strlen($password) < 4) {
                $message = "Mật khẩu phải có ít nhất 4 ký tự.";
            } elseif ($password !== $repassword) {
                $message = "Mật khẩu không khớp.";
            } else {
                // Hash the password using md5
                $hashed_password = md5($password);

                // Insert data into database
                $sql = "INSERT INTO `quanly_taikhoan`(`user_name`, `user_code`, `user_email`, `user_phone`, `user_address`, `user_password`, `role`) 
                        VALUES ('$user_name', '$user_code', '$user_email', '$user_phone', '$user_address', '$hashed_password', '$role')";

                if ($mysqli->query($sql) === TRUE) {
                    $message = "Đăng ký tài khoản thành công!";
                } else {
                    $message = "Đăng ký thất bại: " . $mysqli->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            background-image: linear-gradient(#f4d6cf, #8eccf5);
            font-family: Arial, sans-serif;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        legend {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .btn-primary {
            background-color: #007bff;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .login-link {
            color: #007bff;
            text-decoration: none;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .alert {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* New Styles for horizontal layout */
        .form-row .form-group {
            display: inline-block;
            width: 48%;
            margin-right: 4%;
        }
        .form-row .form-group:last-child {
            margin-right: 0;
        }
        /* Hide the user code input */
        .user-code {
            display: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="col-md-6">
        <form action="register.php" method="post">
            <legend>Đăng Ký</legend>

            <?php if ($message): ?>
                <div class="alert <?= strpos($message, 'thành công') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Tên người dùng</label>
                <input type="text" name="user_name" class="form-control" required>
            </div>

            <!-- Hidden Mã người dùng input -->
            <div class="form-group user-code">
                <label>Mã người dùng</label>
                <input type="text" class="form-control" value="<?php echo 'UC' . time(); ?>" readonly>
            </div>

            <!-- Horizontal layout for email and phone -->
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="user_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="user_phone" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" name="user_address" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nhập lại mật khẩu</label>
                <input type="password" name="repassword" class="form-control" required>
            </div>
            <button type="submit" name="dangky" class="btn btn-primary">Đăng ký</button>

            <div class="form-group mt-2">
                Bạn đã có tài khoản? <a href="loginadmin.php" class="btn btn-link">Đăng Nhập</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
