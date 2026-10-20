<?php

function getRecommendIds($product_id = null)
{
    $user_id = $_SESSION['user_id'] ?? null;

    // 🔥 Hybrid API
    $url = "http://127.0.0.1:5000/recommend-hybrid?";

    if ($user_id) {
        $url .= "user_id=" . $user_id . "&";
    }

    if ($product_id) {
        $url .= "product_id=" . $product_id;
    }

    $response = @file_get_contents($url);

    if ($response === FALSE) {
        return [];
    }

    return json_decode($response, true);
}
