<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';

// Cek koneksi database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Proses form jika metode request adalah POST untuk generate ID Customer
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate ID Customer baru
    $query = "SELECT MAX(CAST(SUBSTRING(idcustomer, 5) AS UNSIGNED)) as max_id 
              FROM customer WHERE idcustomer LIKE 'CST-%'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        $_SESSION['error_message'] = "Error in query: " . mysqli_error($conn);
        header("Location: error_page.php");
        exit();
    } else {
        $row = mysqli_fetch_assoc($result);
        $last_id = $row['max_id'] ?? 0; // Jika null, mulai dari 0
        $next_id = $last_id + 1;
        $_SESSION['idcustomer'] = sprintf("CST-%03d", $next_id);
    }
}

// Cek jika keranjang tidak kosong
if (!empty($_SESSION['keranjang'])) {
    try {
        $conn->begin_transaction();

        $idtransaksi = uniqid('TRX');
        $tanggalTransaksi = date('Y-m-d');
        $idcustomer = $_SESSION['idcustomer'] ?? null;
        $idkaryawan = $_SESSION['idkaryawan'] ?? null;
        $nama_karyawan = $_SESSION['nama_karyawan'] ?? null; 
        $total_harga = 0;
        $total_jumlah = 0;

        // Hitung total harga dan jumlah dari keranjang
        foreach ($_SESSION['keranjang'] as $item) {
            $total_harga += $item['harga'] * $item['jumlah'];
            $total_jumlah += $item['jumlah'];
        }

        // Query untuk menyimpan transaksi utama
        $queryTransaksi = "INSERT INTO transaksi 
            (idtransaksi, tanggal_transaksi, total_harga, idcustomer, idkaryawan, nama_karyawan, jumlah) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmtTransaksi = $conn->prepare($queryTransaksi);
        $stmtTransaksi->bind_param("ssdsdss", $idtransaksi, $tanggalTransaksi, $total_harga, $idcustomer, $idkaryawan, $nama_karyawan, $total_jumlah);

        if (!$stmtTransaksi->execute()) {
            throw new Exception("Error executing transaction: " . $stmtTransaksi->error);
        }

        // Simpan detail transaksi
        foreach ($_SESSION['keranjang'] as $item) {
            $idjenis = ($item['jenis'] === 'listrik') ? 'J001' : 'J002';
            $subtotal = $item['harga'] * $item['jumlah'];
            
            // Generate ID unik untuk kolom 'id' di detail_transaksi
            $queryDetailID = "SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) as max_id 
                              FROM transaksi_detail WHERE id LIKE 'DT-%'";
            $resultDetailID = mysqli_query($conn, $queryDetailID);

            if (!$resultDetailID) {
                throw new Exception("Error generating detail ID: " . mysqli_error($conn));
            }

            $rowDetail = mysqli_fetch_assoc($resultDetailID);
            $last_detail_id = $rowDetail['max_id'] ?? 0; // Jika null, mulai dari 0
            $next_detail_id = $last_detail_id + 1;
            $id_detail = sprintf("DT-%03d", $next_detail_id);

            $queryDetail = "INSERT INTO transaksi_detail 
                (id, idtransaksi, idjenis, idbarang, nama_barang, jumlah, harga_satuan, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtDetail = $conn->prepare($queryDetail);
            if (!$stmtDetail) {
                throw new Exception("Error preparing detail statement: " . $conn->error);
            }

            $stmtDetail->bind_param(
                "sssssddd",
                $id_detail, $idtransaksi, $idjenis, $item['id'], $item['nama'], $item['jumlah'], $item['harga'], $subtotal
            );

            if (!$stmtDetail->execute()) {
                throw new Exception("Error executing detail: " . $stmtDetail->error);
            }

            // Update stok barang
            $table = ($item['jenis'] === 'listrik') ? 'datalistrik' : 'dataatk';
            $id_field = ($item['jenis'] === 'listrik') ? 'idlistrik' : 'idatk';
            $updateStok = "UPDATE $table SET jumlah = jumlah - ? WHERE $id_field = ?";
            
            $stmtUpdateStok = $conn->prepare($updateStok);
            if (!$stmtUpdateStok) {
                throw new Exception("Error preparing stock update: " . $conn->error);
            }

            $stmtUpdateStok->bind_param("is", $item['jumlah'], $item['id']);
            
            if (!$stmtUpdateStok->execute()) {
                throw new Exception("Error updating stock: " . $stmtUpdateStok->error);
            }
        }

        $conn->commit();

        // Hapus keranjang dan session customer
        unset($_SESSION['keranjang']);
        unset($_SESSION['idcustomer']);

        header("Location: transaksi_sukses.php?idtransaksi=" . urlencode($idtransaksi));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: error_page.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Keranjang belanja kosong!";
    header("Location: error_page.php");
    exit();
}

$conn->close();
?>
