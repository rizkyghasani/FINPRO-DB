<?php
session_start();
include 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['login_as_owner'])) {
    header("Location: index.php");
    exit();
}

// Ambil filter dari input pengguna
$filter_supplier = isset($_GET['supplier']) ? $_GET['supplier'] : '';
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

// Query dasar
$query = "SELECT b.*, s.nama_supplier 
          FROM barangsuplier b 
          JOIN supplier s ON b.id_supplier = s.id_supplier 
          WHERE 1=1";

// Tambahkan filter jika input diisi
if (!empty($filter_supplier)) {
    $query .= " AND s.nama_supplier LIKE '%" . $conn->real_escape_string($filter_supplier) . "%'";
}

if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
    $query .= " AND DATE(b.created_at) BETWEEN '" . $conn->real_escape_string($filter_tanggal_awal) . "' 
                AND '" . $conn->real_escape_string($filter_tanggal_akhir) . "'";
}

$query .= " ORDER BY b.created_at DESC";
$result = $conn->query($query);

// Hitung total transaksi
$total_transaksi = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang Supplier - Toko Wahyu Listrik</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(81, 88, 94, 0.5);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #ffffff;
            text-align: center;
        }
        form {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }
        input, button {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            background-color: #007BFF;
            color: #fff;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .table-wrapper {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: gray;
            color: #ffffff;
            position: sticky;
            top: 0;
        }
        .total-transaksi {
            margin-top: 10px;
            text-align: right;
            font-size: 1.1em;
            font-weight: bold;
            color: #ffffff;
        }
        .back-button {
            display: inline-block;
            margin-top: 1rem;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 15px;
            background-color: #007BFF;
            border-radius: 5px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Data Barang Supplier</h1>
    <h2>Filter Data Barang</h2>
    <!-- Form Filter -->
    <form method="GET" action="" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 10px;">
            <input type="text" name="supplier" placeholder="Nama Supplier" value="<?php echo htmlspecialchars($filter_supplier); ?>">
            <input type="date" name="tanggal_awal" value="<?php echo htmlspecialchars($filter_tanggal_awal); ?>">
            <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($filter_tanggal_akhir); ?>">
        </div>
        <button type="submit">Filter</button>
    </form>
        <h2>Daftar Barang</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama Supplier</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Tanggal Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($barang = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($barang['nama_supplier']); ?></td>
                            <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                            <td><?php echo htmlspecialchars($barang['jumlah']); ?></td>
                            <td><?php echo htmlspecialchars($barang['harga_modal']); ?></td>
                            <td><?php echo htmlspecialchars($barang['created_at']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Tidak ada data yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Tampilkan Total Transaksi -->
        <?php if ($filter_supplier || ($filter_tanggal_awal && $filter_tanggal_akhir)): ?>
            <div class="total-transaksi">
                Total Transaksi: <?php echo $total_transaksi; ?>
            </div>
        <?php endif; ?>

        <a class="back-button" href="supplier.php">Kembali ke Manajemen Supplier</a>
    </div>
</body>
</html>
