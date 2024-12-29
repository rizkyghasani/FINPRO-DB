<?php
include 'config.php';

$jenis = $_GET['jenis'];
$table = ($jenis == 'listrik') ? 'datalistrik' : 'dataatk';

$sql = "SELECT * FROM $table";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Stok Barang <?php echo ucfirst($jenis); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(81, 88, 94, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(81, 88, 94, 0.5); /* Mengganti latar belakang menjadi transparan */
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            color: #ffffff; /* Mengubah warna teks menjadi putih untuk kontras yang lebih baik */
        }

        th {
            background-color: rgba(226, 158, 32, 0.9); /* Warna header tabel */
            color: white; /* Warna teks header */
        }

        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.1); /* Warna latar belakang untuk baris genap */
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.2); /* Warna latar belakang saat hover */
        }

        .button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 2px;
            transition: all 0.3s ease;
        }

        .button-danger {
            background-color: #ff4444;
            color: white;
        }

        .button-danger:hover {
            background-color: #cc0000;
        }

        .button-primary {
            background-color: #4CAF50;
            color: white;
        }

        .button-primary:hover {
            background-color: #45a049;
        }

        input[type="number"] {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 80px;
            margin: 0 5px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
            align-items: center;
        }

        .back-button {
            display: block;
            width: fit-content;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lihat Stok Barang <?php echo ucfirst($jenis); ?></h1>
        
        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($jenis == 'listrik') {
                    $query = "SELECT * FROM datalistrik";
                } elseif ($jenis == 'atk') {
                    $query = "SELECT * FROM dataatk";
                } else {
                    echo "Jenis barang tidak valid.";
                    exit;
                }
                
                $result = $conn->query($query);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['nama']}</td>
                                <td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                                <td>{$row['jumlah']}</td>
                                <td class='action-buttons'>
                                    <form action='delete.php' method='post' style='display:inline;'>
                                        <input type='hidden' name='jenis' value='$jenis'>
                                        <input type='hidden' name='nama' value='{$row['nama']}'>
                                        <button type='submit' onclick=\"return confirm('Yakin ingin menghapus barang ini?');\" 
                                            class='button button-danger'>Hapus</button>
                                    </form>
                                    
                                    <form action='reduce.php' method='post' style='display:inline;'>
                                        <input type='hidden' name='jenis' value='$jenis'>
                                        <input type='hidden' name='nama' value='{$row['nama']}'>
                                        <label for='jumlah'>Kurangi:</label>
                                        <input type='number' name='jumlah' required min='1'>
                                        <button type='submit' class='button button-primary'>Kurangi Stok</button>
                                    </form>";

                                    echo "<form action='tambah_stok.php' method='post' style='display:inline;'>";
                                    echo "<input type='hidden' name='jenis' value='".htmlspecialchars($jenis)."'>";

                                    if ($jenis == 'listrik' && isset($row['idlistrik'])) {
                                        echo "<input type='hidden' name='idlistrik' value='".htmlspecialchars($row['idlistrik'])."'>";
                                    } elseif ($jenis == 'atk' && isset($row['idatk'])) {
                                        echo "<input type='hidden' name='idlistrik' value='".htmlspecialchars($row['idatk'])."'>";
                                    }
                                    
                                    echo "<label for='jumlah'>Tambah:</label>
                                        <input type='number' name='jumlah' required min='1'>
                                        <button type='submit' class='button button-primary'>Tambah Stok</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Tidak ada barang ditemukan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <!-- Tombol untuk kembali ke halaman utama -->
        <a href="dashboard.php" class="back-button">Kembali ke Menu Dashboard</a>
    </div>
</body>
</html>