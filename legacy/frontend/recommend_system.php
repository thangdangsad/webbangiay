<?php

function getContentBased($conn, $product_id)
{
    $sql = "SELECT * FROM product WHERE product_id = $product_id";
    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);

    if (!$product) return [];

    $category = $product['category_id'];
    $price = $product['product_price'];
    $name = $product['product_name'];
    $keyword = explode(' ', $name)[0];

    $sql_recommend = "
    SELECT *, 
    (
        (category_id = $category) * 4 +
        (ABS(product_price - $price) < 100000) * 2 +
        (product_name LIKE '%$keyword%') * 1
    ) AS score
    FROM product
    WHERE product_id != $product_id
    ORDER BY score DESC
    LIMIT 4
    ";

    $result = mysqli_query($conn, $sql_recommend);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

function getCollaborative($conn, $product_id)
{
    $sql = "
    SELECT dh2.product_id, COUNT(*) as total
    FROM donhang dh1
    JOIN donhang dh2 
        ON dh1.customer_id = dh2.customer_id
    WHERE dh1.product_id = $product_id
    AND dh2.product_id != $product_id
    GROUP BY dh2.product_id
    ORDER BY total DESC
    LIMIT 4
    ";

    $result = mysqli_query($conn, $sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pid = $row['product_id'];

        $product_sql = "SELECT * FROM product WHERE product_id = $pid";
        $product_result = mysqli_query($conn, $product_sql);
        $product = mysqli_fetch_assoc($product_result);

        if ($product) {
            $data[] = $product;
        }
    }

    return $data;
}
