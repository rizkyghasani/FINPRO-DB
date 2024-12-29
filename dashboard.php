<?php
session_start();
// Cek apakah user sudah login
$login_as_karyawan = $_SESSION['login_as_karyawan'] ?? false;
$login_as_owner = $_SESSION['login_as_owner'] ?? false;

// Jika tidak login sebagai karyawan atau owner, arahkan ke halaman utama
if (!$login_as_karyawan && !$login_as_owner) {
    header("Location: index.php");
    exit;
}

// Koneksi ke database
include 'config.php';

// Ambil data penjualan dari tabel transaksi
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to the first day of the current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Default to today

// Query untuk total penjualan
$query_total = "SELECT SUM(total_harga) as total_penjualan, 
                       SUM(jumlah) as total_produk_terjual 
                FROM transaksi
                WHERE tanggal_transaksi BETWEEN ? AND ?";
$stmt_total = $conn->prepare($query_total);
$stmt_total->bind_param("ss", $start_date, $end_date);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$row_total = $result_total->fetch_assoc();

$total_penjualan = (float)$row_total['total_penjualan'];
$total_produk_terjual = (int)$row_total['total_produk_terjual'];

$stmt_total->close();

// Ambil data penjualan untuk grafik
// Ambil data penjualan untuk grafik
$query = "SELECT DATE(tanggal_transaksi) as tanggal, SUM(total_harga) as total 
          FROM transaksi 
          WHERE tanggal_transaksi BETWEEN ? AND ? 
          GROUP BY DATE(tanggal_transaksi) 
          ORDER BY tanggal";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$data = []; // Array asosiatif untuk menyimpan total penjualan berdasarkan tanggal

while ($row = $result->fetch_assoc()) {
    $data[$row['tanggal']] = (float)$row['total']; // Simpan total penjualan berdasarkan tanggal
}

// Tambahkan total penjualan pada tanggal akhir ke dalam array totals
$total_penjualan_hari_ini = 0;
$query_total_hari_ini = "SELECT SUM(total_harga) as total_penjualan_hari_ini 
                         FROM transaksi 
                         WHERE tanggal_transaksi = ?";
$stmt_total_hari_ini = $conn->prepare($query_total_hari_ini);
$stmt_total_hari_ini->bind_param("s", $end_date);
$stmt_total_hari_ini->execute();
$result_total_hari_ini = $stmt_total_hari_ini->get_result();
$row_total_hari_ini = $result_total_hari_ini->fetch_assoc();
$total_penjualan_hari_ini = (float)$row_total_hari_ini['total_penjualan_hari_ini'];

// Tambahkan tanggal akhir dan total penjualan hari ini ke dalam array
$data[$end_date] = $total_penjualan_hari_ini;

// Siapkan array untuk grafik
$dates = array_keys($data); // Ambil semua tanggal
$totals = array_values($data); // Ambil semua total penjualan

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Wahyu Listrik</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #2c3e50;
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    .sidebar {
        width: 220px;
        background-color: #1a1d23;
        padding-top: 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        color: #ecf0f1;
    }

    .sidebar a {
        padding: 15px;
        text-decoration: none;
        color: #ecf0f1;
        font-size: 16px;
        display: block;
        margin-bottom: 10px;
        transition: 0.3s;
    }

    .sidebar a:hover {
        background-color: #2c3e50;
    }

    .main-content {
        margin-left: 240px;
        padding: 20px;
        flex: 1;
    }

    .header {
        display: flex;
        justify-content: space-between;
        background-color: #1a1d23;
        padding: 10px 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
        font-size: 24px;
        margin: 0;
        color: #ff9900;
    }

    .header .user-info {
        display: flex;
        align-items: center;
    }

    .header .user-info img {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .stats {
        display: flex;
        justify-content: space-between;
        margin-top: 20px; /* Menambahkan jarak antara header dan stats */
        margin-bottom: 20px;
    }

    .card {
        background: #1a1d23;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        flex: 1;
        margin: 0 10px;
    }

    .card h3 {
        margin: 10px 0;
        font-size: 20px;
        color: #ff9900;
    }

    .card p {
        color: #ecf0f1;
        font-size: 18px;
    }

    .chart-container {
        background: #1a1d23;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .menu-container {
        background-color: #1a1d23;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        /* margin-top: 20px; */
    }

    .menu-button {
        background-color: #ff9900;
        color: #1a1d23;
        border: none;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
    }

    .menu-button:hover {
        background-color: #ff9900;
    }

    #myChart {
        width: 100%;
        height: 400px;
    }
</style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 style="text-align: center; color: white;">Toko Wahyu Listrik</h2>
        <a href="index.php">Home</a>
        <a href="input.php">Input</a>
        <a href="form_pembelian.php">Beli</a>
        <a href="view.php?jenis=listrik">Listrik</a>
        <a href="view.php?jenis=atk">ATK</a>
        <a href="riwayat_transaksi.php">Riwayat Transaksi</a>
        <a href="supplier.php">Manajemen Supplier</a>
        <a href="restock.php">Transaksi Supplier</a>
        <a href="input_karyawan.php">Input Karyawan</a>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard Toko Wahyu Listrik</h1>
            <div class="user-info">
                <img src="IMG_4283.jpg" alt="User  ">
                <p style="color: orange;">ADMIN</p>
            </div>
        </div>

        <div class="stats">
            <div class="card">
                <h3>Total Penjualan</h3>
                <p>Rp <?php echo number_format($total_penjualan, 2); ?></p>
            </div>
            <div class="card">
                <h3>Target Penjualan</h3>
                <input type="number" id="target_penjualan_input" value="100000">
                <button id="updateTargetButton" class="menu-button">Update Target</button>
            </div>
            <div class="card">
                <h3>Penjualan Tercapai</h3>
                <p><?php echo number_format(($total_penjualan / 100000) * 100, 2) . '%'; ?></p>
            </div>
            <div class="card">
                <h3>Produk Terjual</h3>
                <p><?php echo $total_produk_terjual; ?></p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="myChart"></canvas>
        </div>

        <div class="menu-container">
            <input type="date" id="start_date" class="date-picker" value="<?php echo $start_date; ?>">
            <input type="date" id="end_date" class="date-picker" value="<?php echo $end_date; ?>">
            <button id="filterButton" class="menu-button">Tampilkan Grafik</button>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        let myChart;

        function updateChart(dates, totals) {
    if (myChart) {
        myChart.destroy();
    }
    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Total Penjualan',
                data: totals,
                backgroundColor: 'rgba(52, 152, 219, 0.2)', // Area di bawah garis
                borderColor: 'rgba(52, 152, 219, 1)', // Warna garis
                borderWidth: 2, // Ketebalan garis
                pointBackgroundColor: 'rgba(52, 152, 219, 1)', // Warna titik
                pointBorderColor: '#fff', // Warna border titik
                pointBorderWidth: 2, // Ketebalan border titik
                pointRadius: 5, // Ukuran titik
                fill: false // Tidak mengisi area di bawah garis
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)', // Warna garis grid
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)', // Warna garis grid
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            }
        }
    });
}
        
        document.getElementById('filterButton').addEventListener('click', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            // Menggunakan fetch untuk mendapatkan data baru dan memperbarui grafik
            fetch(`dashboard.php?start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    updateChart(data.dates, data.totals);

        
                });
        });
        // Inisialisasi chart pertama kali
        updateChart(<?php echo json_encode($dates); ?>, <?php echo json_encode($totals); ?>);
    </script>
</body>
</html>