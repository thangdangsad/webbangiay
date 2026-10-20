<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $id = '';
}

$limit = 9;
$page = isset($_GET['trang']) ? (int)$_GET['trang'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$sql_cate = mysqli_query($mysqli, "SELECT * FROM tbl_category, product 
    WHERE tbl_category.category_id = product.category_id 
    AND product.category_id = '$id' 
    ORDER BY product.product_id DESC 
    LIMIT $start, $limit");

$sql_count = mysqli_query($mysqli, "SELECT COUNT(*) as total FROM product WHERE category_id = '$id'");
$row_count = mysqli_fetch_array($sql_count);
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

$sql_category = mysqli_query($mysqli, "SELECT * FROM tbl_category WHERE category_id = '$id'");
$sql_title = mysqli_fetch_array($sql_category);
$title = $sql_title['category_name'];
?>

<div class="ads-grid py-sm-5 py-4">
    <div class="container py-xl-4 py-lg-2">
        <h3 class="tittle-w3l text-center mb-lg-5 mb-sm-4 mb-3">
            <?php echo $title ?>
        </h3>
        <div class="row">
            <div class="agileinfo-ads-display col-lg-9">
                <div class="wrapper">
                    <div class="product-sec1 px-sm-4 px-3 py-sm-5 py-3 mb-4">
                        <div class="row" style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                            <?php
                            while ($row_product = mysqli_fetch_array($sql_cate)) {
                            ?>
                                <div class="col-md-4 product-men mt-5" style="flex: 0 0 32%; max-width: 32%; margin-bottom: 20px;">
                                    <div class="men-pro-item simpleCart_shelfItem" style="height: 100%; display: flex; flex-direction: column;">
                                        <div class="men-thumb-item text-center" style="flex: 1;">
                                            <img src="images/<?php echo $row_product['product_image'] ?>" alt="" class="img-fluid" style="width: 100%; height: 200px; object-fit: cover;">
                                            <div class="men-cart-pro">
                                                <div class="inner-men-cart-pro">
                                                    <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>" class="link-product-add-cart">Xem sản phẩm</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="item-info-product text-center border-top mt-4" style="flex: 0 0 auto;">
                                            <h4 class="pt-1" style="height: 50px; overflow: hidden;">
                                                <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>">
                                                    <?php echo $row_product['product_name'] ?>
                                                </a>
                                            </h4>
                                            <div class="info-product-price my-2">
                                                <span class="item_price"><?php echo number_format($row_product['product_discount']) . ".đ" ?></span>
                                                <del><?php echo number_format($row_product['product_price']) . ".đ" ?></del>
                                            </div>
                                            <div class="mb-2">
                                                <span>Size: <?php echo $row_product['size'] ?></span>
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
                    <div class="pagination text-center mt-4">
                        <?php
                        if ($total_pages > 1) {
                            for ($i = 1; $i <= $total_pages; $i++) {
                                if ($i == $page) {
                                    echo "<span style='margin:0 5px; font-weight:bold;'>$i</span>";
                                } else {
                                    echo "<a style='margin:0 5px;' href='?quanli=danhmuc&id=$id&trang=$i'>$i</a>";
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
