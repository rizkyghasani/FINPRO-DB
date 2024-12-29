<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cek apakah karyawan atau owner sudah login
if (!isset($_SESSION['login_as_karyawan']) && !isset($_SESSION['login_as_owner'])) {
    echo "<script>
            alert('Anda harus masuk sebagai karyawan atau owner untuk melakukan pembelian.');
            window.location.href='index.php';
          </script>";
    exit();
}

// Ambil ID Karyawan dan Nama Karyawan dari session
$idkaryawan = $_SESSION['idkaryawan'] ?? null; // Ambil ID Karyawan dari session
$nama_karyawan = $_SESSION['nama_karyawan'] ?? null; // Ambil Nama Karyawan dari session

// Ambil ID Owner dan Nama Owner dari session
$idowner = $_SESSION['idowner'] ?? null; // Ambil ID Owner dari session
$nama_owner = $_SESSION['nama_owner'] ?? null; // Ambil Nama Owner dari session

// Pastikan ID Karyawan dan Nama Karyawan tidak kosong
if (empty($idkaryawan) && empty($idowner)) {
    echo "Karyawan atau Owner tidak terdaftar. Silakan login kembali.";
    exit;
}

// Cek apakah customer sudah diinput
if (!isset($_SESSION['idcustomer'])) {
    header("Location: process_customer.php");
    exit();
}

// Inisialisasi keranjang belanja jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = array();
}

include 'config.php';

// Ambil data produk dari database
$listrikProducts = [];
$atkProducts = [];

// Ambil produk listrik
$sqlListrik = "SELECT idlistrik, nama, harga FROM datalistrik";
$resultListrik = $conn->query($sqlListrik);
if ($resultListrik->num_rows > 0) {
    while ($row = $resultListrik->fetch_assoc()) {
        $listrikProducts[] = $row;
    }
}

// Ambil produk ATK
$sqlAtk = "SELECT idatk, nama, harga FROM dataatk";
$resultAtk = $conn->query($sqlAtk);
if ($resultAtk->num_rows > 0) {
    while ($row = $resultAtk->fetch_assoc()) {
        $atkProducts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian Barang - Toko Wahyu Listrik</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #ecf0f1;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('IMG_4283.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 2rem;
            background-color: rgba(81, 88, 94, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        h1, h2 {
            color: #ffffff;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        form {
            display: grid;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ffffff;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 1rem;
        }

        input:focus, select:focus {
            outline: none;
            border-color: rgba(226, 158, 32, 0.9);
            box-shadow: 0 0 5px rgba(226, 158, 32, 0.5);
        }

        button {
            background-color: rgba(226, 158, 32, 0.9);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: rgba(222, 200, 125, 0.9);
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background-color: rgba(226, 158, 32, 0.9);
            color: white;
            font-weight: 500;
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .back-button {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: rgba(140,  99, 24, 0.9);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
            text-align: center;
            margin-top: 2rem;
        }

        .back-button:hover {
            background-color: rgba(222, 200, 125, 0.9);
            transform: translateY(-2px);
        }

        .checkout-button {
            background-color: #27ae60;
            width: 100%;
            max-width: 200px;
            margin: 2rem auto;
            display: block;
        }

        .checkout-button:hover {
            background-color: #219a52;
        }

        .empty-cart {
            text-align: center;
            padding: 2rem;
            color: #ffffff;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 1rem;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Pembelian Barang</h1>
    <div class="customer-info">
        <p>ID Customer: <?php echo htmlspecialchars($_SESSION['idcustomer']); ?></p>
    </div>

   <!-- Form untuk menambah barang ke keranjang -->
   <form action="add_to_cart.php" method="post">
            <div class="form-group">
                <label for="jenis">Jenis Barang:</label>
                <select id="jenis" name="jenis" required>
                    <option value="listrik">Listrik</option>
                    <option value="atk">ATK</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Barang:</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            
            <div class="form-group">
                <label for="jumlah">Jumlah:</label>
                <input type="number" id="jumlah" name="jumlah" min="1" required>
            </div>
            
            <button type="submit">Tambah ke Keranjang</button>
        </form>

        <!-- Tampilkan Keranjang Belanja -->
        <?php if (!empty($_SESSION['keranjang'])): ?>
            <h2>Keranjang Belanja</h2>
            <table>
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    foreach ($_SESSION['keranjang'] as $key => $item): 
                        $subtotal = $item['harga'] * $item['jumlah'];
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['jenis']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama']); ?></td>
                            <td><?php echo htmlspecialchars($item['jumlah']); ?></td>
                            <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                            <td>
                                <form action="remove_from_cart.php" method="post">
                                    <input type="hidden" name="index" value="<?php echo $key; ?>">
                                    <button type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                        <td colspan="2"><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <form action="process_checkout.php" method="post">
                <input type="hidden" name="idkaryawan" value="<?php echo htmlspecialchars($idkaryawan); ?>">
                <input type="hidden" name="nama_karyawan" value="<?php echo htmlspecialchars($nama_karyawan); ?>">
                <button class="checkout-button" type="submit">Proses Checkout</button>
            </form>
        <?php else: ?>
            <p class="empty-cart">Keranjang belanja Anda kosong.</p>
        <?php endif; ?>
        
        <a class="back-button" href="<?php echo isset($_SESSION['login_as_karyawan']) ? 'dashboard_karyawan.php' : 'dashboard.php'; ?>">Kembali ke Dashboard</a>
    </div>
</body>
</html>

