<?php
session_start();
// Kết nối database
$mysqli = new mysqli("localhost", "root", "", "webbangiaythethao2");

// Check connection
if ($mysqli->connect_errno) {
	echo "Không thể kết nối với MySQL: " . $mysqli->connect_error;
	exit();
}

?>

<!DOCTYPE html>
<html lang="zxx">

<head>
	<title>SHOP</title>
	<!-- Meta tag Keywords -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<meta name="keywords" content="SHOP" />
	<script>
		addEventListener("load", function() {
			setTimeout(hideURLbar, 0);
		}, false);

		function hideURLbar() {

			window.scrollTo(0, 1);
		}
	</script>
	<!-- //Meta tag Keywords -->

	<!-- Custom-Files -->
	<link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
	<!-- Bootstrap css -->
	<link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
	<!-- Main css -->
	<link rel="stylesheet" href="css/fontawesome-all.css">
	<!-- Font-Awesome-Icons-CSS -->
	<link href="css/popuo-box.css" rel="stylesheet" type="text/css" media="all" />
	<!-- pop-up-box -->
	<link href="css/menu.css" rel="stylesheet" type="text/css" media="all" />
	<!-- menu style -->
	<!-- //Custom-Files -->
	<link rel="stylesheet" href="css/about.css.css">
	<link rel="stylesheet" href="css/similarproduct.css">

	<!-- web fonts -->
	<link href="//fonts.googleapis.com/css?family=Lato:100,100i,300,300i,400,400i,700,700i,900,900i&amp;subset=latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese"
		rel="stylesheet">
	<!-- //web fonts -->



</head>

<body style="font-family: 'Roboto', sans-serif;">
	<!-- Frontend -->
	<?php
	include('./frontend/topbar.php');
	include('./frontend/menu.php');


	if (isset($_GET['quanli'])) {
		$temp = $_GET['quanli'];
	} else {
		$temp = '';
	}

	if ($temp == 'danhmuc') {
		include('./frontend/danhmuc.php');
	} elseif ($temp == 'chitietsp') {
		include('./frontend/chitietsp.php');
		include('./frontend/similarproduct.php');
	} elseif ($temp == 'giohang') {
		// include('./frontend/herobg.php');
		include('./frontend/cart.php');
	} elseif ($temp == 'dondamua') {
		include('./frontend/dondamua.php');
	} elseif ($temp == 'chitietdonmua') {
		include('./frontend/chitietdonmua.php');
	} elseif ($temp == 'userinfor') {
		include('./frontend/userinfor.php');
	} elseif ($temp == 'thanhtoan') {
		include('./frontend/thanhtoan.php');
	} elseif ($temp == 'camon') {
		include('./frontend/camon.php');
	} elseif ($temp == 'lienhe') {
		include('./frontend/lienhe.php');
	} elseif ($temp == 'huongdan') {
		include('./frontend/huongdan.php');
	} elseif ($temp == 'gioithieu') {
		include('./frontend/gioithieu.php');
	} elseif ($temp == 'tuyendung') {
		include('./frontend/tuyendung.php');
	} elseif ($temp == 'timkiem') {
		include('./frontend/timkiem.php');
	} elseif ($temp == 'doimatkhau') {
		include('./frontend/handleuser/doimatkhau.php');
	} elseif ($temp == 'quenmatkhau') {
		include('./frontend/handleuser/quenmatkhau.php');
	} elseif ($temp == 'suathongtin') {
		include('./frontend/handleuser/edit_user.php');
	} elseif ($temp == 'nhapmaxacnhan') {
		include('./frontend/handleuser/reset_code.php');
	} else {
		include('./frontend/popup_discount.php');
		include('./frontend/slider.php');
		include('./frontend/home.php');
	}
	include('./frontend/footer.php');

	?>





	<!-- Javascript :)) -->

	<!-- js-files -->
	<!-- jquery -->
	<script src="js/jquery-2.2.3.min.js"></script>
	<!-- //jquery -->

	<!-- nav smooth scroll -->
	<script>
		$(document).ready(function() {
			$(".dropdown").hover(
				function() {
					$('.dropdown-menu', this).stop(true, true).slideDown("fast");
					$(this).toggleClass('open');
				},
				function() {
					$('.dropdown-menu', this).stop(true, true).slideUp("fast");
					$(this).toggleClass('open');
				}
			);
		});
	</script>
	<!-- //nav smooth scroll -->

	<!-- popup modal (for location)-->
	<script src="js/jquery.magnific-popup.js"></script>
	<script>
		$(document).ready(function() {
			$('.popup-with-zoom-anim').magnificPopup({
				type: 'inline',
				fixedContentPos: false,
				fixedBgPos: true,
				overflowY: 'auto',
				closeBtnInside: true,
				preloader: false,
				midClick: true,
				removalDelay: 300,
				mainClass: 'my-mfp-zoom-in'
			});

		});
	</script>
	<!-- //popup modal (for location)-->

	<!-- cart-js -->
	<!-- <script src="js/minicart.js"></script> -->
	<script>
		paypals.minicarts.render(); //use only unique class names other than paypals.minicarts.Also Replace same class name in css and minicart.min.js

		paypals.minicarts.cart.on('checkout', function(evt) {
			var items = this.items(),
				len = items.length,
				total = 0,
				i;

			// Count the number of each item in the cart
			for (i = 0; i < len; i++) {
				total += items[i].get('quantity');
			}

			if (total < 3) {
				alert('The minimum order quantity is 3. Please add more to your shopping cart before checking out');
				evt.preventDefault();
			}
		});
	</script>
	<!-- //cart-js -->

	<!-- imagezoom -->
	<script src="js/imagezoom.js"></script>
	<!-- //imagezoom -->

	<!-- flexslider -->
	<link rel="stylesheet" href="css/flexslider.css" type="text/css" media="screen" />

	<script src="js/jquery.flexslider.js"></script>
	<script>
		// Can also be used with $(document).ready()
		$(window).load(function() {
			$('.flexslider').flexslider({
				animation: "slide",
				controlNav: "thumbnails"
			});
		});
	</script>
	<!-- //FlexSlider-->

	<!-- password-script -->
	<script>
		window.onload = function() {
			document.getElementById("password1").onchange = validatePassword;
			document.getElementById("password2").onchange = validatePassword;
		}

		function validatePassword() {
			var pass2 = document.getElementById("password2").value;
			var pass1 = document.getElementById("password1").value;
			if (pass1 != pass2)
				document.getElementById("password2").setCustomValidity("Passwords Don't Match");
			else
				document.getElementById("password2").setCustomValidity('');
			//empty string means no validation error
		}
	</script>
	<!-- //password-script -->

	<!-- scroll seller -->
	<script src="js/scroll.js"></script>
	<!-- //scroll seller -->

	<!-- smoothscroll -->
	<script src="js/SmoothScroll.min.js"></script>
	<!-- //smoothscroll -->

	<!-- start-smooth-scrolling -->
	<script src="js/move-top.js"></script>
	<script src="js/easing.js"></script>
	<script>
		jQuery(document).ready(function($) {
			$(".scroll").click(function(event) {
				event.preventDefault();

				$('html,body').animate({
					scrollTop: $(this.hash).offset().top
				}, 1000);
			});
		});
	</script>
	<!-- //end-smooth-scrolling -->

	<!-- smooth-scrolling-of-move-up -->
	<script>
		$(document).ready(function() {
			/*
			var defaults = {
				containerID: 'toTop', // fading element id
				containerHoverID: 'toTopHover', // fading element hover id
				scrollSpeed: 1200,
				easingType: 'linear' 
			};
			*/
			$().UItoTop({
				easingType: 'easeOutQuart'
			});

		});
	</script>
	<!-- //smooth-scrolling-of-move-up -->

	<!-- for bootstrap working -->
	<script src="js/bootstrap.js"></script>
	<!-- //for bootstrap working -->
	<!-- //js-files -->
</body>

</html>