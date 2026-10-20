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
    $size = $_POST['size'];
    $hinhanh = $_FILES['hinhanh']['name'];
    $hinhanh_tmp = $_FILES['hinhanh']['tmp_name'];
    $soluong = $_POST['soluong'];
    $gia = $_POST['giasanpham'];
    $giakhuyenmai = $_POST['giakhuyenmai'];
    $chitiet = $_POST['chitiet'];
    $mota = $_POST['mota'];
    $danhmuc = $_POST['danhmuc'];
    $path = '../images/';

    $allowedFormats = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg');
    $fileExt = strtolower(pathinfo($hinhanh, PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedFormats)) {
        $error_message = "Chỉ cho phép tải lên các định dạng ảnh hợp lệ.";
    }

    $allowedSize = 2 * 1024 * 1024;
    $fileSize = $_FILES['hinhanh']['size'];
    if ($fileSize > $allowedSize) {
        $error_message = "Kích thước ảnh quá lớn. Vui lòng tải lên ảnh có kích thước nhỏ hơn 2 MB.";
    }

    if (isset($error_message)) {
        echo "<script> alert('$error_message') </script>";
    } else {
        $sql_insert_product = mysqli_query(
            $mysqli,
            "INSERT INTO product(product_name, size, product_image, product_quantity, product_price,
             product_discount, product_details, product_description, category_id) 
            values('$tensanpham', '$size', '$hinhanh', '$soluong', '$gia', '$giakhuyenmai'
            , '$chitiet', '$mota', '$danhmuc')"
        );
        move_uploaded_file($hinhanh_tmp, $path . $hinhanh);
        echo "<script> alert('Đã thêm sản phẩm: $tensanpham') </script>";
    }
} elseif (isset($_POST['capnhatsanpham'])) {
    $id_update = $_GET['capnhat_id'];
    $tensanpham = $_POST['tensanpham'];
    $size = $_POST['size'];
    $hinhanh = $_FILES['hinhanh']['name'];
    $hinhanh_tmp = $_FILES['hinhanh']['tmp_name'];
    $soluong = $_POST['soluong'];
    $gia = $_POST['giasanpham'];
    $giakhuyenmai = $_POST['giakhuyenmai'];
    $chitiet = $_POST['chitiet'];
    $mota = $_POST['mota'];
    $danhmuc = $_POST['danhmuc'];
    $path = '../images/';

    if ($hinhanh == '') {
        $chitiet_escaped = mysqli_real_escape_string($mysqli, $chitiet);
        $sql_update_product = "UPDATE product SET product_name='$tensanpham', size='$size', product_quantity='$soluong', product_price='$gia', product_discount='$giakhuyenmai', product_details='$chitiet_escaped', product_description='$mota', category_id='$danhmuc' WHERE product_id='$id_update'";
    } else {
        $chitiet_escaped = mysqli_real_escape_string($mysqli, $chitiet);
        $hinhanh_escaped = mysqli_real_escape_string($mysqli, $hinhanh);
        $sql_update_product = "UPDATE product SET product_name='$tensanpham', size='$size', product_image='$hinhanh_escaped', product_quantity='$soluong', product_price='$gia', product_discount='$giakhuyenmai', product_details='$chitiet_escaped', product_description='$mota', category_id='$danhmuc' WHERE product_id='$id_update'";
        move_uploaded_file($hinhanh_tmp, $path . $hinhanh);
    }

    mysqli_query($mysqli, $sql_update_product);
    header('Location: xulysanpham.php');
}
?>

<?php
if (isset($_GET['xoa'])) {
    $id_xoa = $_GET['xoa'];
    $sql_xoa = mysqli_query($mysqli, "DELETE FROM product WHERE product_id = '$id_xoa'");
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include('./common/head.php')
?>

<body id="page-top">
    <div id="wrapper">
        <?php
        include('./common/sidebar.php');
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                include('./common/topbar.php');
                ?>
                <div class="container-fluid">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <a href="index.php">Dashboard</a><span class="text-dark">  / Quản lí sản phẩm</span>
                            </h6>
                        </div>
                    </div>
                    <div class="row mb-5 mb-5">
                        <?php
                        if (isset($_GET['quanli']) == 'capnhat') {
                            $id_capnhat = $_GET['capnhat_id'];
                            $sql_capnhat = mysqli_query($mysqli, "SELECT * FROM product WHERE product_id='$id_capnhat'");
                            $row_capnhat = mysqli_fetch_array($sql_capnhat);
                            $id_category_1 = $row_capnhat['category_id'];
                        ?>
                            <div class="col-lg-10 col-lg-12">
                                <h4 class="py-1 text-center">Cập nhật sản phẩm</h4>
                                <form class="row" action="" method="post" enctype="multipart/form-data">
                                    <div class="col-1"></div>
                                    <div class="col-lg-5">
                                        <label for="">Tên sản phẩm</label>
                                        <input type="text" class="form-control" name="tensanpham" value="<?php echo $row_capnhat['product_name'] ?>">
                                        <label for="">Size</label>
                                        <input type="number" class="form-control" name="size" value="<?php echo $row_capnhat['size'] ?>">
                                        <label for="">Hình ảnh</label>
                                        <input type="file" class="form-control" name="hinhanh">
                                        <img style="max-width: 100px;" src="../images/<?php echo $row_capnhat['product_image'] ?>" alt=""><br>
                                        <label for="">Giá sản phẩm</label>
                                        <input type="text" class="form-control" name="giasanpham" value="<?php echo $row_capnhat['product_price'] ?>">
                                        <label for="">Giá khuyến mãi</label>
                                        <input type="text" class="form-control" name="giakhuyenmai" value="<?php echo $row_capnhat['product_discount'] ?>">
                                        <label for="">Số lượng</label>
                                        <input type="text" class="form-control" name="soluong" value="<?php echo $row_capnhat['product_quantity'] ?>">
                                        <?php
                                        $sql_danhmuc = mysqli_query($mysqli, "SELECT * FROM tbl_category ORDER BY category_id ASC");
                                        ?>
                                        <label for="">Danh mục sản phẩm</label>
                                        <select class="form-control" name="danhmuc" required>
                                            <option value="">Chọn danh mục</option>
                                            <?php
                                            while ($row_danhmuc = mysqli_fetch_array($sql_danhmuc)) {
                                            ?>
                                                <option value="<?php echo $row_danhmuc['category_id'] ?>"><?php echo $row_danhmuc['category_name'] ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                        <input type="submit" name="capnhatsanpham" value="Cập nhật" class="btn btn-success my-2" style="width: 200px;">
                                    </div>
                                    <div class="col-lg-5">
                                        <label for="">Mô tả</label>
                                        <textarea rows="8" class="form-control mb-2" name="mota" style="height: 210px;"><?php echo $row_capnhat['product_description'] ?></textarea>
                                        <label for="">Chi tiết</label>
                                        <textarea rows="8" class="form-control" name="chitiet" style="height: 210px;"><?php echo $row_capnhat['product_details'] ?></textarea>
                                    </div>
                                    <div class="col-1"></div>
                                </form>
                            </div>
                        <?php
                        } else {
                        ?>
                            <div class="col-md-12">
                                <div class="d-flex justify-content-center align-items-center mb-2">
                                    <div class="d-flex mr-auto">
                                        <a href="themsanpham.php" class="btn btn-success">
                                            <i class="fas fa-plus"></i>
                                            Thêm sản phẩm
                                        </a>
                                    </div>
                                    <h4 class="py-1 mr-auto">Danh sách sản phẩm</h4>
                                    <a href="#1" class="btn btn-secondary ml-2">
                                            <i class="fas fa-trash"></i>
                                            Thùng rác(0)
                                    </a>
                                </div>
                                <table class="table table-bordered">
                                    <tr class="text-center">
                                        <th>Thứ tự</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Size</th>
                                        <th>Hình ảnh</th>
                                        <th>Số lượng</th>
                                        <th>Danh mục</th>
                                        <th>Giá sản phẩm</th>
                                        <th>Giá khuyến mãi</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    <?php
                                    $productsPerPage = 5;
                                    if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                                        $currentPage = max(1, $_GET['page']);
                                    } else {
                                        $currentPage = 1;
                                    }
                                    $begin = ($currentPage - 1) * $productsPerPage;
                                    $sql_select_sp = mysqli_query($mysqli, "SELECT * FROM product, tbl_category 
                                    WHERE product.category_id = tbl_category.category_id ORDER BY product.product_id ASC LIMIT $begin, $productsPerPage");
                                    $i = 0;
                                    while ($row_sanpham = mysqli_fetch_array($sql_select_sp)) {
                                        $i++;
                                        $orderNumber = $begin + $i;
                                    ?>
                                        <tr class="text-center">
                                            <td><?php echo $orderNumber ?></td>
                                            <td><?php echo $row_sanpham['product_name'] ?></td>
                                            <td><?php echo $row_sanpham['size'] ?></td>
                                            <td class="invert-image">
                                                <img style="max-width: 100px;" src="../images/<?php echo $row_sanpham['product_image'] ?>" alt="Hình ảnh" class="img-responsive">
                                            </td>
                                            <td><?php echo $row_sanpham['product_quantity'] ?></td>
                                            <td><?php echo $row_sanpham['category_name'] ?></td>
                                            <td><?php echo number_format($row_sanpham['product_price']) . "đ" ?></td>
                                            <td><?php echo number_format($row_sanpham['product_discount']) . "đ" ?></td>
                                            <td>
                                                <a href="?xoa=<?php echo $row_sanpham['product_id'] ?>" class="btn btn-danger mr-1 my-auto" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm không, sau khi xóa sẽ không thể khôi phục!')">Xóa
                                                </a>
                                                <a href="xulysanpham.php?quanli=capnhat&capnhat_id=<?php echo $row_sanpham['product_id'] ?>" class="btn btn-warning">Sửa</a>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </table>
                                <?php
                                $sql_page = mysqli_query($mysqli, "SELECT * FROM product");
                                $count_row_page = mysqli_num_rows($sql_page);
                                $page = ceil($count_row_page / 5);
                                include_once('../common/paging.php')
                                ?>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            include('./common/footer.php')
            ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <?php
        include('./common/modal.php')
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
