<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'config.php';

$message = "";

// Koneksi ke database
$conn = new mysqli('localhost', 'root', '080425', 'wahyulistrik');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Proses form jika metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek ketersediaan barang
    if (isset($_POST['check'])) {
        $nama_barang = $_POST['nama_barang'];
        $query = "SELECT harga_modal, jumlah FROM barangsuplier WHERE nama_barang = ? ORDER BY id_barang_suplier DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $nama_barang);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $harga_modal = $row['harga_modal'];
            $stok = $row['jumlah'];
            $message = "<div class='message success'>Harga Modal: Rp " . number_format($harga_modal, 0, ',', '.') . ", Stok: " . $stok . "</div>";
        } else {
            $message = "<div class='message error'>Barang tidak ditemukan.</div>";
        }
        $stmt->close();
    }

    // Proses untuk menambahkan barang
    if (isset($_POST['add'])) {
        $jenis = $_POST['jenis'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $harga = $_POST['harga'] ?? 0;
        $jumlah = $_POST['jumlah'] ?? 0;
        $harga_modal = $_POST['harga_modal'] ?? 0;

        // Validasi nama barang ada di tabel barangsuplier
        $validasi_query = "SELECT id_barang_suplier, jumlah FROM barangsuplier WHERE nama_barang = ? ORDER BY id_barang_suplier DESC LIMIT 1";
        $validasi_stmt = $conn->prepare($validasi_query);
        $validasi_stmt->bind_param("s", $nama);
        $validasi_stmt->execute();
        $validasi_result = $validasi_stmt->get_result();
        $barang_ada = $validasi_result->num_rows > 0;

        if ($barang_ada) {
            // Ambil stok yang ada di database
            $row = $validasi_result->fetch_assoc();
            $stok_db = $row['jumlah'];

            // Validasi harga, jumlah, dan stok
            if (!empty($jenis) && $harga > $harga_modal && $jumlah > 0 && $jumlah <= $stok_db) {
                // Query untuk menambahkan barang
                $insert_sql = ($jenis == 'listrik') ? "INSERT INTO datalistrik (nama, harga, jumlah) VALUES (?, ?, ?)" : "INSERT INTO dataatk (nama, harga, jumlah) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssi", $nama, $harga, $jumlah);
                if ($insert_stmt->execute()) {
                    $message = "<div class='message success'>Barang berhasil ditambahkan.</div>";
                } else {
                    $message = "<div class='message error'>Gagal menambahkan barang: " . $insert_stmt->error . "</div>";
                }
                $insert_stmt->close();
            } else {
                if ($jumlah > $stok_db) {
                    $message = "<div class='message error'>Jumlah yang diinput melebihi stok yang tersedia (Stok: " . $stok_db . ").</div>";
                } else {
                    $message = "<div class='message error'>Harga jual harus lebih tinggi dari harga modal dan jumlah harus valid.</div>";
                }
            }
        } else {
            $message = "<div class='message error'>Nama barang tidak ditemukan di tabel suplier. Silakan periksa kembali.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Barang</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .judul {
            color: #ffffff; /* Ganti dengan warna yang diinginkan */
        }
        .container {
            position: relative;
            z-index: 1;
            background-color: rgba(81, 88, 94, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            width: 100%;
            max-width: 550px;
            margin: auto; /* Center the container */
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .submit-button {
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-button:hover {
            background-color: #45a049;
        }
        .back-button {
            display: inline-block;
            margin-top: 1rem;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 15px;
            background-color: #007BFF; /* Blue */
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
        <h1 class="judul">Input Barang</h1>
        <?php echo $message; ?>
        <form id="barangForm" action="" method="post">
            <div class="form-group">
                <label for="nama_barang">Cek Ketersediaan Barang:</label>
                <input type="text" id="nama_barang" name="nama_barang" required>
                <button type="submit" name="check" class="submit-button">Cek</button>
            </div>
        </form>

        <form id="addBarangForm" action="" method="post">
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
                <label for="harga">Harga Barang:</label>
                <input type="number" id="harga" name="harga" required>
            </div>
            
            <div class="form-group">
                <label for="jumlah">Jumlah Barang:</label>
                <input type="number" id="jumlah" name="jumlah" required>
            </div>

            <div class="form-group">
                <label for="harga_modal">Harga Modal (dari cek):</label>
                <input type="number" id="harga_modal" name="harga_modal" readonly value="<?php echo $harga_modal ?? 0; ?>">
            </div>

            <button type="submit" name="add" class="submit-button">Tambah Barang</button>
            <a href="dashboard.php" class="back-button">Kembali ke dashboard</a> 
        </form>
    </div>
</body>
</html>
