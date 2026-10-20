<?php
include('../db/connect.php');
?>
<?php
    session_start();
    include('./common/checkLogin.php')
?>

<?php
if (isset($_POST['themsanpham'])) {
    $tensanpham = $_POST['tensanpham'];
    $size = $_POST['size']; // thêm size
    $hinhanh = $_FILES['hinhanh']['name'];
    $hinhanh_tmp = $_FILES['hinhanh']['tmp_name'];
    $soluong = $_POST['soluong'];
    $gia = $_POST['giasanpham'];
    $giakhuyenmai = $_POST['giakhuyenmai'];
    $chitiet = $_POST['chitiet'];
    $mota = $_POST['mota'];
    $danhmuc = $_POST['danhmuc'];
    $path = '../images/';

    $allowedFormats = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $fileExt = strtolower(pathinfo($hinhanh, PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedFormats)) {
        $error_message = "Chỉ cho phép tải lên các định dạng JPG, JPEG, PNG và GIF.";
    }

    // Xác thực kích thước hình ảnh (tính bằng byte)
    $allowedSize = 2 * 1024 * 1024; // 2 MB
    $fileSize = $_FILES['hinhanh']['size'];
    if ($fileSize > $allowedSize) {
        $error_message = "Kích thước ảnh quá lớn. Vui lòng tải lên ảnh có kích thước nhỏ hơn 2 MB.";
    }

    // Kiểm tra xem có thông báo lỗi nào không
    if (isset($error_message)) {
        echo "<script> alert('$error_message') </script>";
    } else {
        // Không có lỗi, tiến hành chèn dữ liệu vào cơ sở dữ liệu
        $sql_insert_product = mysqli_query(
            $mysqli,
            "INSERT INTO product(product_name, size, product_image, product_quantity, product_price,
                 product_discount, product_details, product_description, category_id) 
    
                values('$tensanpham', '$size', '$hinhanh', '$soluong', '$gia', '$giakhuyenmai'
                , '$chitiet', '$mota', '$danhmuc')"
        );
        // Sau khi thêm ảnh sẽ vào thư mục images
        move_uploaded_file($hinhanh_tmp, $path . $hinhanh);
        echo "<script> alert('Đã thêm sản phẩm: $tensanpham') </script>";
        // header('Location: xulysanpham.php');
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
                                
                                <a href="index.php">Dashboard</a> <span class="text-dark"> / </span>
                                <a href="xulysanpham.php">Quản lí sản phẩm</a>
                                <span class="text-dark">  / Thêm sản phẩm</span>
                            </h6> 
                            
                        </div>

                    </div>
                    
                    

                    <div class="col-lg-10 col-lg-12 mb-5">
                        <h4 class="py-1 text-center">Thêm sản phẩm</h4>

                        <form class="row" action="" method="post" enctype="multipart/form-data">
                            <div class="col-1"></div>
                            <div class="col-lg-5">
                                <label for="">Tên sản phẩm</label>
                                <input type="text" class="form-control" name="tensanpham" placeholder="Tên sản phẩm">

                                <label for="">Size</label>
                                <input type="number" class="form-control" name="size" placeholder="Nhập size sản phẩm">

                                <label for="">Hình ảnh</label>
                                <input type="file" class="form-control" name="hinhanh">
                                <label for="">Giá sản phẩm</label>
                                <input type="text" class="form-control" name="giasanpham" placeholder="Giá sản phẩm">
                                <label for="">Giá khuyến mãi</label>
                                <input type="text" class="form-control" name="giakhuyenmai" placeholder="Giá khuyến mãi">
                                <label for="">Số lượng</label>
                                <input type="text" class="form-control" name="soluong" placeholder="Số lượng">

                                <?php
                                $sql_danhmuc = mysqli_query($mysqli, "SELECT * FROM tbl_category ORDER BY category_id ASC");
                                ?>
                                <label for="">Danh mục sản phẩm</label>
                                
                                <select class="form-control" name="danhmuc">
                                    <option value="">Chọn danh mục</option>
                                    <?php
                                    while ($row_danhmuc = mysqli_fetch_array($sql_danhmuc)) {
                                    ?>
                                        <option value="<?php echo $row_danhmuc['category_id'] ?>"><?php echo $row_danhmuc['category_name'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                                
                                <input type="submit" name="themsanpham" value="Thêm sản phẩm" class="btn btn-success my-2" style="width: 200px;">
                            </div>
                            <div class="col-lg-5">
                                
                                <label for="">Mô tả</label>
                                <textarea class="form-control" placeholder="Nhập mô tả sản phẩm" name="mota" style="height: 178px;"></textarea>
                                <label for="">Chi tiết</label>
                                <textarea class="form-control" placeholder="Nhập chi tiết sản phẩm" name="chitiet" style="height: 178px;"></textarea>
                            </div>
                            <div class="col-1"></div>

                        </form>
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

    <!-- Logout Modal-->
    <?php
        include('./common/modal.php')
    ?>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
