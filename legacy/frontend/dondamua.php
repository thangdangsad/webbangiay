<!-- Hero bg -->
<style>
	.hero-section {
		display: block;
		text-align: center;
		position: relative;
		font-size: 0;
		line-height: 1;
		margin-top: 4px;
	}

	.hero-background {
		background-image: url('./images/hero_bg.jpg');
		height: 194px;
		background-position: center;
		background-repeat: no-repeat;
		background-size: cover;
	}

	.hero-section .page-title {
		display: inline-block;
		position: absolute;
		top: 29%;
		left: 0;
		right: 0;
		font-size: 30px;
		color: #ffffff;
		font-weight: 700;
		line-height: 50px;
		margin: 0;
	}
</style>


<?php
	if(isset($_SESSION['login_home'])) {
?>
	<div class="hero-section hero-background">
		<h2 class="page-title">Cảm ơn quý khách đã mua hàng</h2>
	</div>
	<div class="services-breadcrumb">

		<div class="agile_inner_breadcrumb">
			<div class="container">
				<ul class="w3_short">
					<li>
						<a class="text-dark" href="index.php">Trang chủ</a>
						<i>|</i>
					</li>
					<li class="text-danger">Đơn mua</li>
				</ul>
			</div>
		</div>
	</div>


<!-- End Chi tiết đơn hàng -->


	<?php
	if(isset($_SESSION['user_code'])) {
		$user_code = $_SESSION['user_code'];
	}else {
		$user_code = 0;
	}
	$sql_select_donhang = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang
		WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id  
		AND customer.user_code = '$user_code' GROUP BY donhang.mahang
		ORDER BY donhang.order_id DESC");
	?>

	
	<div class="container py-xl-4 py-lg-2">
		
		<!-- tittle heading -->
		<h4 class="text-center mb-lg-5 mb-sm-4 mb-3">
			Đơn hàng của bạn
		</h4>
		<!-- //tittle heading -->
		<div class="checkout-right">
			<!-- Lấy dữ liệu từ databse cart -->

			<div class="table-responsive">
				<form action="" method="POST">

					<table class="timetable_sub">
						<thead>
							<tr>
								<th>Thứ tự</th>
								<th>Mã đơn hàng</th>
								<th>Ngày đặt</th>
								<th>Trang thái</th>
								<th>Xem chi tiết</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$i = 0;
							while ($row_donhang = mysqli_fetch_array($sql_select_donhang)) {
								$i++;
							?>
								<tr class="text-center">
									<td><?php echo $i ?></td>
									<td><?php echo $row_donhang['mahang'] ?></td>
									<td><?php echo $row_donhang['ngayDatHang'] ?></td>
									<td>
										<?php 
											if($row_donhang['trangThai'] == 0) {
												echo "Chưa xác nhận";
											}elseif($row_donhang['trangThai'] == 1) {
												echo "Đã xác nhận";
											}elseif($row_donhang['trangThai'] == 2) {
												echo "Đã hủy";
											}
									
										?>
									</td>
									<td>
										<a href="?quanli=chitietdonmua&madonhang=<?php echo $row_donhang['mahang'] ?>" class="btn btn-success">Xem</a>
									</td>
								</tr>
							<?php
							}
							?>

						</tbody>
						
					</table>
				</form>
			</div>
		</div>

	</div>

<?php
	} else {
?>
	<h5 class="text-center mt-5">Vui lòng đăng nhập để sử dụng chức năng này</h5>
<?php
		} 
?>

