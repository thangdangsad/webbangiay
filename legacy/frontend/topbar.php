<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
ob_start(); // Bật bộ đệm output để tránh lỗi headers already sent
//Đăng nhập
if (isset($_POST['login_home'])) {
	$taikhoan = $_POST['email_login'];
	$matkhau = $_POST['password_login'];

	//Lấy dữ liệu từ bảng quan li user
	$select_admin = mysqli_query($mysqli, "SELECT * FROM quanli_user WHERE role='1' ");
	$row_admin = mysqli_fetch_array($select_admin);
	$row_email = $row_admin['user_email'];
	$row_password = $row_admin['user_password'];

	//Lấy dữ liệu từ bảng customer

	if ($taikhoan == '' || $matkhau  == '') {
		echo "<script> alert('Vui lòng nhập đầy đủ tài khoản và password')  </script>";
	} else {
		if (($taikhoan == $row_email) && ($matkhau == $row_password)) {
			$_SESSION['admin_home'] = $row_admin['role'];
			$_SESSION['login_email'] = $row_admin['user_email'];
			$_SESSION['login_name'] = $row_admin['user_name'];
			$_SESSION['login_id'] = $row_admin['user_id'];
			echo "<script> alert('Xin chào Admin bạn đã đăng nhập thành công.'); </script>";
			// window.location.href = './backend/donhang.php';

		} else {
			$sql_select_user = mysqli_query($mysqli, "SELECT * FROM quanli_user 
				WHERE user_email= '$taikhoan' AND user_password ='$matkhau' LIMIT 1");
			$count = mysqli_num_rows($sql_select_user);
			$row_dangnhap = mysqli_fetch_array($sql_select_user);
			if ($count > 0) {
				$_SESSION['login_home'] = $row_dangnhap['user_email'];
				$_SESSION['login_name'] = $row_dangnhap['user_name'];
				$_SESSION['login_id'] = $row_dangnhap['user_id'];
				$_SESSION['user_code'] = $row_dangnhap['user_code'];

				$login_name = $_SESSION['login_name'];
				echo "<script> alert('Xin chào $login_name bạn đã đăng nhập thành công.'); window.location.href = 'index.php'; </script>";
			} else {
				echo "<script> alert('Tài khoản hoặc mật khẩu sai') </script>";
			}
		}
	}
}
// Đang ký
elseif (isset($_POST['register'])) {
	$name = $_POST['register_name'];
	$email = $_POST['register_email'];
	$phone = $_POST['register_phone'];
	$address = $_POST['register_address'];
	$password = $_POST['register_password'];
	$user_code = 0;

	if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($password)) {
		echo "<script> alert('Vui lòng nhập đầy đủ thông tin đăng ký') </script>";
	} else {
		// Nếu dữ liệu hợp lệ, thực hiện câu truy vấn để thêm vào cơ sở dữ liệu
		$sql_user_insert = mysqli_query($mysqli, "INSERT INTO quanli_user(user_name, user_code, user_email, user_phone, user_address, user_password) 
			values ('$name', $user_code, '$email', '$phone', '$address', '$password')");


		if ($sql_user_insert) {
			echo "<script> alert('Đăng ký thành công \n Mời bạn đăng nhập'); window.location.href = 'index.php'; </script>";
		} else {
			echo "<script> alert('Đăng ký thất bại.') </script>";
		}
	}
}

// Đăng xuất
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
	// Xóa session
	unset($_SESSION['login_home']);
	unset($_SESSION['login_name']);
	unset($_SESSION['login_id']);
	unset($_SESSION['user_code']);
	unset($_SESSION['admin_home']);
	session_unset();
	session_destroy();
	header("Location: index.php");

	// Dừng thực thi mã PHP sau khi chuyển hướng
	exit;
}
?>


<!-- top-header -->
<div class="agile-main-top">
	<div class="container-fluid">
		<div class="row main-top-w3l py-2" style="background-color: #3b3b3b;">

			<div class="col-lg-6 d-flex justify-content-between align-items-center">
				<p class="shop-intro-neon mb-0">
					Czuka Shop | Shop Giày Chính Hãng Hơn 10 Năm Thịnh Hành Và Phát Triển
					<i class="fas fa-shopping-cart ml-1"></i>
				</p>
			</div>

			<div class="col-lg-6 header-right mt-lg-0 mt-2">
				<!-- header lists -->
				<ul>
					<li class="text-center border-right text-white">
						<a href="index.php?quanli=dondamua" class="text-white">
							<i class="fas fa-truck mr-2"></i>Đơn mua</a>
					</li>
					<li class="text-center border-right text-white">
						<i class="fas fa-phone mr-2"></i> 0987654321
					</li>

					<?php if (!isset($_SESSION['login_home']) && !isset($_SESSION['admin_home'])): ?>
						<!-- Chưa đăng nhập thì hiện Đăng nhập/Đăng ký -->
						<li class="text-center border-right text-white">
							<a href="#" data-toggle="modal" data-target="#loginModal" class="text-white">
								<i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập </a>
						</li>
						<li class="text-center text-white">
							<a href="#" data-toggle="modal" data-target="#registerModal" class="text-white">
								<i class="fas fa-sign-out-alt mr-2"></i>Đăng ký </a>
						</li>
					<?php endif; ?>
					<li class="text-center" style="display: inline; padding-left: 10px;">
						<span style="color: #ffc107; font-weight: bold;">✨ Chào mừng bạn ✨</span>
					</li>

				</ul>
				<!-- //header lists -->
			</div>

		</div>
	</div>
</div>


<!-- //shop locator (popup) -->
<!-- modals -->
<!-- modals -->
<!-- log in -->
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title text-center">Đăng nhập</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form action="#" method="post">
					<div class="form-group">
						<label class="col-form-label">Email</label>
						<input type="text" class="form-control" placeholder=" " name="email_login" required="">
					</div>
					<div class="form-group">
						<label class="col-form-label">Mật khẩu</label>
						<input type="password" class="form-control" placeholder=" " name="password_login" required="">
					</div>
					<div class="form-group">
						<a href="index.php?quanli=quenmatkhau" class="text-danger">Quên mật khẩu?</a>
					</div>
					<div class="right-w3l">
						<input type="submit" class="form-control" name="login_home" value="Đăng nhập">
					</div>

					<p class="text-center dont-do mt-3">Bạn chưa có tài khoản?
						<a href="#" data-toggle="modal" data-target="#registerModal">
							Đăng ký</a>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- register -->
<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Đăng ký</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form action="" method="post">
					<div class="form-group">
						<label class="col-form-label">Họ tên</label>
						<input type="text" class="form-control" placeholder=" " name="register_name" required="">
					</div>
					<div class="form-group">
						<label class="col-form-label">Email</label>
						<input type="email" class="form-control" placeholder=" " name="register_email" required="">
					</div>
					<div class="form-group">
						<label class="col-form-label">Số điện thoại</label>
						<input type="text" class="form-control" placeholder=" " name="register_phone" required="">
					</div>
					<div class="form-group">
						<label class="col-form-label">Địa chỉ</label>
						<input type="text" class="form-control" placeholder=" " name="register_address" required="">
					</div>
					<div class="form-group">
						<label class="col-form-label">Mật khẩu</label>
						<input type="password" class="form-control" placeholder=" " name="register_password" required="">
					</div>

					<div class="right-w3l">
						<input type="submit" name="register" class="form-control" value="Đăng ký">
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- //modal -->
<!-- //top-header#808080 -->
<!-- #3b3b3b -->
<!-- header-bottom-->
<div class="header-bot" style="background-color: azure;">
	<div class="container">
		<div class="row header-bot_inner_wthreeinfo_header_mid d-flex align-items-center justify-content-between">
			<!-- logo -->
			<div class="col-md-3 logo_agile">
				<a class="text-center" href="index.php">
					<img src="images/CzukaShop.jpg" style="z-index: 1000;" class="img-fluid" alt="">
				</a>
			</div>

			<!-- //logo -->
			<!-- header-bot -->
			<div class="col-md-9 header mt-4 mb-md-0 mb-5">
				<div class="row d-flex align-items-center">
					<!-- search -->
					<div class="col-9 agileits_search">
						<form class="form-inline" action="index.php?quanli=timkiem" method="post">
							<input class="form-control mr-sm-2" name="seach_product" type="search" placeholder="Tìm kiếm sản phẩm" aria-label="Search" required>
							<button class="btn my-2 my-sm-0" name="seach_btn" type="submit">Tìm kiếm</button>
						</form>
					</div>
					<!-- //search -->
					<!-- cart details -->
					<?php
					$sql_cart = mysqli_query($mysqli, "SELECT * FROM cart");
					$countCart = mysqli_num_rows($sql_cart);
					?>
					<div class="col-1 top_nav_right text-center mt-sm-0 mt-2">
						<div class="wthreecartaits wthreecartaits2 cart cart box_1">
							<form action="?quanli=giohang" method="post">
								<input type="hidden" name="cmd" value="_cart">
								<input type="hidden" name="display" value="1">
								<button class="btn w3view-cart" type="submit" name="submit" value="">
									<i class="fas fa-cart-arrow-down position-relative">
										<span class="position-absolute quantity_cart">
											<?php echo $countCart ?>
										</span>
									</i>
								</button>
							</form>

						</div>

					</div>


					<div class="col-1 dropdown top_nav_right mt-sm-0 mt-2">
						<?php if (isset($_SESSION['login_home']) || isset($_SESSION['admin_home'])) : ?>
							<nav class="navbar navbar-expand navbar-light bg-white topbar static-top shadow">
								<div class="nav-item dropdown no-arrow">
									<a class="nav-link dropdown-toggle d-flex align-items-center rounded" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<img class="img-profile rounded-circle" src="./images/user1.png" width="40px">
										<span class="mx-2 d-none d-lg-inline text-gray-600" style="color: #111;">
											<?php if (isset($_SESSION['login_home'])) {
												$username = strstr($_SESSION['login_home'], "@", true);
												if ($username !== false) {
													echo $username;
												}
											} else {
												echo 'ADMIN';
											}

											?>
										</span>
									</a>
									<!-- Dropdown - User Information -->
									<div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
										<a class="dropdown-item" href="?quanli=userinfor">
											<i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
											Hồ sơ
										</a>

										<a class="dropdown-item" href="?quanli=dondamua">
											<i class="fas fa-truck fa-sm fa-fw mr-2 text-gray-400"></i>
											Đơn mua
										</a>
										<a class="dropdown-item" href="?quanli=thanhtoan">
											<i class="fas fa-credit-card fa-sm fa-fw mr-2 text-gray-400"></i>
											Thanh toán
										</a>

										<div class="dropdown-divider"></div>
										<a class="dropdown-item" href="?logout=1">
											<i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
											Đăng xuất
										</a>
									</div>
								</div>
							</nav>
						<?php else : ?>
							<button class="btn w3view-cart" onclick="alert('Vui lòng đăng nhập trước khi sử dụng chức năng này.');">
								<i class="fas fa-user"></i>
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- shop locator (popup) -->
<!-- //header-bottom -->
<!-- navigation -->

<style>
	/* --- HEADER CZUKA SHOP --- */
	/* Toàn bộ header */
	.header-bot {
		background: linear-gradient(to right, #f8ffff, #e9f8ff);
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		padding: 10px 0;
	}

	/* Logo CZUKA SHOP */
	.logo_agile img {
		width: 140px;
		height: auto;
		border-radius: 20px;
		transition: transform 0.4s ease, box-shadow 0.3s ease;
		box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
	}

	.logo_agile img:hover {
		transform: scale(1.05);
		box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
	}

	/* Thanh tìm kiếm */
	.agileits_search input {
		border-radius: 50px;
		border: 2px solid #0dcaf0;
		padding: 8px 20px;
		font-size: 15px;
		transition: 0.3s;
	}

	.agileits_search input:focus {
		box-shadow: 0 0 8px #0dcaf0;
		outline: none;
	}

	.agileits_search button {
		border-radius: 50px;
		background: linear-gradient(90deg, #00c6ff, #0072ff);
		color: #fff;
		padding: 8px 25px;
		font-weight: bold;
		transition: 0.3s;
	}

	.agileits_search button:hover {
		background: linear-gradient(90deg, #0072ff, #00c6ff);
		transform: translateY(-1px);
	}

	.w3view-cart,
	.user-btn {
		background: #fff;
		border: none;
		border-radius: 50%;
		width: 45px;
		height: 45px;
		display: flex;
		align-items: center;
		justify-content: center;
		position: relative;
		transition: 0.3s;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
	}

	/* Icon bên trong */
	.w3view-cart i,
	.user-btn i {
		color: #111;
		font-size: 20px;
		transition: 0.3s;
	}

	/* Hiệu ứng hover */
	.w3view-cart:hover,
	.user-btn:hover {
		background: #00c6ff;
		transform: translateY(-2px);
	}

	.w3view-cart:hover i,
	.user-btn:hover i {
		color: #00ffff;
	}

	/* Số lượng sản phẩm trong giỏ */
	.quantity_cart {
		position: absolute;
		top: 2px;
		right: 4px;
		background: red;
		color: white;
		font-size: 12px;
		font-weight: bold;
		padding: 2px 5px;
		border-radius: 50%;
		min-width: 18px;
		text-align: center;
		line-height: 1;
		border: 2px solid #fff;
	}

	/* Bỏ nền đen cũ */
	.btn.w3view-cart {
		background: transparent;
		box-shadow: none;
	}

	/* Đảm bảo không bị dính margin/padding thừa */
	.top_nav_right button {
		margin: 0;
		padding: 0;
	}

	/* --- Dòng neon trên cùng --- */
	.shop-intro-neon {
		font-weight: bold;
		color: #fff;
		font-size: 16px;
		text-transform: uppercase;
		animation: neon-blink 1.5s infinite alternate;
		text-shadow: 0 0 8px #00e6e6, 0 0 16px #00ffff;
	}

	/* Hiệu ứng neon */
	@keyframes neon-blink {

		0%,
		100% {
			text-shadow: 0 0 8px #00e6e6, 0 0 16px #00ffff;
			color: #fff;
		}

		50% {
			text-shadow: none;
			color: #ddd;
		}
	}

	/* Làm cho ô user khớp màu với nền header */
	nav.navbar.bg-white {
		background-color: #EBF9FF !important;
		/* hoặc cùng màu nền header */
		box-shadow: none !important;
		/* bỏ bóng đổ trắng */
		border-radius: 10px;
		/* tùy chọn: bo góc mềm hơn */
		padding: 4px 10px;
	}

	/* Màu chữ và icon user */
	#userDropdown span {
		color: #111 !important;
		font-weight: 500;
	}

	#userDropdown img {
		border: 2px solid #e0f0ff;
		/* viền nhẹ cho hợp màu nền */
	}
</style>