<?php
session_start();

// --- 1. KONEKSI DATABASE ---
$host = "localhost";
$user = "u960862048_roy";
$pass = "Caracter_Cs321";
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 2. AMBIL DATA BERDASARKAN ID MATERI ---
if (!isset($_GET['id'])) {
    header("Location: pilih_level_jp.php");
    exit();
}

$id_materi = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM materi_jp WHERE id = ?");
$stmt->bind_param("i", $id_materi);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Materi tidak ditemukan!");
}

$materi = $result->fetch_assoc();
$warna = !empty($materi["warna_aksen"]) ? $materi["warna_aksen"] : '#ff9a9e'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $materi['judul']; ?> - </title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* TEMA JEPANG & SAKURA BACKGROUND */
        body { 
            font-family: 'Poppins', sans-serif; 
            background-image: url('https://images.unsplash.com/photo-1522383225653-ed111181a951?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0; 
            padding: 40px 20px; 
            color: #2d3436;
        }

        .container { 
            max-width: 900px; 
            margin: 0 auto; 
        }

        /* Navigasi Atas */
        .top-nav {
            margin-bottom: 20px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #636e72;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: <?php echo $warna; ?>;
            color: #fff;
            transform: translateX(-5px);
        }

        /* Kontainer Utama Bacaan */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 50px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-top: 5px solid <?php echo $warna; ?>;
        }

        .badge-level {
            display: inline-block;
            background: #ffe8ed;
            color: #d63031;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        h1 { 
            color: #2d3436; 
            margin-top: 0;
            margin-bottom: 10px; 
            font-weight: 700;
            font-size: 2.2rem;
            line-height: 1.2;
        }

        .materi-desc {
            color: #636e72;
            font-size: 1.1rem;
            border-bottom: 1px solid #dfe6e9;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        /* Styling Khusus Untuk Konten Pelajaran (Biar Rapi Otomatis) */
        .isi-pelajaran {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #2d3436;
        }

        .isi-pelajaran h2 {
            color: <?php echo $warna; ?>;
            font-size: 1.4rem;
            margin-top: 35px;
            margin-bottom: 15px;
            border-bottom: 2px dashed rgba(0,0,0,0.05);
            padding-bottom: 10px;
        }

        .isi-pelajaran ul {
            background: #fcfcfc;
            padding: 20px 20px 20px 40px;
            border-radius: 12px;
            border-left: 4px solid <?php echo $warna; ?>;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin: 20px 0;
        }

        .isi-pelajaran li {
            margin-bottom: 10px;
        }

        .isi-pelajaran p {
            margin-bottom: 20px;
        }

        /* Tombol Selesai */
        .btn-finish {
            display: block;
            text-align: center;
            margin-top: 50px;
            padding: 15px 30px;
            background: #d63031;
            color: #fff;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(214, 48, 49, 0.2);
        }

        .btn-finish:hover {
            background: #b33939;
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(214, 48, 49, 0.3);
        }

    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <a href="lihat_materi_jp.php?level=<?php echo $materi['level']; ?>" class="btn-back">← Kembali ke Daftar Modul</a>
    </div>

    <div class="content-card">
        <span class="badge-level">Level <?php echo $materi['level']; ?></span>
        <h1><?php echo $materi['judul']; ?></h1>
        <div class="materi-desc"><?php echo $materi['deskripsi']; ?></div>
        
        <div class="isi-pelajaran">
            <?php 
                // Menampilkan isi konten yang tersimpan di database
                echo $materi['konten']; 
            ?>
        </div>

        <a href="lihat_materi_jp.php?level=<?php echo $materi['level']; ?>" class="btn-finish">Tandai Selesai & Kembali 🎌</a>
    </div>
</div>

</body>
</html>