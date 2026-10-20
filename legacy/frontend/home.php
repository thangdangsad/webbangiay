<!-- New Products Banner Slide -->
<div class="new-products-banner py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <div class="container">
        <h2 class="text-center mb-5" style="font-size: 2.5rem; font-weight: bold;">Sản Phẩm Mới</h2>
        <div class="row">
            <div class="col-lg-12">
                <div id="newProductsCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
                    <div class="carousel-inner">
                        <?php
                        $sql_new_products = mysqli_query($mysqli, "SELECT * FROM product ORDER BY product_id DESC LIMIT 10");
                        $slide_index = 0;
                        while ($row_new_product = mysqli_fetch_array($sql_new_products)) {
                            $active_class = ($slide_index === 0) ? 'active' : '';
                        ?>
                            <div class="carousel-item <?php echo $active_class; ?>">
                                <div class="row align-items-center" style="min-height: 450px; padding-bottom: 80px;">
                                    <div class="col-md-6">
                                        <img src="images/<?php echo $row_new_product['product_image']; ?>" alt="<?php echo $row_new_product['product_name']; ?>" class="img-fluid" style="max-height: 350px; object-fit: cover; border-radius: 10px;">
                                    </div>
                                    <div class="col-md-6 pl-5 pr-3">
                                        <h3 style="font-size: 2rem; font-weight: bold; margin-bottom: 15px;">
                                            <?php echo $row_new_product['product_name']; ?>
                                        </h3>
                                        <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px; opacity: 0.95;">
                                            <?php
                                            $description = isset($row_new_product['product_description']) ? $row_new_product['product_description'] : 'Sản phẩm chất lượng cao, thiết kế hiện đại.';
                                            echo strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description;
                                            ?>
                                        </p>
                                        <div style="margin-bottom: 20px;">
                                            <p style="margin: 0; font-size: 0.95rem;"><strong>Kích cỡ:</strong> <?php echo $row_new_product['size']; ?></p>
                                        </div>
                                        <div class="pricing" style="margin-bottom: 25px;">
                                            <del style="font-size: 1rem; opacity: 0.8;"><?php echo number_format($row_new_product['product_price']); ?>đ</del>
                                            <span style="font-size: 2rem; font-weight: bold; margin-left: 15px;">
                                                <?php echo number_format($row_new_product['product_discount']); ?>đ
                                            </span>
                                        </div>
                                        <a href="?quanli=chitietsp&id=<?php echo $row_new_product['product_id']; ?>&category=<?php echo $row_new_product['category_id']; ?>" class="btn" style="background-color: white; color: #667eea; font-weight: bold; padding: 12px 30px; border-radius: 5px; font-size: 1.05rem;">
                                            Xem Chi Tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $slide_index++;
                        }
                        ?>
                    </div>
                    <a class="carousel-control-prev" href="#newProductsCarousel" role="button" data-slide="prev" style="display: none;">
                        <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color: rgba(255,255,255,0.5); border-radius: 50%;"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#newProductsCarousel" role="button" data-slide="next" style="position: absolute; right: -120px; top: 50%; transform: translateY(-50%); width: auto; background-color: rgba(255,255,255,0.8); border-radius: 50%; padding: 12px;">
                        <span class="carousel-control-next-icon" aria-hidden="true" style="background-color: transparent; width: 20px; height: 20px;"></span>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- //New Products Banner Slide -->

<!-- top Products -->
<div class="ads-grid py-sm-5 py-4">
    <div class="container py-xl-4 py-lg-2">
        <!-- tittle heading -->
        <h3 class="tittle-w3l text-center mb-lg-5 mb-sm-4 mb-3">
            Sản Phẩm Nổi Bật
        </h3>
        <!-- //tittle heading -->
        <div class="row">
            <!-- product right -->
            <div class="col-lg-3 mt-lg-0 mt-4 p-lg-0">
                <div class="side-bar p-sm-4 p-3">
                    <!-- reviews -->
                    <div class="customer-rev border-bottom left-side py-3">
                        <h3 class="agileits-sear-head mb-3">Đánh giá khách hàng</h3>
                        <ul class="list-unstyled">
                            <li>
                                <a href="#">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star-half text-warning"></i>
                                    <span class="ml-2">5.0</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <!-- //reviews -->

                    <!-- best seller -->
                    <div class="f-grid py-3">
                        <h3 class="agileits-sear-head mb-3">Sản phẩm bán chạy</h3>
                        <div class="box-scroll">
                            <div class="scroll">
                                <?php
                                $sql_product_sidebar = mysqli_query($mysqli, "SELECT * FROM product WHERE product_hot='0' ORDER BY product_id ASC");
                                while ($row_product_sidebar = mysqli_fetch_array($sql_product_sidebar)) {
                                ?>
                                    <div class="row p-2 align-items-center product-sidebar-item">
                                        <div class="col-4">
                                            <img src="images/<?php echo $row_product_sidebar['product_image'] ?>"
                                                alt="<?php echo $row_product_sidebar['product_name'] ?>"
                                                class="img-fluid rounded">
                                        </div>
                                        <div class="col-8 pl-2">
                                            <a href="#" class="product-name-sidebar">
                                                <?php echo $row_product_sidebar['product_name'] ?>
                                            </a>
                                            <p style="margin: 0; font-size: 13px;"><strong>Size:</strong> <?php echo $row_product_sidebar['size']; ?></p>
                                            <div class="price-sidebar mt-1">
                                                <?php echo number_format($row_product_sidebar['product_discount']) . '.đ' ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <!-- //best seller -->

                </div>
            </div>
            <!-- //product right -->

            <!-- product left -->
            <div class="agileinfo-ads-display col-lg-9">
                <div id="categoryCarousel" class="carousel slide" data-ride="carousel" data-interval="false">
                    <div class="carousel-inner">
                        <?php
                        $sql_cate_home = mysqli_query($mysqli, "SELECT * FROM tbl_category ORDER BY category_id ASC");
                        $category_index = 0;
                        while ($row_cate_home = mysqli_fetch_array($sql_cate_home)) {
                            $id_category = $row_cate_home['category_id'];
                            $active_category = ($category_index === 0) ? 'active' : '';
                        ?>
                            <div class="carousel-item <?php echo $active_category; ?>">
                                <div class="wrapper">
                                    <!-- first section -->
                                    <div class="product-sec1 px-sm-4 px-3 py-sm-5 py-3 mb-4">
                                        <h3 class="heading-tittle text-center">
                                            <?php echo $row_cate_home['category_name'] ?>
                                        </h3>
                                        <div class="row" style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                                            <?php
                                            // chỉ lấy 3 sản phẩm mới nhất theo danh mục
                                            $sql_product = mysqli_query($mysqli, "SELECT * FROM product WHERE category_id='$id_category' ORDER BY product_id DESC LIMIT 3");
                                            while ($row_product = mysqli_fetch_array($sql_product)) {
                                            ?>
                                                <div class="col-md-4 product-men mt-5" style="flex: 0 0 32%; max-width: 32%; margin-bottom: 20px;">
                                                    <div class="men-pro-item simpleCart_shelfItem" style="height: 100%; display: flex; flex-direction: column;">
                                                        <div class="men-thumb-item text-center" style="flex: 1;">
                                                            <img src="images/<?php echo $row_product['product_image'] ?>" alt="" class="img-fluid" style="width: 100%; height: 200px; object-fit: cover;">
                                                            <div class="men-cart-pro">
                                                                <div class="inner-men-cart-pro">
                                                                    <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>&category=<?php echo $row_product['category_id'] ?>" class="link-product-add-cart">Xem sản phẩm</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="item-info-product text-center border-top mt-4" style="flex: 0 0 auto;">
                                                            <h4 class="pt-1" style="height: 50px; overflow: hidden;">
                                                                <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>&category=<?php echo $row_product['category_id'] ?>">
                                                                    <?php echo $row_product['product_name'] ?>
                                                                </a>
                                                            </h4>

                                                            <!-- Hiển thị size sản phẩm -->
                                                            <p style="margin: 5px 0;"><strong>Size:</strong> <?php echo $row_product['size']; ?></p>

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
                                                                        <input type="hidden" name="size" value="<?php echo $row_product['size']; ?>" />
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
                                        <!-- nút xem thêm -->
                                        <div class="text-center mt-4">
                                            <a href="?quanli=danhmuc&id=<?php echo $id_category ?>" class="btn btn-primary">Xem thêm</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $category_index++;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <!-- //product left -->
        </div>
    </div>
</div>
<!-- //top products -->

<!-- Nike, Puma, Accessories Sections -->
<div class="ads-grid py-1">
    <div class="container py-1">
        <div class="row">
            <!-- sidebar -->
            <div class="col-lg-3 mt-lg-0 mt-4 p-lg-0">
                <div class="side-bar p-sm-4 p-3">
                </div>
            </div>
            <!-- content -->
            <div class="col-lg-9">
                <!-- Nike Section -->
                <div class="product-sec1 px-sm-4 px-3 py-sm-2 py-1 mb-2" style="margin-top: -140px;">
                    <h3 class="heading-tittle text-center">Nike</h3>
                    <div class="row" style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                        <?php
                        $sql_nike = mysqli_query($mysqli, "SELECT * FROM product WHERE category_id IN (SELECT category_id FROM tbl_category WHERE category_name LIKE '%Nike%') ORDER BY product_id DESC LIMIT 3");
                        while ($row_nike = mysqli_fetch_array($sql_nike)) {
                        ?>
                            <div class="col-md-4 product-men mt-3" style="flex: 0 0 32%; max-width: 32%; margin-bottom: 15px;">
                                <div class="men-pro-item simpleCart_shelfItem" style="height: 100%; display: flex; flex-direction: column;">
                                    <div class="men-thumb-item text-center" style="flex: 1;">
                                        <img src="images/<?php echo $row_nike['product_image'] ?>" alt="" class="img-fluid" style="width: 100%; height: 180px; object-fit: cover;">
                                        <div class="men-cart-pro">
                                            <div class="inner-men-cart-pro">
                                                <a href="?quanli=chitietsp&id=<?php echo $row_nike['product_id'] ?>&category=<?php echo $row_nike['category_id'] ?>" class="link-product-add-cart">Xem sản phẩm</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-info-product text-center border-top mt-2" style="flex: 0 0 auto;">
                                        <h4 class="pt-1" style="height: 40px; overflow: hidden; font-size: 14px;">
                                            <a href="?quanli=chitietsp&id=<?php echo $row_nike['product_id'] ?>&category=<?php echo $row_nike['category_id'] ?>">
                                                <?php echo $row_nike['product_name'] ?>
                                            </a>
                                        </h4>
                                        <p style="margin: 3px 0; font-size: 12px;"><strong>Size:</strong> <?php echo $row_nike['size']; ?></p>
                                        <div class="info-product-price my-1" style="font-size: 13px;">
                                            <span class="item_price"><?php echo number_format($row_nike['product_discount']) . ".đ" ?></span>
                                            <del style="font-size: 11px;"><?php echo number_format($row_nike['product_price']) . ".đ" ?></del>
                                        </div>
                                        <div class="snipcart-details top_brand_home_details item_add single-item hvr-outline-out">
                                            <form action="?quanli=giohang" method="post">
                                                <fieldset>
                                                    <input type="hidden" name="tensanpham" value="<?php echo $row_nike['product_name'] ?>" />
                                                    <input type="hidden" name="sanpham_id" value="<?php echo $row_nike['product_id'] ?>" />
                                                    <input type="hidden" name="giasanpham" value="<?php echo $row_nike['product_discount'] ?>" />
                                                    <input type="hidden" name="hinhanh" value="<?php echo $row_nike['product_image'] ?>" />
                                                    <input type="hidden" name="soluong" value="1" />
                                                    <input type="hidden" name="size" value="<?php echo $row_nike['size']; ?>" />
                                                    <input type="submit" name="themgiohang" value="Thêm vào giỏ" class="button" style="padding: 6px 12px; font-size: 12px;" />
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
                    <!-- nút xem thêm -->
                    <div class="text-center mt-4">
                        <?php
                        $sql_nike_cat = mysqli_query($mysqli, "SELECT category_id FROM tbl_category WHERE category_name LIKE '%Nike%' LIMIT 1");
                        $row_nike_cat = mysqli_fetch_array($sql_nike_cat);
                        $nike_cat_id = $row_nike_cat['category_id'];
                        ?>
                        <a href="?quanli=danhmuc&id=<?php echo $nike_cat_id ?>" class="btn btn-primary">Xem thêm</a>
                    </div>
                </div>

                <!-- Puma Section -->
                <div class="product-sec1 px-sm-4 px-3 py-sm-2 py-1 mb-2">
                    <h3 class="heading-tittle text-center">Puma</h3>
                    <div class="row" style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                        <?php
                        $sql_puma = mysqli_query($mysqli, "SELECT * FROM product WHERE category_id IN (SELECT category_id FROM tbl_category WHERE category_name LIKE '%Puma%') ORDER BY product_id DESC LIMIT 3");
                        while ($row_puma = mysqli_fetch_array($sql_puma)) {
                        ?>
                            <div class="col-md-4 product-men mt-3" style="flex: 0 0 32%; max-width: 32%; margin-bottom: 15px;">
                                <div class="men-pro-item simpleCart_shelfItem" style="height: 100%; display: flex; flex-direction: column;">
                                    <div class="men-thumb-item text-center" style="flex: 1;">
                                        <img src="images/<?php echo $row_puma['product_image'] ?>" alt="" class="img-fluid" style="width: 100%; height: 180px; object-fit: cover;">
                                        <div class="men-cart-pro">
                                            <div class="inner-men-cart-pro">
                                                <a href="?quanli=chitietsp&id=<?php echo $row_puma['product_id'] ?>&category=<?php echo $row_puma['category_id'] ?>" class="link-product-add-cart">Xem sản phẩm</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-info-product text-center border-top mt-2" style="flex: 0 0 auto;">
                                        <h4 class="pt-1" style="height: 40px; overflow: hidden; font-size: 14px;">
                                            <a href="?quanli=chitietsp&id=<?php echo $row_puma['product_id'] ?>&category=<?php echo $row_puma['category_id'] ?>">
                                                <?php echo $row_puma['product_name'] ?>
                                            </a>
                                        </h4>
                                        <p style="margin: 3px 0; font-size: 12px;"><strong>Size:</strong> <?php echo $row_puma['size']; ?></p>
                                        <div class="info-product-price my-1" style="font-size: 13px;">
                                            <span class="item_price"><?php echo number_format($row_puma['product_discount']) . ".đ" ?></span>
                                            <del style="font-size: 11px;"><?php echo number_format($row_puma['product_price']) . ".đ" ?></del>
                                        </div>
                                        <div class="snipcart-details top_brand_home_details item_add single-item hvr-outline-out">
                                            <form action="?quanli=giohang" method="post">
                                                <fieldset>
                                                    <input type="hidden" name="tensanpham" value="<?php echo $row_puma['product_name'] ?>" />
                                                    <input type="hidden" name="sanpham_id" value="<?php echo $row_puma['product_id'] ?>" />
                                                    <input type="hidden" name="giasanpham" value="<?php echo $row_puma['product_discount'] ?>" />
                                                    <input type="hidden" name="hinhanh" value="<?php echo $row_puma['product_image'] ?>" />
                                                    <input type="hidden" name="soluong" value="1" />
                                                    <input type="hidden" name="size" value="<?php echo $row_puma['size']; ?>" />
                                                    <input type="submit" name="themgiohang" value="Thêm vào giỏ" class="button" style="padding: 6px 12px; font-size: 12px;" />
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
                    <!-- nút xem thêm -->
                    <div class="text-center mt-4">
                        <?php
                        $sql_puma_cat = mysqli_query($mysqli, "SELECT category_id FROM tbl_category WHERE category_name LIKE '%Puma%' LIMIT 1");
                        $row_puma_cat = mysqli_fetch_array($sql_puma_cat);
                        $puma_cat_id = $row_puma_cat['category_id'];
                        ?>
                        <a href="?quanli=danhmuc&id=<?php echo $puma_cat_id ?>" class="btn btn-primary">Xem thêm</a>
                    </div>
                </div>

                <!-- Accessories Section -->
                <div class="product-sec1 px-sm-4 px-3 py-sm-2 py-1 mb-2">
                    <h3 class="heading-tittle text-center">Phụ Kiện</h3>
                    <div class="row" style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                        <?php
                        $sql_phu_kien = mysqli_query($mysqli, "SELECT * FROM product WHERE category_id IN (SELECT category_id FROM tbl_category WHERE category_name LIKE '%Phụ%' OR category_name LIKE '%kiện%') ORDER BY product_id DESC LIMIT 3");
                        while ($row_phu_kien = mysqli_fetch_array($sql_phu_kien)) {
                        ?>
                            <div class="col-md-4 product-men mt-3" style="flex: 0 0 32%; max-width: 32%; margin-bottom: 15px;">
                                <div class="men-pro-item simpleCart_shelfItem" style="height: 100%; display: flex; flex-direction: column;">
                                    <div class="men-thumb-item text-center" style="flex: 1;">
                                        <img src="images/<?php echo $row_phu_kien['product_image'] ?>" alt="" class="img-fluid" style="width: 100%; height: 180px; object-fit: cover;">
                                        <div class="men-cart-pro">
                                            <div class="inner-men-cart-pro">
                                                <a href="?quanli=chitietsp&id=<?php echo $row_phu_kien['product_id'] ?>&category=<?php echo $row_phu_kien['category_id'] ?>" class="link-product-add-cart">Xem sản phẩm</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-info-product text-center border-top mt-2" style="flex: 0 0 auto;">
                                        <h4 class="pt-1" style="height: 40px; overflow: hidden; font-size: 14px;">
                                            <a href="?quanli=chitietsp&id=<?php echo $row_phu_kien['product_id'] ?>&category=<?php echo $row_phu_kien['category_id'] ?>">
                                                <?php echo $row_phu_kien['product_name'] ?>
                                            </a>
                                        </h4>
                                        <p style="margin: 3px 0; font-size: 12px;"><strong>Size:</strong> <?php echo $row_phu_kien['size']; ?></p>
                                        <div class="info-product-price my-1" style="font-size: 13px;">
                                            <span class="item_price"><?php echo number_format($row_phu_kien['product_discount']) . ".đ" ?></span>
                                            <del style="font-size: 11px;"><?php echo number_format($row_phu_kien['product_price']) . ".đ" ?></del>
                                        </div>
                                        <div class="snipcart-details top_brand_home_details item_add single-item hvr-outline-out">
                                            <form action="?quanli=giohang" method="post">
                                                <fieldset>
                                                    <input type="hidden" name="tensanpham" value="<?php echo $row_phu_kien['product_name'] ?>" />
                                                    <input type="hidden" name="sanpham_id" value="<?php echo $row_phu_kien['product_id'] ?>" />
                                                    <input type="hidden" name="giasanpham" value="<?php echo $row_phu_kien['product_discount'] ?>" />
                                                    <input type="hidden" name="hinhanh" value="<?php echo $row_phu_kien['product_image'] ?>" />
                                                    <input type="hidden" name="soluong" value="1" />
                                                    <input type="hidden" name="size" value="<?php echo $row_phu_kien['size']; ?>" />
                                                    <input type="submit" name="themgiohang" value="Thêm vào giỏ" class="button" style="padding: 6px 12px; font-size: 12px;" />
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
                    <!-- nút xem thêm -->
                    <div class="text-center mt-4">
                        <?php
                        $sql_phu_kien_cat = mysqli_query($mysqli, "SELECT category_id FROM tbl_category WHERE category_name LIKE '%Phụ%' OR category_name LIKE '%kiện%' LIMIT 1");
                        $row_phu_kien_cat = mysqli_fetch_array($sql_phu_kien_cat);
                        $phu_kien_cat_id = $row_phu_kien_cat['category_id'];
                        ?>
                        <a href="?quanli=danhmuc&id=<?php echo $phu_kien_cat_id ?>" class="btn btn-primary">Xem thêm</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- //Nike, Puma, Accessories Sections -->
<?php

include('recommend_api.php');

// dùng lại kết nối từ index.php
global $mysqli;

// 🔥 lấy danh sách AI
$ids = getRecommendIds();

if (!empty($ids)) {
    $list = implode(",", $ids);

    $sql = "SELECT * FROM product WHERE product_id IN ($list)";
    $result = $mysqli->query($sql);

    echo '<h3 class="text-center mt-5 mb-4">Gợi ý cho bạn</h3>';
    echo '<div class="row">';

    while ($row = $result->fetch_assoc()) {
?>
        <div class="col-md-3 product-men mt-4">
            <div class="men-pro-item simpleCart_shelfItem" style="border:1px solid #eee; padding:10px; border-radius:8px;">

                <!-- ẢNH -->
                <div class="men-thumb-item text-center">
                    <img src="images/<?php echo $row['product_image']; ?>"
                        class="img-fluid"
                        style="height:200px; object-fit:cover;">

                    <div class="men-cart-pro">
                        <div class="inner-men-cart-pro">
                            <a href="?quanli=chitietsp&id=<?php echo $row['product_id']; ?>&category=<?php echo $row['category_id']; ?>"
                                class="link-product-add-cart">
                                Xem sản phẩm
                            </a>
                        </div>
                    </div>
                </div>

                <!-- INFO -->
                <div class="item-info-product text-center mt-3">

                    <h4 style="height:40px; overflow:hidden;">
                        <?php echo $row['product_name']; ?>
                    </h4>

                    <p style="font-size:13px;">
                        Size: <?php echo $row['size']; ?>
                    </p>

                    <!-- GIÁ -->
                    <div class="info-product-price my-2">
                        <span class="item_price" style="color:red; font-weight:bold;">
                            <?php echo number_format($row['product_discount']); ?>đ
                        </span>
                        <br>
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
                            style="background:#ff5722; color:white; border:none; padding:8px 12px; border-radius:5px;">
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