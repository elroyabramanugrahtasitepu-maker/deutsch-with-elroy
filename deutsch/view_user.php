<?php
$host = "localhost"; // biasanya tetap localhost
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; // ganti dengan password MySQL user (bukan password login hosting!)
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

// Ambil ID dari URL
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM users WHERE id = $id");
$user = $result->fetch_assoc();

if (!$user) {
    die("Data peserta tidak ditemukan!");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Peserta - <?= $user['nama'] ?></title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 50px; }
        .detail-card { background: white; padding: 30px; max-width: 600px; margin: auto; border-radius: 8px; border-top: 5px solid #8e44ad; }
        .info-group { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        label { font-weight: bold; color: #7f8c8d; font-size: 12px; text-transform: uppercase; }
        p { font-size: 18px; margin: 5px 0; color: #2c3e50; }
        .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #8e44ad; }
    </style>
</head>
<body>
    <div class="detail-card">
        <h2>Profil Lengkap Peserta</h2>
        
        <div class="info-group">
            <label>Nama Lengkap</label>
            <p><?= $user['nama'] ?></p>
        </div>

        <div class="info-group">
            <label>Email</label>
            <p><?= $user['email'] ?></p>
        </div>

        <div class="info-group">
            <label>Nomor Telepon</label>
            <p><?= $user['telepon'] ?></p>
        </div>

        <div class="info-group">
            <label>Alamat</label>
            <p><?= !empty($user['alamat']) ? $user['alamat'] : '-' ?></p>
        </div>

        <a href="admin_dashboard.php" class="back-link">&larr; Kembali ke Dashboard</a>
    </div>
</body>
</html>