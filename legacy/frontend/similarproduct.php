<style>
.text-roboto {
    font-family: 'Roboto', sans-serif;
}

.card {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.card-body img {
    object-fit: contain;
    height: 250px;
    width: 100%;
}

.card-body.bg-light {
    margin-top: auto;
}

.item-info-product h4 {
    font-size: 16px;
    min-height: 50px;
}

.info-product-price {
    font-size: 14px;
}

.info-product-price del {
    color: #888;
    margin-left: 5px;
}
</style>

<h2 class="h2-text text-center text-dark">Sản phẩm tương tự</h2>
<div class="container d-flex justify-content-center mt-50 mb-50">

<?php
$id_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$id_product = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql_products = false;

if ($id_category > 0) {
    $sql_products = mysqli_query($mysqli, "SELECT * FROM product 
        WHERE category_id = $id_category AND product_id != $id_product LIMIT 4");
}
?>

<div class="row col-12 text-roboto">
<?php
if ($sql_products && mysqli_num_rows($sql_products) > 0) {
    while ($row_product = mysqli_fetch_array($sql_products)) {
?>
    <div class="col-md-3 col-sm-12 mt-2 d-flex">
        <div class="card text-center flex-fill">
            <div class="card-body">
                <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>&category=<?php echo $row_product['category_id'] ?>" class="card-img-actions">
                    <img src="images/<?php echo $row_product['product_image'] ?>" class="card-img img-fluid" alt="">
                </a>
            </div>

            <div class="card-body bg-light text-center">
                <div class="item-info-product mb-2">
                    <h4 class="pt-1">
                        <a href="?quanli=chitietsp&id=<?php echo $row_product['product_id'] ?>&category=<?php echo $row_product['category_id'] ?>" class="card-img-actions">
                            <?php echo $row_product['product_name'] ?>
                        </a>
                    </h4>
                </div>

                <div class="product-size mb-2">
                    <span>Kích cỡ: <?php echo $row_product['size']; ?></span>
                </div>

                <div class="info-product-price my-2">
                    <span class="item_price"><?php echo number_format((int)$row_product['product_discount']) . ".đ" ?></span>
                    <del><?php echo number_format((int)$row_product['product_price']) . ".đ" ?></del>
                </div>

                <form action="?quanli=giohang" method="post">
                    <input type="hidden" name="tensanpham" value="<?php echo $row_product['product_name'] ?>" />
                    <input type="hidden" name="sanpham_id" value="<?php echo $row_product['product_id'] ?>" />
                    <input type="hidden" name="giasanpham" value="<?php echo $row_product['product_discount'] ?>" />
                    <input type="hidden" name="hinhanh" value="<?php echo $row_product['product_image'] ?>" />
                    <input type="hidden" name="soluong" value="1" />

                    <button type="submit" name="themgiohang" class="btn bg-cart"><i class="fa fa-cart-plus mr-2"></i> Thêm vào giỏ</button>
                </form>
            </div>
        </div>
    </div>
<?php
    }
} else {
    echo '<p class="text-center">Không có sản phẩm tương tự.</p>';
}
?>
</div>
</div>
