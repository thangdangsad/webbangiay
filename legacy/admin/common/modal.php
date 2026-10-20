<!-- Logout Modal-->

<?php
if (isset($_POST['dangxuat_admin'])) {
    // Xóa tất cả các session
    unset($_SESSION['admin']);
	echo "<script> alert('Đăng xuất thành công.'); window.location.href = 'loginadmin.php'; </script>";

    // Dừng thực thi mã PHP sau khi chuyển hướng
    exit;
}
?>

<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Bạn muốn đăng xuất?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Chọn "Đăng xuất" bên dưới nếu bạn muốn tiếp tục đăng xuất.</div>
            <div class="modal-footer">
                <form action="" method="post">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy bỏ</button>
                    <!-- <a class="btn btn-primary" href="loginadmin.php">
                        
                    </a> -->
                    <input type="submit" name="dangxuat_admin" class="btn btn-success" value="Đăng xuất">
                </form>
            </div>
        </div>
    </div>
</div>