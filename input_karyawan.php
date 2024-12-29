<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Karyawan Baru</title>
    <style>
        /* Gunakan style yang sama dengan index.php */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }

        .back-btn:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Input Karyawan Baru</h1>
        <form action="process_karyawan.php" method="post">
            <div class="form-group">
                <label for="idkaryawan">ID Karyawan:</label>
                <input type="text" id="idkaryawan" name="idkaryawan" required>
            </div>
            
            <div class="form-group">
                <label for="nama_karyawan">Nama Karyawan:</label >
                <input type="text" id="nama_karyawan" name="nama_karyawan" required>
            </div>
            
            <div class="form-group">
                <label for="gender">Jenis Kelamin:</label>
                <select id="gender" name="gender" required>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Simpan</button>
            <a href="dashboard.php" class="back-btn">Kembali ke Halaman Utama</a>
        </form>
    </div>
</body>
</html>