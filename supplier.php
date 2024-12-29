<?php
session_start();
include 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['login_as_owner'])) {
    header("Location: index.php");
    exit();
}

// Proses menambahkan supplier
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supplier'])) {
    $nama_supplier = $_POST['nama_supplier'];
    $alamat_supplier = $_POST['alamat_supplier'];
    $telepon_supplier = $_POST['telepon_supplier'];

    $query = "INSERT INTO supplier (nama_supplier, alamat_supplier, telepon_supplier) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $nama_supplier, $alamat_supplier, $telepon_supplier);
    if ($stmt->execute()) {
        $message = "<div class='message success'>Supplier berhasil ditambahkan.</div>";
    } else {
        $message = "<div class='message error'>Gagal menambahkan supplier. Silakan coba lagi.</div>";
    }
    $stmt->close();
}

// Ambil data supplier
$query = "SELECT * FROM supplier";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - Toko Wahyu Listrik</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: gray;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #45a049;
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
        .message {
            margin-bottom: 1rem;
            padding: 10px;
            border-radius: 5px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manajemen Supplier</h1>
        <?php echo $message; ?>
        <form method="POST">
            <div class="form-group">
                <label for="nama_supplier">Nama Supplier:</label>
                <input type="text" id="nama_supplier" name="nama_supplier" required>
            </div>
            <div class="form-group">
                <label for="alamat_supplier">Alamat Supplier:</label>
                <input type="text" id="alamat_supplier" name="alamat_supplier" required>
            </div>
            <div class="form-group">
                <label for="telepon_supplier">Telepon Supplier:</label>
                <input type="text" id="telepon_supplier" name="telepon_supplier" required>
            </div>
            <button type="submit" name="add_supplier" class="button">Tambah Supplier</button>
        </form>
        
        <h2>Daftar Supplier</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama Supplier</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($supplier = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($supplier['nama_supplier']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['alamat_supplier']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['telepon_supplier']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        

        <a class="button" href="data_barang_supplier.php">Lihat Data Barang Supplier</a> <!-- Tombol untuk mengarah ke halaman data barang supplier -->
        
        <a class="back-button" href="dashboard.php">Kembali ke Dashboard</a>
    </div>
</body>
</html>