<?php

if (isset($_SESSION['login_id']) == false) {
    echo "<script> alert('Bạn vẫn chưa đăng nhập, Vui lòng đăng nhập'); window.location.href = 'index.php'; </script>";
    exit();
}

if (isset($_SESSION['login_home'])){
    $tendangnhap = $_SESSION['login_home'];
}

$id_user = $_SESSION['login_id'];
$errors = '';
$thongBao = '';

if (isset($_POST['doithongtin'])) {
    $name = $_POST['name_user'];
    $phone = $_POST['phone_user'];
    $address = $_POST['address_user'];



    if($errors == '') {
        $sql_update_password = "UPDATE quanli_user SET user_name = ?, user_address = ?, user_phone = ? 
        WHERE user_id = ?";

        $stmt_update = $mysqli->prepare($sql_update_password);
        $stmt_update -> bind_param("ssss",$name , $address, $phone, $id_user);
        $stmt_update ->execute();

        $thongBao = "Thay đổi thông tin thành công";

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
                                    }elseif($thongBao != '') {
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
                                    $stmt->bind_param("ss", $tendangnhap, $id_user); // 2 giá trị tương đương 2 dấu ? ở trên select
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
                                            <input type="text" name="name_user" value="<?php echo $row['user_name'] ?>" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="">Địa chỉ</label>
                                            <input type="text" name="address_user" value="<?php echo $row['user_address'] ?>" class="form-control form-control-user" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="">Số điện thoại</label>
                                            <input type="text" name="phone_user" value="<?php echo $row['user_phone'] ?>" class="form-control form-control-user" required>
                                        </div>
                                        

                                        
                                        <?php
                                        if ($thongBao != '') {
                                            echo '
                                            <a href="index.php?quanli=userinfor" class="btn btn-primary btn-user btn-block">Đến trang hồ sơ</a>
                                            ';
                                            
                                        }else {
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
                                        <a href="index.php?quanli=userinfor">Trở lại hồ sơ</a>
                                        <a href="index.php?quanli=thanhtoan">Đến trang thanh toán</a>
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