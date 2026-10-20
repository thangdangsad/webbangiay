<?php
include 'recommend_system.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $id = '';
}
$sql_chitiet = mysqli_query($mysqli, "SELECT * FROM product  WHERE product_id ='$id'");
$contentProducts = getContentBased($mysqli, $id);
$collabProducts = getCollaborative($mysqli, $id);

// fallback nếu không có dữ liệu collaborative
if (empty($collabProducts)) {
    $collabProducts = $contentProducts;
}
?>

<div class="services-breadcrumb">
    <div class="agile_inner_breadcrumb">
        <div class="container">
            <ul class="w3_short">
                <li>
                    <a href="index.php">Trang chủ</a>
                    <i>|</i>
                </li>
                <li>Chi tiết sản phẩm</li>
            </ul>
        </div>
    </div>
</div>

<?php
while ($row_chitiet = mysqli_fetch_array($sql_chitiet)) {
?>
    <div class="banner-bootom-w3-agileits py-5">
        <div class="container py-xl-4 py-lg-2">
            <div class="row">
                <div class="col-lg-5 col-md-8 single-right-left ">
                    <div class="grid images_3_of_2">
                        <div class="flexslider">
                            <ul class="slides">
                                <li data-thumb="images/<?php echo $row_chitiet['product_image'] ?>">
                                    <div class="thumb-image">
                                        <img src="images/<?php echo $row_chitiet['product_image'] ?>" data-imagezoom="true" class="img-fluid" alt="">
                                    </div>
                                </li>
                                <li data-thumb="images/<?php echo $row_chitiet['product_image'] ?>">
                                    <div class="thumb-image">
                                        <img src="images/<?php echo $row_chitiet['product_image'] ?>" data-imagezoom="true" class="img-fluid" alt="">
                                    </div>
                                </li>
                                <li data-thumb="images/<?php echo $row_chitiet['product_image'] ?>">
                                    <div class="thumb-image">
                                        <img src="images/<?php echo $row_chitiet['product_image'] ?>" data-imagezoom="true" class="img-fluid" alt="">
                                    </div>
                                </li>
                            </ul>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 single-right-left simpleCart_shelfItem">
                    <h2 class="mb-1"><?php echo $row_chitiet['product_name'] ?></h2>
                    <p class="mb-1">
                        <span class="item_price2"><?php echo number_format($row_chitiet['product_discount']) . 'đ' ?></span>
                        <del class="del2 mx-2 font-weight-light"><?php echo number_format($row_chitiet['product_price']) . 'đ' ?></del>
                        <label>Giao hàng miễn phí</label>
                    </p>
                    <p class="mb-1">Size: <?php echo $row_chitiet['size'] ?></p>
                    <div class="product-single-w3l">
                        <p class="my-3">
                            <?php echo ($row_chitiet['product_details']) ?>
                        </p>
                        <p class="my-3">
                            <?php echo ($row_chitiet['product_description']) ?>
                        </p>
                    </div>
                    <div class="occasion-cart">
                        <div class="snipcart-details top_brand_home_details item_add single-item hvr-outline-out">
                            <form action="?quanli=giohang" method="post">
                                <fieldset>
                                    <input type="hidden" name="tensanpham" value="<?php echo $row_chitiet['product_name'] ?>" />
                                    <input type="hidden" name="sanpham_id" value="<?php echo $row_chitiet['product_id'] ?>" />
                                    <input type="hidden" name="giasanpham" value="<?php echo $row_chitiet['product_discount'] ?>" />
                                    <input type="hidden" name="hinhanh" value="<?php echo $row_chitiet['product_image'] ?>" />
                                    <input type="hidden" name="size" value="<?php echo $row_chitiet['size'] ?>" />
                                    <input type="hidden" name="soluong" value="1" />
                                    <div class="d-flex">
                                        <input type="number" class="p-1 mr-2" style="width: 40px;" min="1" value="1">
                                        <input type="submit" name="themgiohang" value="Thêm vào giỏ" class="button mr-2" />
                                        <input type="submit" name="themgiohang" value="Mua ngay" class="button-buy" />
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>
<?php

include('recommend_api.php');

// dùng lại kết nối từ index.php
global $mysqli;
$product_id = $_GET['id'] ?? null;

// 🔥 gọi AI hybrid
$ids = getRecommendIds($product_id);

if (!empty($ids)) {
    $list = implode(",", $ids);

    $sql = "SELECT * FROM product WHERE product_id IN ($list)";
    $result = $mysqli->query($sql);

    echo '<h3 class="text-center mt-5 mb-4">Bạn cũng có thể thích</h3>';
    echo '<div class="row">';

    while ($row = $result->fetch_assoc()) {
?>
        <div class="col-md-3 product-men mt-4">
            <div class="men-pro-item" style="border:1px solid #eee; border-radius:8px; padding:10px; transition:0.3s;">

                <!-- ẢNH -->
                <div class="men-thumb-item text-center">
                    <img src="images/<?php echo $row['product_image']; ?>"
                        class="img-fluid"
                        style="height:200px; object-fit:cover;">

                    <div class="men-cart-pro">
                        <div class="inner-men-cart-pro">
                            <a href="?quanli=chitietsp&id=<?php echo $row['product_id']; ?>&category=<?php echo $row['category_id']; ?>"
                                class="link-product-add-cart">
                                Xem nhanh
                            </a>
                        </div>
                    </div>
                </div>

                <!-- INFO -->
                <div class="item-info-product text-center mt-3">

                    <h4 style="height:40px; overflow:hidden; font-size:14px;">
                        <?php echo $row['product_name']; ?>
                    </h4>

                    <p style="font-size:12px;">
                        Size: <?php echo $row['size']; ?>
                    </p>

                    <!-- GIÁ -->
                    <div class="info-product-price my-2">
                        <span style="color:red; font-weight:bold;">
                            <?php echo number_format($row['product_discount']); ?>đ
                        </span><br>
                        <del style="font-size:12px;">
                            <?php echo number_format($row['product_price']); ?>đ
                        </del>
                    </div>

                    <!-- NÚT -->
                    <form action="?quanli=giohang" method="post">
                        <input type="hidden" name="tensanpham" value="<?php echo $row['product_name']; ?>">
                        <input type="hidden" name="sanpham_id" value="<?php echo $row['product_id']; ?>">
                        <input type="hidden" name="giasanpham" value="<?php echo $row['product_discount']; ?>">
                        <input type="hidden" name="hinhanh" value="<?php echo $row['product_image']; ?>">
                        <input type="hidden" name="soluong" value="1">
                        <input type="hidden" name="size" value="<?php echo $row['size']; ?>">

                        <button type="submit" name="themgiohang"
                            style="background:#ff5722; color:white; border:none; padding:6px 10px; border-radius:5px; font-size:13px;">
                            Thêm vào giỏ
                        </button>
                    </form>

                </div>
            </div>
        </div>
<?php
    }

    echo '</div>';
}
?>