<?php
session_start();
// Kiểm tra nếu chưa đăng nhập (mà tự ý gõ đường dẫn dasdboard.php) thì sẽ trở về trang index đăng nhập
if (!isset($_SESSION['dangnhap'])) {
    header('Location:index.php');
} else {
    echo "<script> alert('Đăng nhập vào trang Admin thành công') </script>";
}
?>

<?php
//Đăng xuất
if (isset($_GET['login'])) {
    $dangxuat = $_GET['login'];
} else {
    $dangxuat = "";
}

if ($dangxuat == 'dangxuat') {
    // Hủy bỏ SESSION và đăng xuất
    unset($_SESSION['dangnhap']);
    header('Location:index.php');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="../css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
</head>

<body>
    <h1>dashboard</h1>
    <p>Xin chào <?php echo $_SESSION['dangnhap'] ?></p>
    <a href="?login=dangxuat">Đăng xuất</a>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <h2><a class="navbar-brand" href="#">Dashboard</a></h2>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="donhang.php">Đơn hàng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="xulydanhmuc.php?quanli=danhmuc">Danh mục</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="xulysanpham.php" >Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled">Khách hàng</a>
                    </li>
                   
                    
                </ul>
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Tìm kiếm" aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">Tìm kiếm</button>
                </form>
            </div>
        </div>
    </nav>
</body>

</html>