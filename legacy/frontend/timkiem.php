<?php
if (isset($_POST['seach_btn'])) {
    $tukhoa = $_POST['seach_product'];
}else {
    header('location: index.php');
}

// Lấy tên danh mục thêm vào thẻ h3 VD: Điện thoai;
$sql_seach_product = mysqli_query($mysqli, "SELECT * FROM product 
	WHERE product_name LIKE '%$tukhoa%'
	ORDER BY product_id ASC");

$title = $tukhoa;
$count_seach = mysqli_num_rows($sql_seach_product);

?>

<!-- top Products -->
<div class="ads-grid py-sm-5 py-4">
    <?php
    if ($count_seach >= 1) {
    ?>
        <div class="container py-xl-4 py-lg-2">
            <!-- tittle heading -->
            <h5 class="tittle-w3l text-center text-dark mb-lg-5 mb-sm-4 mb-3">
                Kết quả tìm kiếm sản phẩm: <?php echo $title ?>
            </h5>
            <!-- //tittle heading -->
            <div class="row">
                <!-- product left -->

                <div class="agileinfo-ads-display col-lg-9">
                    <div class="wrapper">
                        <!-- first section -->
                        <div class="product-sec1 px-sm-4 px-3 py-sm-5 py-3 mb-4">
                            <div class="row">
                                <?php

                                while ($row_product = mysqli_fetch_array($sql_seach_product)) {
                                ?>
                                    <div class="col-md-4 product-men mt-5">
                                        <div class="men-pro-item simpleCart_shelfItem">
                                            <div class="men-thumb-item text-center">
                                                <img src="images/<?php echo $row_product['product_image'] ?>" alt="" class="img-fluid">
                                                <div class="men-cart-pro">
                                                    <div class="inner-men-cart-pro">
                                                        <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>" class="link-product-add-cart">Xem sản phẩm</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item-info-product text-center border-top mt-4">
                                                <h4 class="pt-1">
                                                    <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>">
                                                        <?php echo $row_product['product_name'] ?>
                                                    </a>
                                                </h4>
                                                <div class="info-product-price my-2">
                                                    <span class="item_price"><?php echo number_format($row_product['product_discount']) . ".đ" ?></span>
                                                    <del><?php echo number_format($row_product['product_price']) . ".đ" ?></del>
                                                </div>
                                                <div class="snipcart-details top_brand_home_details item_add single-item hvr-outline-out">
                                                    <form action="?quanli=giohang" method="post">
                                                        <fieldset>
                                                            <input type="hidden" name="tensanpham" value="<?php echo $row_product['product_name'] ?>" />
                                                            <input type="hidden" name="sanpham_id" value="<?php echo $row_product['product_id'] ?>" />
                                                            <input type="hidden" name="giasanpham" value="<?php echo $row_product['product_discount'] ?>" />
                                                            <input type="hidden" name="hinhanh" value="<?php echo $row_product['product_image'] ?>" />
                                                            <input type="hidden" name="soluong" value="1" />
                                                            <input type="submit" name="themgiohang" value="Thêm vào giỏ" class="button" />
                                                        </fieldset>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>

                            </div>
                        </div>
                        <!-- //first section -->


                    </div>
                </div>

                <!-- //product left -->
                <!-- product right -->
                <div class="col-lg-3 mt-lg-0 mt-4 p-lg-0">
                    <div class="side-bar p-sm-4 p-3">
                        <div class="search-hotel border-bottom py-2">
                            <h3 class="agileits-sear-head mb-3">Các loai sản phẩm</h3>
                            <form action="#" method="post">
                                <input type="search" placeholder="Tìm kiếm" name="search" required="">
                                <input type="submit" value=" ">
                            </form>
                            
                        </div>


                    </div>
                    <!-- //product right -->
                </div>
            </div>
        </div>
    <?php

    } else {
    ?>
        <h5 class="text-center text-dark">
            Không tìm thấy sản phẩm : <?php echo $title ?> <br> <br>
            <a href="index.php" class="text-primary small">Trở lại trang chủ</a>
        </h5>
        
    <?php
    }
    ?>
</div>
<!-- //top products -->