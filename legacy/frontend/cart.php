<!-- Lấy dữ liệu từ form chi tiết san pham comment = formaction_lydulieu-->
<?php
if (isset($_POST['themgiohang'])) {
    $tensanpham = $_POST['tensanpham'];
    $sanpham_id = $_POST['sanpham_id'];
    $gia = $_POST['giasanpham'];
    $hinhanh = $_POST['hinhanh'];
    $soluong = $_POST['soluong'];

    $sql_select_giohang = mysqli_query($mysqli, "SELECT * FROM cart WHERE product_id ='$sanpham_id'");
    $count = mysqli_num_rows($sql_select_giohang);
    if ($count > 0) {
        $row_sanpham = mysqli_fetch_array($sql_select_giohang);
        $soluong = $row_sanpham['product_quantity'] + 1;
        $sql_giohang = "UPDATE cart SET product_quantity = '$soluong' WHERE product_id = '$sanpham_id'";
    } else {
        $sql_giohang = "INSERT INTO cart(product_name, product_id, product_price, product_image, product_quantity) 
            values ('$tensanpham', '$sanpham_id', '$gia', '$hinhanh', '$soluong')";
    }
    $insert_row = mysqli_query($mysqli, $sql_giohang);
    if ($insert_row == 0) {
        echo "Lỗi MySQL: " . mysqli_error($mysqli);
        header('Location:index.php?quanli=chitietsp&id=' . $sanpham_id);
    }
} elseif (isset($_POST['capnhatsoluong'])) {
    $sanpham_id = $_POST['product_id'];
    $soluong = $_POST['soluong'];
    for ($i = 0; $i < count($sanpham_id); $i++) {
        $id = $sanpham_id[$i];
        $quantity = $soluong[$i];
        if ($quantity <= 0) {
            $sql_delete = mysqli_query($mysqli, "DELETE FROM  cart WHERE product_id = '$id'");
        } else {
            $sql_update = mysqli_query($mysqli, "UPDATE cart SET product_quantity = '$quantity' WHERE product_id = '$id'");
        }
    }
} elseif (isset($_GET['xoa'])) {
    $id_cart = $_GET['xoa'];
    $sql_delete = mysqli_query($mysqli, "DELETE FROM cart WHERE cart_id = '$id_cart'");
}
?>

<style>
    .table-container {
        max-height: 600px;
        overflow-y: auto;
        position: relative;
    }
    .timetable_sub thead {
        position: sticky;
        top: 0;
        background-color: #f5f5f5;
    }
    .timetable_sub tbody {
        width: 100%;
    }
    .timetable_sub tfoot {
        position: sticky;
        bottom: 0;
        background-color: #f5f5f5;
    }
</style>

<div class="privacy py-sm-5 py-4">
    <div class="container py-xl-4 py-lg-2">
        <h3 class="tittle-w3l text-center mb-lg-5 mb-sm-4 mb-3">
            Giỏ hàng của bạn
        </h3>
        <div class="checkout-right">
            <?php
            $sql_laygiohang = mysqli_query($mysqli, "SELECT cart.*, product.size 
                                                     FROM cart 
                                                     JOIN product ON cart.product_id = product.product_id 
                                                     ORDER BY cart.cart_id ASC");
            ?>
            <form action="" method="POST">
                <div class="table-container">
                    <table class="timetable_sub">
                        <thead>
                            <tr>
                                <th>Thứ tự</th>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Tên sản phẩm</th>
                                <th>Size</th>
                                <th>Giá</th>
                                <th>Tổng tiền</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="scrollable-body">
                            <?php
                            $total = 0;
                            $i = 0;
                            while ($row_fetch_giohang = mysqli_fetch_array($sql_laygiohang)) {
                                $totalMoney = $row_fetch_giohang['product_quantity'] * $row_fetch_giohang['product_price'];
                                $total += $totalMoney;
                                $i++;
                            ?>
                                <tr class="rem1">
                                    <td class="invert"><?php echo $i ?></td>
                                    <td class="invert-image">
                                        <a href="#">
                                            <img src="images/<?php echo $row_fetch_giohang['product_image'] ?>" alt=" " class="img-responsive">
                                        </a>
                                    </td>
                                    <td class="invert">
                                        <input style="width: 40px;" type="number" name="soluong[]" min="1" required value="<?php echo $row_fetch_giohang['product_quantity'] ?>">
                                        <input type="hidden" name="product_id[]" value="<?php echo $row_fetch_giohang['product_id'] ?>">
                                    </td>
                                    <td class="invert"><?php echo $row_fetch_giohang['product_name'] ?></td>
                                    <td class="invert"><?php echo $row_fetch_giohang['size'] ?></td>
                                    <td class="invert"><?php echo number_format($row_fetch_giohang['product_price']) . 'đ' ?></td>
                                    <td class="invert"><?php echo number_format($totalMoney) . 'đ' ?></td>
                                    <td class="invert">
                                        <a href="?quanli=giohang&xoa=<?php echo $row_fetch_giohang['cart_id'] ?>" class="btn btn-danger">Xóa</a>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <?php
                            $count_giohang = mysqli_num_rows($sql_laygiohang);
                            if ($count_giohang > 0) {
                            ?>
                                <tr>
                                    <td colspan="8" style="font-size: 20px;">
                                        Tổng tiền cần thanh toán: <span style="color: red;"><?php echo number_format($total) . 'đ' ?></span>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                            <tr>
                                <td colspan="8">
                                    <?php
                                    if ($count_giohang > 0) {
                                    ?>
                                        <input type="submit" class="btn btn-success" value="Cập nhập giỏ hàng" name="capnhatsoluong">
                                        <?php 
                                            if(isset($_SESSION['login_home'])) { 
                                        ?>
                                        <a href="index.php?quanli=thanhtoan" class="btn btn-danger ml-1">Thanh toán</a>
                                        <?php 
                                            } 
                                        ?>
                                    <?php
                                    } else {
                                    ?>
                                        <a class="text-danger font-weight-normal form-control-lg">Chưa có sản phẩm</a>
                                    <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>
