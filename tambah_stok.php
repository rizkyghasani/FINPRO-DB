<?php
include 'config.php'; // Include koneksi ke database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $jenis = $_POST['jenis'];
    $idlistrik = $_POST['idlistrik']; 
    $jumlah = $_POST['jumlah'];

    // Ambil nama barang dari tabel yang sesuai
    if ($jenis == 'listrik') {
        $table = 'datalistrik'; 
        $id_field = 'idlistrik'; 
    } elseif ($jenis == 'atk') {
        $table = 'dataatk';
        $id_field = 'idatk'; 
    } else {
        echo "Jenis barang tidak valid.";
        exit;
    }

    // Query untuk mendapatkan stok saat ini
    $sql = "SELECT jumlah, nama FROM $table WHERE $id_field = ?"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idlistrik);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stokSekarang = $row['jumlah'];
        $nama_barang = $row['nama']; // Ambil nama barang

        // Query untuk memeriksa apakah nama barang sudah ada di tabel supplier
        $supplier_sql = "SELECT jumlah FROM barangsuplier WHERE jenis = ? AND nama_barang = ? ORDER BY created_at DESC LIMIT 1";
        $stmt_supplier = $conn->prepare($supplier_sql);
        $stmt_supplier->bind_param("ss", $jenis, $nama_barang);
        $stmt_supplier->execute();
        $supplier_result = $stmt_supplier->get_result();

        if ($supplier_result->num_rows > 0) {
            $supplier_row = $supplier_result->fetch_assoc();
            $jumlah_supplier = $supplier_row['jumlah'];

            // Validasi jumlah barang
            if ($jumlah > $jumlah_supplier) {
                echo "Jumlah yang diminta melebihi stok yang tersedia di supplier. Stok saat ini: $jumlah_supplier.";
                exit;
            }

            // Update stok barang dengan menambahkan jumlah baru
            $stokBaru = $stokSekarang + $jumlah;
            $updateSql = "UPDATE $table SET jumlah = ? WHERE $id_field = ?";
            $stmt_update = $conn->prepare($updateSql);
            $stmt_update->bind_param("is", $stokBaru, $idlistrik);

            if ($stmt_update->execute()) {
                echo "Stok barang berhasil ditambahkan.<br>";
            } else {
                echo "Error: " . $updateSql . "<br>" . $conn->error;
            }
        } else {
            echo "Tidak ada data di tabel supplier untuk barang ini.";
        }
    } else {
        echo "Barang tidak ditemukan.";
    }
    $conn->close();
} else {
    echo "Request method salah.";
}

// Tambahkan tombol untuk kembali ke halaman view.php dengan jenis barang yang sesuai
if ($jenis == 'listrik') {
    echo '<br><a href="view.php?jenis=listrik"><button>Kembali ke Stok Barang Listrik</button></a>';
} elseif ($jenis == 'atk') {
    echo '<br><a href="view.php?jenis=atk"><button>Kembali ke Stok Barang ATK</button></a>';
}
?>