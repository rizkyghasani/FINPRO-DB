<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['idcustomer'])) {
    $idcustomer = $_SESSION['idcustomer'];
    $idkaryawan = $_SESSION['idkaryawan'] ?? null; // Ambil ID Karyawan dari session
    $nama_karyawan = $_SESSION['nama_karyawan'] ?? null; // Ambil Nama Karyawan dari session
    $idowner = $_SESSION['idowner'] ?? null; // Menambahkan ID Owner
    $nama_owner = $_SESSION['nama_owner'] ?? null; // Menambahkan Nama Owner
    $jenis = $_POST['jenis'];
    $namaBarang = $_POST['nama'];
    $jumlah = $_POST['jumlah'];

    // Validasi dasar
    if (empty($jenis) || empty($namaBarang) || empty($jumlah)) {
        echo "Harap mengisi semua field.";
        exit;
    }

    if ($jenis === 'listrik') {
        $queryBarang = "SELECT idlistrik as id, nama, harga, jumlah FROM datalistrik WHERE nama = ?";
        $id_field = 'idlistrik';
    } else {
        $queryBarang = "SELECT idatk as id, nama, harga, jumlah FROM dataatk WHERE nama = ?";
        $id_field = 'idatk';
    }

    $stmt = $conn->prepare($queryBarang);
    $stmt->bind_param("s", $namaBarang);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $barang = $result->fetch_assoc();
        $idBarang = $barang['id'];
        $harga = $barang['harga'];
        $jumlahStok = $barang['jumlah'];

        if ($jumlah > $jumlahStok) {
            echo "Jumlah yang diminta melebihi stok yang tersedia. Stok saat ini: $jumlahStok.";
            exit;
        }

        $totalHarga = $harga * $jumlah;
        $idtransaksi = uniqid('TRX');
        $tanggalTransaksi = date('Y-m-d');
        $idjenis = ($jenis === 'listrik') ? 'J001' : 'J002';

        // Mulai transaksi
        $conn->begin_transaction();

        try {
            // Insert ke tabel transaksi
            $queryTransaksi = "INSERT INTO transaksi 
                (idtransaksi, tanggal_transaksi, idjenis, total_harga, 
                idcustomer, idkaryawan, nama_karyawan, idowner, nama_owner) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtTransaksi = $conn->prepare($queryTransaksi);
            $stmtTransaksi->bind_param(
                "sssdsssss", 
                $idtransaksi, $tanggalTransaksi, $idjenis, $totalHarga,
                $idcustomer, $idkaryawan, $nama_karyawan, $idowner, $nama_owner
            );
            $stmtTransaksi->execute();

            // Insert ke tabel transaksi_detail
            $queryDetail = "INSERT INTO transaksi_detail 
                (idtransaksi, idjenis, idbarang, nama_barang, jumlah, harga_satuan, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmtDetail = $conn->prepare($queryDetail);
            $stmtDetail->bind_param(
                "ssssidd",
                $idtransaksi, $idjenis, $idBarang, $namaBarang, $jumlah, $harga, $ totalHarga
            );
            $stmtDetail->execute();

            // Update stok barang
            $updateStok = "UPDATE " . ($jenis === 'listrik' ? 'datalistrik' : 'dataatk') . " SET jumlah = jumlah - ? WHERE $id_field = ?";
            $stmtUpdateStok = $conn->prepare($updateStok);
            $stmtUpdateStok->bind_param("is", $jumlah, $idBarang);
            $stmtUpdateStok->execute();

            // Commit transaksi
            $conn->commit();

            // Tampilkan informasi transaksi
            echo "<div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;'>";
            echo "<h2 style='color: #4CAF50; text-align: center;'>Transaksi Berhasil!</h2>";
            echo "<div style='margin: 15px 0;'>";
            echo "<p><strong>ID Transaksi:</strong> " . htmlspecialchars($idtransaksi) . "</p>";
            echo "<p><strong>Tanggal:</strong> " . htmlspecialchars($tanggalTransaksi) . "</p>";
            echo "<p><strong>ID Customer:</strong> " . htmlspecialchars($idcustomer) . "</p>";
            echo "<p><strong>ID Karyawan:</strong> " . htmlspecialchars($idkaryawan) . "</p>";
            echo "<p><strong>ID Owner:</strong> " . htmlspecialchars($idowner) . "</p>"; // Menampilkan ID Owner
            echo "<p><strong>Nama Barang:</strong> " . htmlspecialchars($namaBarang) . "</p>";
            echo "<p><strong>Jumlah:</strong> " . htmlspecialchars($jumlah) . "</p>";
            echo "<p><strong>Total Harga:</strong> Rp" . number_format($totalHarga, 2, ',', '.') . "</p>";
            echo "</div>";

            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<a href='pembelian.php' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Kembali ke Pembelian</a>";
            echo "</div>";
            echo "</div>";

            // Bersihkan session jika diperlukan
            unset($_SESSION['idcustomer']);
            // unset($_SESSION['namacustomer']); // Tidak perlu lagi

        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollback();
            echo "Gagal menyimpan transaksi: " . $e->getMessage();
        }

    } else {
        echo "Barang tidak ditemukan.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Metode request tidak valid atau data customer tidak tersedia.";
}
?> 