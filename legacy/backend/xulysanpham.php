<?php
include('../db/connect.php');
?>
<?php
// Thêm

if (isset($_POST['themsanpham'])) {
    $tensanpham = $_POST['tensanpham'];
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
        $sql_insert_product = mysqli_query($mysqli,
            "INSERT INTO product(product_name, product_image, product_quantity, product_price,
             product_discount, product_details, product_description, category_id) 

            values('$tensanpham', '$hinhanh', '$soluong', '$gia', '$giakhuyenmai'
            , '$chitiet', '$mota', '$danhmuc')");
        // Sau khi thêm ảnh sẽ vào thư mục images
        move_uploaded_file($hinhanh_tmp, $path . $hinhanh);
        echo "<script> alert('Đã thêm sản phẩm: $tensanpham') </script>";
    }

    // $sql_insert_product = mysqli_query($mysqli, 
    // "INSERT INTO product(product_name, product_image, product_quantity, product_price,
    //  product_discount, product_details, product_description, category_id) 

    // values('$tensanpham', '$hinhanh', '$soluong', '$gia', '$giakhuyenmai'
    // , '$chitiet', '$mota', '$danhmuc')");
    // Sau khi thêm ảnh sẽ vào thư mục images
    // move_uploaded_file($hinhanh_tmp, $path . $hinhanh);
}

//Cập nhật
elseif (isset($_POST['capnhatsanpham'])) {
    // Lấy id để so sanh cập nhật
    $id_update = $_GET['capnhat_id'];

    $tensanpham = $_POST['tensanpham'];
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
        //Trường hợp không chỉnh sửa ảnh
        $chitiet_escaped = mysqli_real_escape_string($mysqli, $chitiet);
        $sql_update_product = "UPDATE product SET product_name='$tensanpham', product_quantity='$soluong', product_price='$gia', product_discount='$giakhuyenmai', product_details='$chitiet_escaped', product_description='$mota', category_id='$danhmuc' WHERE product_id='$id_update'";
    } else {
        //Trường hợp có chỉnh chỉnh sửa ảnh
        $chitiet_escaped = mysqli_real_escape_string($mysqli, $chitiet);
        $hinhanh_escaped = mysqli_real_escape_string($mysqli, $hinhanh);
        $sql_update_product = "UPDATE product SET product_name='$tensanpham', product_image='$hinhanh_escaped', product_quantity='$soluong', product_price='$gia', product_discount='$giakhuyenmai', product_details='$chitiet_escaped', product_description='$mota', category_id='$danhmuc' WHERE product_id='$id_update'";
        move_uploaded_file($hinhanh_tmp, $path . $hinhanh);
    }
    
    mysqli_query($mysqli, $sql_update_product);
    header('Location: xulysanpham.php');
}

?>

<?php
    if(isset($_GET['xoa'])) {
        $id_xoa = $_GET['xoa'];
        $sql_xoa = mysqli_query($mysqli, "DELETE FROM product WHERE product_id = '$id_xoa'");
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm</title>
    <link href="../css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>

<body>
    
    
    </style>
    <div class="d-flex justify-content-center align-content-center bg-light fixed-top">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <h2><a class="navbar-brand" href="#">Dashboard</a></h2>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="donhang.php">Đơn hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="xulydanhmuc.php?quanli=danhmuc">Danh mục</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger font-weight-bold"  href="xulysanpham.php">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customer.php">Khách hàng</a>
                        </li>
                    </ul>
                </div>
                
            </div>
        </nav>
    </div><br>
    <div class="mx-5 mt-5">

        <div class="row mb-5">
            <?php

            if (isset($_GET['quanli']) == 'capnhat') {
                $id_capnhat = $_GET['capnhat_id'];
                $sql_capnhat = mysqli_query($mysqli, "SELECT * FROM product WHERE product_id='$id_capnhat'");
                $row_capnhat = mysqli_fetch_array($sql_capnhat);
                $id_category_1 = $row_capnhat['category_id'];
            ?>
                <div class="col-md-3">
                    <h4 class="py-1 text-center">Cập nhật sản phẩm</h4>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        
                        <label for="">Tên sản phẩm</label>
                        <input type="text" class="form-control" name="tensanpham" value="<?php echo $row_capnhat['product_name'] ?>">
                        
                        <label for="">Hình ảnh</label>
                        <input type="file" class="form-control" name="hinhanh" >
                        <img style="max-width: 100px;" src="../images/<?php echo $row_capnhat['product_image'] ?>" alt=""><br>
                        <label for="">Giá sản phẩm</label>
                        <input type="text" class="form-control" name="giasanpham" value="<?php echo $row_capnhat['product_price'] ?>">
                        <label for="">Giá khuyến mãi</label>
                        <input type="text" class="form-control" name="giakhuyenmai" value="<?php echo $row_capnhat['product_discount'] ?>">
                        <label for="">Số lượng</label>
                        <input type="text" class="form-control" name="soluong" value="<?php echo $row_capnhat['product_quantity'] ?>">

                        <?php
                            $sql_danhmuc= mysqli_query($mysqli, "SELECT * FROM tbl_category ORDER BY category_id ASC");
                        ?>
                            <label for="">Danh mục sản phẩm</label>
                            <select class="form-control" name="danhmuc" required>
                                <option value="">Chọn danh mục</option>
                                <?php
                                    while($row_danhmuc = mysqli_fetch_array($sql_danhmuc)){
                                ?>
                                    <option value="<?php echo $row_danhmuc['category_id'] ?>"><?php echo $row_danhmuc['category_name'] ?></option>
                                <?php
                                    }
                                ?>
                            </select>
                        <label for="">Mô tả</label>
                        <textarea rows="8" class="form-control"  name="mota"><?php echo $row_capnhat['product_description'] ?></textarea>
                        <label for="">Chi tiết</label>
                        <textarea rows="8" class="form-control" name="chitiet"><?php echo $row_capnhat['product_details'] ?></textarea>
                        
                        

                        <input type="submit" name="capnhatsanpham" value="Cập nhật" class="btn btn-success my-2">
                    </form>
                </div>
            <?php
            } else {
            ?>
                <div class="col-md-3 more_product-modal">
                    <h4 class="py-1 text-center">Thêm sản phẩm</h4>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        
                        <label for="">Tên sản phẩm</label>
                        <input type="text" class="form-control" name="tensanpham" placeholder="Tên sản phẩm">
                        
                        <label for="">Hình ảnh</label>
                        <input type="file" class="form-control" name="hinhanh" >
                        <label for="">Giá sản phẩm</label>
                        <input type="text" class="form-control" name="giasanpham" placeholder="Giá sản phẩm">
                        <label for="">Giá khuyến mãi</label>
                        <input type="text" class="form-control" name="giakhuyenmai" placeholder="Giá khuyến mãi">
                        <label for="">Số lượng</label>
                        <input type="text" class="form-control" name="soluong" placeholder="Số lượng">

                        <?php
                            $sql_danhmuc= mysqli_query($mysqli, "SELECT * FROM tbl_category ORDER BY category_id ASC");
                        ?>
                        <label for="">Danh mục sản phẩm</label>
                        <select class="form-control" name="danhmuc" >
                            <option value="">Chọn danh mục</option>
                            <?php
                                while($row_danhmuc = mysqli_fetch_array($sql_danhmuc)){
                            ?>
                            <option value="<?php echo $row_danhmuc['category_id'] ?>"><?php echo $row_danhmuc['category_name'] ?></option>
                            <?php
                                }
                            ?>
                        </select>
                        <label for="">Mô tả</label>
                        <textarea  class="form-control" placeholder="Nhập mô tả sản phẩm" name="mota"></textarea>
                        <label for="">Chi tiết</label>
                        <textarea  class="form-control" placeholder="Nhập chi tiết sản phẩm" name="chitiet"></textarea>
                        
                        

                        <input type="submit" name="themsanpham" value="Thêm sản phẩm" class="btn btn-success my-2">
                    </form>
                </div>
            <?php
            }
            ?>


            <div class="col-md-9">
                <h4 class="py-1 text-center">Danh sách sản phẩm</h4>
                <table class="table table-bordered">
                    <!-- <?php
                    
                    $productsPerPage = 5;
                    if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                        $currentPage = max(1, $_GET['page']);
                    } else {
                        $currentPage = 1;
                    }
                    $begin = ($currentPage - 1) * $productsPerPage;

                    $sql_select_sp = mysqli_query($mysqli, "SELECT * FROM product, tbl_category 
                    WHERE product.category_id = tbl_category.category_id ORDER BY product.product_id ASC LIMIT $begin, $productsPerPage");
                    ?> -->
                    <tr class="text-center">
                        <th>Thứ tự</th>
                        <th>Tên sản phẩm</th>
                        <th>Hình ảnh</th>
                        <th>Số lượng</th>
                        <th>Danh mục</th>
                        <th>Giá sản phẩm</th>
                        <th>Giá khuyến mãi</th>        
                        <th>Thao tác</th>
                    </tr>
                    <?php
                    $i = 0;
                    while ($row_sanpham = mysqli_fetch_array($sql_select_sp)) {
                        $i++;
                        $orderNumber = $begin + $i;
                    ?>
                        <tr class="text-center">
                            <td><?php echo $orderNumber ?></td>
                            <td><?php echo $row_sanpham['product_name'] ?></td>
                            <td class="invert-image">
                                <img style="max-width: 100px;" src="../images/<?php echo $row_sanpham['product_image'] ?>" alt="Hình ảnh" class="img-responsive">
                            </td>
                            <td><?php echo $row_sanpham['product_quantity'] ?></td>
                            <td><?php echo $row_sanpham['category_name'] ?></td>
                            <td><?php echo number_format($row_sanpham['product_price'])."đ" ?></td>
                            <td><?php echo number_format($row_sanpham['product_discount'])."đ" ?></td>
                            <td >
                               <div class="d-flex">
                                     <!-- echo ra id để xóa và cập nhật -->
                                    <a href="?xoa=<?php echo $row_sanpham['product_id']?>" class="btn btn-danger mr-1">Xóa</a>
                                    <!-- Lấy id để so sanh cập nhật -->
                                    <a href="xulysanpham.php?quanli=capnhat&capnhat_id=<?php echo $row_sanpham['product_id']?>" class="btn btn-warning">Sửa</a>
                               </div>
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
                ?>
                <?php
                    include_once('../common/paging.php')
                ?>

            </div>
        </div>
                       
    </div>
</body>

</html>