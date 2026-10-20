<?php
    session_start();
    include('../db/connect.php');
?>

<?php 
    // session_destroy();
    if(isset($_POST['dangnhap'])) {
        $taikhoan =$_POST['taikhoan'];
        $matkhau = $_POST['matkhau'];


        if($taikhoan == '' || $matkhau  == '') {
            echo "<script> alert('Vui lòng nhập đầy đủ tài khoản và mật khẩu') </script>";
        }else {
            $sql_select_admin = mysqli_query($mysqli , "SELECT * FROM quanli_admin WHERE account= '$taikhoan' AND password ='$matkhau' LIMIT 1");
            $count = mysqli_num_rows($sql_select_admin);
            $row_dangnhap = mysqli_fetch_array($sql_select_admin);
            if($count > 0) {
                $_SESSION['dangnhap'] = $row_dangnhap['admin_name'];
                $_SESSION['admin_id'] = $row_dangnhap['admin_id'];
                
                header('Location:dashboard.php');
                
            }else {
                echo "<script> alert('Tài khoản hoặc mật khẩu sai') </script>";
            }
            
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập admin</title>
    <link href="../css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
</head>

<body>
    
    <div class="modal-body col-md-3 my-5 mx-auto border rounded">
        <form action="" method="post">
            <h3 class="text-center text-danger">Đăng nhập Admin</h3>
            <div class="form-group">
                <label class="col-form-label">Tài khoản</label>
                <input type="text" class="form-control" placeholder="Nhập tài khoản admin ..." name="taikhoan" required="">
            </div>
            <div class="form-group">
                <label class="col-form-label">Mật khẩu</label>
                <input type="password" class="form-control" placeholder="Nhập mật khẩu ... " name="matkhau" required="">
            </div>
            <div class="form-group">
                <input type="submit" name="dangnhap" class="form-control btn btn-primary" value="Đăng nhập Admin">
            </div>
            <div class="sub-w3l">
                <div class="custom-control custom-checkbox mr-sm-2">
                    <input type="checkbox" class="custom-control-input" id="customControlAutosizing">
                    <label class="custom-control-label" for="customControlAutosizing">Nhớ mật khẩu?</label>
                </div>
            </div>
        </form>
    </div>
</body>

</html>