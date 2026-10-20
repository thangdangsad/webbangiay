<?php
$thongBao = '';
if (isset($_POST['huy_don_hang'])) {
	if (isset($_GET['madonhang'])) {
		$maDonHang = $_GET['madonhang'];
	} else {
		$maDonHang = '';
	}
	$sql_update_donhang = mysqli_query($mysqli, "UPDATE donhang SET trangThai = 2
        WHERE mahang = '$maDonHang' ");
	if ($sql_update_donhang) {
		$thongBao = "Đã hủy đơn hàng thành công";
	} else {
		$thongBao = "Hủy đơn hàng thất bại";
	}
}
?>

<?php
if (isset($_GET['madonhang'])) {
	$maDonHang = $_GET['madonhang'];
} else {
	$maDonHang = '';
}
if (isset($_GET['quanli']) && $_GET['quanli'] === 'chitietdonmua') {
	$sql_chitet_donhang = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang
		WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id  
		AND donhang.mahang = '$maDonHang'
		ORDER BY donhang.order_id DESC");
?>
	<div class="container py-xl-4 py-lg-2">
		<div class="d-flex justify-content-between">
			<a href="?quanli=dondamua" class="mr-auto">Trở lại</a>
			<h4 class="text-center mb-lg-5 mb-sm-4 mb-3 mr-auto">
				Chi tiết đơn hàng: <?php echo $maDonHang ?>
			</h4>
		</div>
		<div class="checkout-right">
			<div class="table-responsive">
				<form action="" method="POST">
					<table class="timetable_sub">
						<thead>
							<tr>
								<th>Thứ tự</th>
								<th>Sản phẩm</th>
								<th>Số lượng</th>
								<th>Tên sản phẩm</th>
								<th>Size</th>
								<th>Giá</th>
								<th>Tổng tiền</th>
								<th>Trạng thái</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$i = 0;
							while ($row_chitiet = mysqli_fetch_array($sql_chitet_donhang)) {
								$i++;
							?>
								<tr class="rem1">
									<td class="invert"><?php echo $i ?></td>
									<td class="invert-image">
										<a href="#1">
											<img src="images/<?php echo $row_chitiet['product_image'] ?>" alt=" " class="img-responsive">
										</a>
									</td>
									<td class="invert">
										<?php echo $row_chitiet['soluong'] ?>
									</td>
									<td class="invert"><?php echo $row_chitiet['product_name'] ?></td>
									<td class="invert"><?php echo $row_chitiet['size'] ?></td>
									<td class="invert"><?php echo number_format($row_chitiet['product_discount']) . ",đ" ?></td>
									<td class="invert"><?php echo number_format($row_chitiet['product_discount'] * $row_chitiet['soluong']) . ",đ"  ?></td>
									<td class="invert">
										<?php
										if ($row_chitiet['trangThai'] == 0) {
											echo "Chưa xác nhận";
										} elseif ($row_chitiet['trangThai'] == 1) {
											echo "Đã xác nhận";
										} elseif ($row_chitiet['trangThai'] == 2) {
											echo "Đã hủy";
										}
										?>
									</td>
								</tr>
							<?php
							}
							?>
						</tbody>
						<?php
						$sql_ct_donhang = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang
                        WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id  
                        AND donhang.mahang = '$maDonHang' GROUP BY donhang.mahang
                        ORDER BY donhang.order_id DESC");
						?>
						<tfoot>
							<tr>
								<?php
								while ($row_total = mysqli_fetch_array($sql_ct_donhang)) {
								?>
									<td colspan="5" style="font-size: 1.5rem;">Thành tiền: <span class="text-danger"><?php echo number_format($row_total['tongDoanhThu']) . ",đ" ?></span></td>
									<td colspan="4">
										<form action="" method="post">
											<?php
											if ($row_total['trangThai'] == 2) {
											?>
												<input type="submit" class="btn btn-warning mr-1" value="Đã hủy">
											<?php
											} elseif ($row_total['trangThai'] == 0) {
											?>
												<input type="submit" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng không?');" name="huy_don_hang" class="btn btn-danger mr-1" value="Hủy đơn hàng">
											<?php
											} elseif ($row_total['trangThai'] == 1) {
											?>
												<input type="submit" style="cursor: no-drop;" class="btn btn-success mr-1" value="Đang giao hàng">
											<?php
											}
											?>
											<input type="submit" class="btn btn-secondary" value="Liên hệ shop">
										</form>
									</td>
								<?php
								}
								?>
							</tr>
						</tfoot>
					</table>
				</form>
			</div>
		</div>
	</div>
<?php
}
?>

<?php
$count = 0;
if (isset($_SESSION['login_home'])) {
	$email = $_SESSION['login_home'];
	if (isset($_GET['madonhang'])) {
		$maDonHang = $_GET['madonhang'];
	} else {
		$maDonHang = '';
	}
	$sql_select_donhang = mysqli_query($mysqli, "SELECT * FROM product, customer, donhang 
							WHERE donhang.product_id = product.product_id AND donhang.customer_id = customer.customer_id 
							AND customer.customer_email = '$email' AND donhang.mahang = '$maDonHang' LIMIT 1");
	$count = mysqli_num_rows($sql_select_donhang);
}
?>
<div class="container">
	<h4 class="text-center my-4">Thông tin giao hàng</h4>
	<div class="row">
		<?php
		while ($row_donhang = mysqli_fetch_array($sql_select_donhang)) {
		?>
			<div class="col-lg-6">
				<div class="card mb-4">
					<div class="card-body">
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Họ tên</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark"><?php echo $row_donhang['customer_name'] ?></p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Số điện thoại</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark"><?php echo $row_donhang['customer_phone'] ?></p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Đia chỉ</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark"><?php echo $row_donhang['customer_address'] ?></p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Ngày đặt hàng</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark"><?php echo $row_donhang['ngayDatHang'] ?></p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 font-weight-bold text-danger text-dark">Ghi chú: </p>
							</div>
							<div class="col-sm-8">
								<p style="font-weight: 550;" class="mb-0 text-dark"><?php echo $row_donhang['customer_note'] ?> </p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="card mb-4">
					<div class="card-body">
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Mã đơn</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark">
									<?php echo $row_donhang['mahang'] ?>
								</p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Tổng tiền hàng</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark">
									<?php echo number_format($row_donhang['tongDoanhThu']) . ",đ" ?>
								</p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Phí vận chuyển</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark">Miễn phí vận chuyển</p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 text-dark">Hình Thức vận chuyển</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 text-dark">
									<?php
									if ($row_donhang['payments'] == 0) echo "Thanh toán khi nhận hàng";
									elseif ($row_donhang['payments'] == 1) echo "Thanh toán MOMO";
									elseif ($row_donhang['payments'] == 2) echo "Thanh toán MOMO ATM";
									else echo "Chưa xác định";
							
								
									?>
								</p>

							</div>

						</div>
						<hr>
						<div class="row">
							<div class="col-sm-4">
								<p class="mb-0 font-weight-bolder h5 text-dark">Thành tiền</p>
							</div>
							<div class="col-sm-7">
								<p class="mb-0 font-weight-bolder h5 text-danger">
									<?php echo number_format($row_donhang['tongDoanhThu']) . ",đ" ?>
								</p>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-sm-12">
								<h6 class="mb-0 text-dark" style="font-weight: 500;">
									Vui lòng thanh toán:
									<span class="text-danger"><?php echo number_format($row_donhang['tongDoanhThu']) . ",đ" ?></span>
									khi nhận hàng
								</h6>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
		?>
	</div>
</div>