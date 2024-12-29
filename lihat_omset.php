<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Omset</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background-color: #333; /* Warna gelap untuk latar belakang */
            color: #ffffff; /* Warna teks putih untuk kontras */
        }

        .container {
            max-width: 1500px;
            width: 90%;
            margin: 20px auto;
            padding: 30px;
            background-color: rgba(51, 51, 51, 0.8); /* Warna gelap dengan transparansi */
            backdrop-filter: blur(10px);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            overflow-x: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filter-form label {
            margin-right: 5px;
        }

        .filter-form input[type="date"],
        .filter-form select {
            padding: 8px;
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .filter-form button {
            padding: 8px 15px;
            background-color: rgba(226, 158, 32, 0.9);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-form button:hover {
            background-color: rgba(222, 200, 125, 0.9);
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .transaction-table th, 
        .transaction-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #444; /* Warna border lebih gelap */
            color: #ffffff;
        }

        .transaction-table th {
            background-color: rgba(226, 158, 32, 0.9);
        }

        .transaction-table tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .transaction-table tr:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .back-button, .print-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: rgba(226, 158, 32, 0.9);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover, .print-button:hover {
            background-color: rgba(222, 200, 125, 0.9);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            color: white;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.9);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.9);
        }

        .total-omset {
            font-size: 18px;
            color: #ffffff;
            background-color: rgba(226, 158, 32, 0.9);
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }

        /* CSS untuk scrollable table */
        .table-container {
            max-height: 400px; /* Atur tinggi maksimum sesuai kebutuhan */
            overflow-y: auto; /* Mengizinkan scroll vertikal */
            margin-top: 20px; /* Jarak antara filter dan tabel */
            padding-top: 20px; /* Tambahkan padding untuk memberi ruang pada judul */
        }

    </style>

</head>
<body>
    <div class="container">
        <h1>Lihat Omset</h1>

        <?php
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            $message = $_GET['message'] ?? '';
            $alertClass = ($status === 'success') ? 'alert-success' : 'alert-error';
            
            echo "<div class='alert {$alertClass}'>{$message}</div>";
        }
        ?>
        
        <!-- Form Filter -->
        <form class="filter-form" method="GET">
            <label for="start_date">Dari Tanggal:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
            
            <label for="end_date">Sampai Tanggal:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
            
            <button type="submit">Filter</button>
            <button type="button" onclick="window.location.href='lihat_omset.php'">Reset</button>
        </form>

        <div class="table-container">
            <?php
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);

            include 'config.php';

            // Build query with filters
            $query = "SELECT t.idtransaksi, t.tanggal_transaksi, t.total_harga,
                    GROUP_CONCAT(td.nama_barang, ' (', td.jumlah, ')' SEPARATOR ', ') as items
                    FROM transaksi t 
                    LEFT JOIN transaksi_detail td ON t.idtransaksi = td.idtransaksi 
                    WHERE 1=1";

            // Tambahkan filter
            if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                $start_date = $_GET['start_date'];
                $query .= " AND t.tanggal_transaksi >= '$start_date'";
            }

            if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
                $end_date = $_GET['end_date'];
                $query .= " AND t.tanggal_transaksi <= '$end_date'";
            }

            $query .= " GROUP BY t.idtransaksi ORDER BY t.tanggal_transaksi DESC, t.idtransaksi DESC";

            $result = mysqli_query($conn, $query);

            // Cek kesalahan query
            if (!$result) {
                die('Query Error: ' . mysqli_error($conn));
            }

            // Calculate total omset
            $totalOmset = 0;

            if ($result && mysqli_num_rows($result) > 0) {
                echo "<table class='transaction-table'>";
                echo "<tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Items</th>
                        <th>Total Harga</th>
                      </tr>";

                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['idtransaksi']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['tanggal_transaksi']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['items']) . "</td>";
                    echo "<td>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
                    echo "</tr>";

                    $totalOmset += $row['total_harga'];  // Add to total omset
                }

                echo "</table>";
            } else {
                echo "<p>Tidak ada omset yang ditemukan.</p>";
            }
            ?>
        </div>

        <div class="total-omset">
            <strong>Total Omset: </strong>Rp <?php echo number_format($totalOmset, 0, ',', '.'); ?>
        </div>

        <div>
            <a href="cetak_omset.php?start_date=<?php echo $_GET['start_date'] ?? ''; ?>&end_date=<?php echo $_GET['end_date'] ?? ''; ?>" class="print-button" target="_blank">Cetak Laporan Omset</a>
        </div>

        <a href="riwayat_transaksi.php" class="back-button">Kembali ke riwayat transaksi</a>
    </div>
</body>
</html>