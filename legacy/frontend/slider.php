<!-- banner -->
<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="2000">
    <!-- Indicators-->
    <ol class="carousel-indicators">
        <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
        <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
        <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
        <li data-target="#carouselExampleIndicators" data-slide-to="3"></li>
    </ol>
    <div class="carousel-inner">
        <div class="carousel-item item1 active">
        </div>
        <div class="carousel-item item2">
        </div>
        <div class="carousel-item item3">
        </div>
        <div class="carousel-item item4">
        </div>
    </div>
    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>
<!-- //banner -->

<!-- CSS để sử dụng hình ảnh làm nền cho các item carousel -->
<style>
    .carousel-item.item1 {
        background-image: url('images/bn1.png');
        /* Đường dẫn đến hình ảnh cho item 1 */
        background-size: cover;
        background-position: center;
    }

    .carousel-item.item2 {
        background-image: url('images/bn2.png');
        /* Đường dẫn đến hình ảnh cho item 2 */
        background-size: cover;
        background-position: center;
    }

    .carousel-item.item3 {
        background-image: url('images/bn33.png');
        /* Đường dẫn đến hình ảnh cho item 3 */
        background-size: cover;
        background-position: center;
    }

    .carousel-item.item4 {
        background-image: url('images/bn4.png');
        /* Đường dẫn đến hình ảnh cho item 4 */
        background-size: cover;
        background-position: center;
    }
</style>

<script>
    $(document).ready(function() {
        let carouselInterval;
        const carousel = $('#carouselExampleIndicators');

        // Khởi động carousel tự động
        carousel.carousel({
            interval: 2000,
            pause: 'hover'
        });

        // Xử lý khi click vào indicator hoặc nút prev/next
        carousel.on('click.bs.carousel', '[data-slide], [data-slide-to]', function() {
            // Dừng carousel
            carousel.carousel('pause');

            // Dừng 5 giây rồi tiếp tục
            setTimeout(function() {
                carousel.carousel('cycle');
            }, 5000);
        });
    });
</script>