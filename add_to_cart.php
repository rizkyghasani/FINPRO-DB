<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis'];
    $nama = $_POST['nama'];
    $jumlah = (int)$_POST['jumlah'];

    // Cek stok dan harga dari database
    $table = ($jenis == 'listrik') ? 'datalistrik' : 'dataatk';
    $query = "SELECT * FROM $table WHERE nama = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['jumlah'] >= $jumlah) {
            // Tambahkan ke keranjang
            $_SESSION['keranjang'][] = [
                'id' => $jenis == 'listrik' ? $row['idlistrik'] : $row['idatk'],
                'jenis' => $jenis,
                'nama' => $nama,
                'jumlah' => $jumlah,
                'harga' => $row['harga'],
                'stok_tersedia' => $row['jumlah']
            ];
            
            header("Location: pembelian.php");
            exit();
        } else {
            echo "<script>
                    alert('Stok tidak cukup');
                    window.location.href='pembelian.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Barang tidak ditemukan');
                window.location.href='pembelian.php';
              </script>";
    }
}
?>