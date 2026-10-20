<!-- footer -->
<footer>
    <div class="footer-top-first">
        <div class="container py-md-5 py-sm-4 py-3">
            <!-- footer first section -->
            <div class="row w3l-grids-footer border-top border-bottom py-sm-4 py-3">
                <div class="col-md-4 offer-footer">
                    <div class="row">
                        <div class="col-4 icon-fot">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="col-8 text-form-footer">
                            <h3>Miễn phí vận chuyển</h3>
                            <p>Đơn hàng trên 1.000.000đ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 offer-footer my-md-0 my-4">
                    <div class="row">
                        <div class="col-4 icon-fot">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="col-8 text-form-footer">
                            <h3>Giao hàng nhanh</h3>
                            <p>Trên toàn quốc</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 offer-footer">
                    <div class="row">
                        <div class="col-4 icon-fot">
                            <i class="far fa-thumbs-up"></i>
                        </div>
                        <div class="col-8 text-form-footer">
                            <h3>Đa dạng sản phẩm</h3>
                            <p>Thời trang & dụng cụ thể thao</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- //footer second section -->
        </div>
    </div>
    <!-- footer third section -->
    <div class="w3l-middlefooter-sec">
        <div class="container py-md-5 py-sm-4 py-3">
            <div class="row footer-info w3-agileits-info">
                <!-- footer categories -->
                <div class="col-md-3 col-sm-6 footer-grids">
                    <h3 class="text-white font-weight-bold mb-3">Danh mục</h3>
                    <ul>
                        <?php
                        $sql_category_danhmuc = mysqli_query($mysqli, 'SELECT * FROM tbl_category ORDER BY category_id ASC');
                        while ($row_category_danhmuc = mysqli_fetch_array($sql_category_danhmuc)) {
                        ?>

                            <li class="nav-item mr-lg-2 mb-lg-0 mb-2">
                                <a class="nav-link" href="?quanli=danhmuc&id=<?php echo $row_category_danhmuc['category_id'] ?>" role="button" aria-haspopup="true" aria-expanded="false">
                                    <?php
                                    echo $row_category_danhmuc['category_name'];
                                    ?>
                                </a>

                            </li>
                        <?php
                        }
                        ?>
                    </ul>

                </div>
                <!-- //footer categories -->
                <!-- quick links -->
                <div class="col-md-3 col-sm-6 footer-grids mt-sm-0 mt-4">
                    <h3 class="text-white font-weight-bold mb-3">Đường dẫn</h3>
                    <ul>
                        <li class="mb-3"><a href="?quanli=gioithieu">Về Czuka Shop</a></li>
                        <li class="mb-3"><a href="?quanli=huongdan">Hướng Dẫn</a></li>
                        <li class="mb-3"><a href="?quanli=tuyendung">Tuyển Dụng</a></li>

                    </ul>
                </div>
                <div class="col-md-3 col-sm-6 footer-grids mt-md-0 mt-4">
                    <h3 class="text-white font-weight-bold mb-3">Liên hệ</h3>
                    <ul>
                        <li class="mb-3"><i class="fas fa-map-marker"></i> Yên Nghĩa - Hà Đông - Hà Nội</li>
                        <li class="mb-3"><i class="fas fa-mobile"></i> 0987654321</li>
                        <li class="mb-3"><i class="fas fa-envelope-open"></i>
                            <a href="mailto:sportshop@gmail.com">CzukaShop@gmail.com</a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-3 col-sm-6 footer-grids w3l-agileits mt-md-0 mt-4">

                    <!-- //newsletter -->
                    <!-- social icons -->
                    <div class="footer-grids w3l-socialmk mt-3">
                        <h3 class="text-white font-weight-bold mb-3">Theo dõi chúng tôi</h3>
                        <div class="social">
                            <ul>
                                <li>
                                    <a class="icon fb" href="#">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                </li>
                                <li>
                                    <a class="icon ig" href="#">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                </li>
                                <li>
                                    <a class="icon yt" href="#">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- //social icons -->
                </div>
            </div>
            <!-- //quick links -->
        </div>
    </div>
    <!-- //footer third section -->
</footer>
<!-- //footer -->
<!-- copyright -->
<div class="copy-right py-3">
    <div class="container">
        <p class="text-center text-white">© 2025 Czuka Shop. Bản quyền thuộc về Czuka Shop</p>
    </div>
</div>
<!-- //copyright -->

<head>
    <style>
        /* Màu nền riêng cho từng mạng xã hội */
        .footer-grids .social ul li a.fb {
            background-color: #3b5998;
            /* Facebook xanh */
            color: #fff;
        }

        .footer-grids .social ul li a.ig {
            background: linear-gradient(45deg, #f58529, #dd2a7b, #8134af, #515bd4);
            /* Instagram gradient */
            color: #fff;
        }

        .footer-grids .social ul li a.yt {
            background-color: #ff0000;
            /* Youtube đỏ */
            color: #fff;
        }

        /* Các icon tròn và căn giữa */
        .footer-grids .social ul li a {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            transition: 0.3s;
            text-decoration: none;
        }

        /* Hover nhẹ */
        .footer-grids .social ul li a:hover {
            opacity: 0.8;
        }
    </style>
</head>