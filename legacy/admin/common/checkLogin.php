<?php

// Kiểm tra nếu chưa đăng nhập (mà tự ý gõ đường dẫn admin/index.php) thì sẽ trở về trang index đăng nhập
if (!isset($_SESSION['admin'])) {
    header('Location:loginadmin.php');
    exit();
}
?>