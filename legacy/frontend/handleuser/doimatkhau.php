<?php

if (isset($_SESSION['login_id']) == false) {
    echo "<script> alert('Bạn vẫn chưa đăng nhập, Vui lòng đăng nhập'); window.location.href = 'index.php'; </script>";
    exit();
}

if (isset($_SESSION['login_home'])){
    $tendangnhap = $_SESSION['login_home'];
}

$errors = '';
$thongBao = '';

if (isset($_POST['doimatkhau'])) {
    $matkhaucu = $_POST['matkhaucu'];
    $matkhaumoi_1 = $_POST['matkhaumoi_1'];
    $matkhaumoi_2 = $_POST['matkhaumoi_2'];

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

        //Sau khi đổi mật khẩu đăng xuất
        unset($_SESSION['login_home']);
       
        unset($_SESSION['login_name']);
        unset($_SESSION['login_id']);
        unset($_SESSION['user_code']);
    }
}
?>



<div style="background-color: #4E73DF;">
    <div class="container">
        <!-- Outer Row -->
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-6 col-md-4">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 mb-2">Đổi mật khẩu</h1>
                                    </div>

                                    <?php
                                        if(isset($_SESSION['admin_home'])) {                                      
                                    ?>
                                    <p class="h6 text-dark mb-4 text-center">Mật khẩu admin chỉ có thể thay đổi ở trang quản trị</p>
                                    <?php
                                        }else {           
                                            
                                    ?>

                                    <p class="h6 text-dark mb-4 text-center">Vui lòng nhập đầy đủ thông tin cần thay đổi</p>
                                        <?php
                                        if ($errors != '') {
                                            echo '
                                                <div class="alert alert-danger p-2"> 
                                                    ' . $errors . '
                                                </div> 
                                            ';
                                        }elseif($thongBao != '') {
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
                                                <input type="password" name="matkhaucu" value="<?php if(isset($matkhaucu)) echo $matkhaucu ?>" class="form-control form-control-user" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="">Mật khẩu mới</label>
                                                <input type="password" name="matkhaumoi_1" value="<?php if(isset($matkhaumoi_1)) echo $matkhaumoi_1 ?>" class="form-control form-control-user" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="">Nhập lại mật khẩu</label>
                                                <input type="password" name="matkhaumoi_2" value="<?php if(isset($matkhaumoi_2)) echo $matkhaumoi_2 ?>" class="form-control form-control-user" required>
                                            </div>

                                            <!-- <input type="submit" name="doimatkhau" class="btn btn-primary btn-user btn-block" value="Đổi mật khẩu"> -->
                                            <?php
                                            if ($thongBao != '') {
                                                echo '
                                                <a href="index.php" class="btn btn-primary btn-user btn-block">Đến trang đăng nhập</a>
                                                ';
                                            }else {
                                                echo '
                                                <input type="submit" name="doimatkhau" class="btn btn-primary btn-user btn-block" value="Đổi mật khẩu">
                                                ';
                                            }
                                            ?>
                                            

                                            <hr>

                                        </form>
                                        <!-- <a href="index.php?quanli=nhapmaxacnhan" class="text-danger">Quên mật khẩu?</a> -->
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