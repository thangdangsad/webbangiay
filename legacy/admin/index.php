<?php
    include('../db/connect.php');
    session_start();
    include('./common/checkLogin.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('./common/head.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        /* Giảm kích thước tổng thể các biểu đồ */
canvas {
    max-height: 220px !important; /* Giới hạn chiều cao */
}

/* Riêng chart tổng quan có thể nhỏ hơn */
#chartTongQuan {
    max-height: 180px !important;
}

/* Đảm bảo card không chiếm quá nhiều chỗ */
.card-body {
    padding: 0.75rem !important;
}

/* Tiêu đề nhỏ gọn hơn */
.card-header h6 {
    font-size: 0.95rem;
}

/* Các phần cách nhau ít hơn */
.row.mb-4 {
    margin-bottom: 1rem !important;
}

    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('./common/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('./common/topbar.php'); ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

                <?php
                    $year = date("Y");
                    $sql_total_year = "
                        SELECT SUM(tongDoanhThu) AS tongnam
                        FROM donhang
                        WHERE YEAR(ngayDatHang) = '$year' AND trangThai = 1
                    ";
                    $res_total_year = mysqli_query($mysqli, $sql_total_year);
                    $row_total_year = mysqli_fetch_assoc($res_total_year);
                    $tongnam = $row_total_year['tongnam'] ?? 0;

                    $month = date("m");
                    $sql_total_month = "
                        SELECT SUM(tongDoanhThu) AS tongthang
                        FROM donhang
                        WHERE YEAR(ngayDatHang) = '$year' 
                          AND MONTH(ngayDatHang) = '$month' 
                          AND trangThai = 1
                    ";
                    $res_total_month = mysqli_query($mysqli, $sql_total_month);
                    $row_total_month = mysqli_fetch_assoc($res_total_month);
                    $tongthang = $row_total_month['tongthang'] ?? 0;
                ?>

                <!-- Tổng quan -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card shadow h-100 py-2">
                            <div class="card-body">
                                <canvas id="chartTongQuan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doanh thu theo tháng -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    Doanh thu theo tháng (<?php echo date("Y"); ?>)
                                    <?php
                                        echo " - Tháng này: " . number_format($tongthang, 0, ',', '.') . " VND / Cả năm: " . number_format($tongnam, 0, ',', '.') . " VND";
                                    ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartDoanhThu"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top sản phẩm -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-success">Top 5 sản phẩm bán chạy (tháng <?php echo date("m"); ?>)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartSanPham"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phân loại khách hàng -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-warning">Phân loại khách hàng</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartKhachHang"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top khách hàng -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-danger">Khách hàng mua nhiều sản phẩm nhất / ít nhất</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTopKhachHang"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php include('./common/footer.php'); ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<?php include('./common/modal.php'); ?>

<?php
    // Doanh thu theo tháng
    $year = date("Y");
    $sql = "
        SELECT MONTH(ngayDatHang) AS thang, SUM(tongDoanhThu) AS doanhthu
        FROM donhang
        WHERE YEAR(ngayDatHang) = '$year' AND trangThai = 1
        GROUP BY MONTH(ngayDatHang)
        ORDER BY thang ASC
    ";
    $res = mysqli_query($mysqli, $sql);
    $doanhthu_labels = [];
    $doanhthu_data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $doanhthu_labels[] = "Tháng " . $row['thang'];
        $doanhthu_data[] = $row['doanhthu'];
    }

    // Top 5 sản phẩm bán chạy
    $sql = "
        SELECT p.product_name, SUM(dh.soluong) AS tong_soluong
        FROM donhang dh
        JOIN product p ON dh.product_id = p.product_id
        WHERE MONTH(dh.ngayDatHang) = MONTH(CURRENT_DATE())
          AND YEAR(dh.ngayDatHang) = YEAR(CURRENT_DATE())
          AND dh.trangThai = 1
        GROUP BY p.product_id
        ORDER BY tong_soluong DESC
        LIMIT 5
    ";
    $res = mysqli_query($mysqli, $sql);
    $sp_labels = [];
    $sp_data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $wrapped = wordwrap($row['product_name'], 15, "\n", true);
        $sp_labels[] = explode("\n", $wrapped);
        $sp_data[] = $row['tong_soluong'];
    }

    // Phân loại khách hàng + top/least khách hàng
    $sql = "
        SELECT c.customer_name, SUM(dh.soluong) AS tong_sp
        FROM donhang dh
        JOIN customer c ON c.customer_id = dh.customer_id
        WHERE dh.trangThai = 1
        GROUP BY c.customer_id
        ORDER BY tong_sp DESC
    ";
    $res = mysqli_query($mysqli, $sql);
    $customers = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $customers[$row['customer_name']] = $row['tong_sp'];
    }

    // Phân loại
    $vip = 0;
    $thuong = 0;
    foreach ($customers as $sp) {
        if ($sp > 10) $vip++; else $thuong++;
    }

    // Tách top 5 và bottom 5, không để trùng
    $top_labels = array_slice(array_keys($customers), 0, 5);
    $top_data   = array_slice(array_values($customers), 0, 5);

    $bottom_labels = array_slice(array_keys(array_reverse($customers)), 0, 5);
    $bottom_data   = array_slice(array_values(array_reverse($customers)), 0, 5);

    // Nếu có trùng giữa top và bottom -> loại bỏ bottom trùng
    $bottom_final_labels = [];
    $bottom_final_data = [];
    foreach ($bottom_labels as $i => $lbl) {
        if (!in_array($lbl, $top_labels)) {
            $bottom_final_labels[] = $lbl;
            $bottom_final_data[] = $bottom_data[$i];
        }
    }
?>

<script>
    // function autoFontSize(ctx, label) {
    //     if (Array.isArray(label)) return 11;
    //     if (label.length > 25) return 8;
    //     if (label.length > 15) return 10;
    //     return 12;
    // }

    // Tổng quan doanh thu
    new Chart(document.getElementById('chartTongQuan'), {
        type: 'bar',
        data: {
            labels: ['Doanh thu tháng <?php echo $month; ?>', 'Doanh thu cả năm <?php echo $year; ?>'],
            datasets: [{
                label: 'Doanh thu (VND)',
                data: [<?php echo $tongthang; ?>, <?php echo $tongnam; ?>],
                backgroundColor: ['rgba(0,123,255,0.6)', 'rgba(40,167,69,0.6)'],
                barThickness: 20,
                maxBarThickness: 25
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    formatter: (value) => value.toLocaleString("vi-VN") + " VND",
                    font: { weight: 'bold' }
                }
            },
            scales: {
                x: { ticks: { autoSkip: false } }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Doanh thu theo tháng
    new Chart(document.getElementById('chartDoanhThu'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($doanhthu_labels); ?>,
            datasets: [{
                label: 'Doanh thu (VND)',
                data: <?php echo json_encode($doanhthu_data); ?>,
                borderColor: 'blue',
                backgroundColor: 'rgba(0,123,255,0.2)',
                fill: true
            }]
        },
        options: { scales: { x: { ticks: { autoSkip: false } } } }
    });

    // Top sản phẩm
    new Chart(document.getElementById('chartSanPham'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($sp_labels); ?>,
            datasets: [{
                label: 'Số lượng bán',
                data: <?php echo json_encode($sp_data); ?>,
                backgroundColor: 'rgba(40,167,69,0.6)'
            }]
        },
        options: { plugins: { datalabels: { anchor: 'end', align: 'end' } }, scales: { x: { ticks: { autoSkip: false } } } },
        plugins: [ChartDataLabels]
    });

    // Phân loại khách hàng
    new Chart(document.getElementById('chartKhachHang'), {
        type: 'pie',
        data: {
            labels: ['Khách VIP (>10 SP)', 'Khách thường'],
            datasets: [{ data: [<?php echo $vip; ?>, <?php echo $thuong; ?>], backgroundColor: ['#ffc107', '#17a2b8'] }]
        }
    });

    // Top khách hàng
    new Chart(document.getElementById('chartTopKhachHang'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_merge($top_labels, $bottom_final_labels)); ?>,
            datasets: [{
                label: 'Số SP đã mua',
                data: <?php echo json_encode(array_merge($top_data, $bottom_final_data)); ?>,
                backgroundColor: 'rgba(220,53,69,0.6)'
            }]
        },
        options: { plugins: { datalabels: { anchor: 'end', align: 'end' } }, scales: { x: { ticks: { autoSkip: false } } } },
        plugins: [ChartDataLabels]
    });
</script>

</body>
</html>
