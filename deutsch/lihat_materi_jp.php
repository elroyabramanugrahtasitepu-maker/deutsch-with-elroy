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

// --- 2. VALIDASI LEVEL DARI URL ---
$allowed_levels = ['N5', 'N4', 'N3', 'N2', 'N1'];
$level_aktif = isset($_GET['level']) ? strtoupper($_GET['level']) : '';

if (!in_array($level_aktif, $allowed_levels)) {
    header("Location: materi_jp.php"); 
    exit();
}

// --- 3. LOGIKA PAGINATION (HALAMAN) ---
$batas_per_halaman = 5; // Menampilkan 5 materi per halaman sesuai permintaan
$halaman_sekarang = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if($halaman_sekarang < 1) {
    $halaman_sekarang = 1;
}

$offset = ($halaman_sekarang - 1) * $batas_per_halaman;

// Hitung total materi untuk level ini agar tahu butuh berapa halaman
$stmt_count = $conn->prepare("SELECT COUNT(id) as total FROM materi_jp WHERE level = ?");
$stmt_count->bind_param("s", $level_aktif);
$stmt_count->execute();
$hasil_count = $stmt_count->get_result()->fetch_assoc();
$total_materi = $hasil_count['total'];

$total_halaman = ceil($total_materi / $batas_per_halaman);

// --- 4. AMBIL DATA MATERI SESUAI HALAMAN ---
$stmt = $conn->prepare("SELECT * FROM materi_jp WHERE level = ? ORDER BY id ASC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $level_aktif, $batas_per_halaman, $offset);
$stmt->execute();
$result_materi = $stmt->get_result();

$label_level = [
    'N5' => 'Dasar (Beginner)',
    'N4' => 'Dasar Atas (Upper Beginner)',
    'N3' => 'Menengah (Intermediate)',
    'N2' => 'Menengah Atas (Upper Intermediate)',
    'N1' => 'Mahir (Advanced)'
];
$label_aktif = $label_level[$level_aktif];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi <?php echo $level_aktif; ?> -</title>
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
            max-width: 1000px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 40px 50px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
        }

        /* Tombol Kembali */
        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: #ffffff;
            color: #636e72;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid #ffe8ed;
        }

        .btn-back:hover {
            background: #ffe8ed;
            color: #d63031;
            transform: translateX(-5px);
        }

        h1 { 
            text-align: center; 
            color: #d63031; 
            margin-top: 0;
            margin-bottom: 5px; 
            font-weight: 700;
            font-size: 2.2rem;
        }

        .subtitle {
            text-align: center;
            color: #636e72;
            margin-bottom: 30px;
            font-size: 1rem;
        }

        /* Layout Grid Materi */
        .materi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        /* Styling Card Materi */
        .materi-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            transition: all 0.4s ease;
            position: relative;
            border-left: 5px solid; 
            display: flex;
            flex-direction: column;
        }

        .materi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(214, 48, 49, 0.1);
        }

        .materi-title { 
            font-size: 1.25rem; 
            font-weight: 600; 
            color: #2d3436; 
            margin-bottom: 12px; 
            line-height: 1.3;
        }
        
        .materi-desc { 
            color: #636e72; 
            font-size: 0.95rem; 
            line-height: 1.6; 
            margin-bottom: 20px;
            flex-grow: 1; 
        }

        .btn-mulai {
            display: inline-block;
            text-align: center;
            padding: 10px 20px;
            background: #ffe8ed;
            color: #d63031;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-mulai:hover {
            background: #d63031;
            color: #ffffff;
        }

        /* --- STYLING UNTUK PAGINATION (TOMBOL HALAMAN) --- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: #ffffff;
            color: #636e72;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #ffe8ed;
        }

        .page-link:hover {
            background: #ffe8ed;
            color: #d63031;
            transform: translateY(-2px);
        }

        .page-link.active {
            background: #d63031;
            color: #ffffff;
            border-color: #d63031;
            box-shadow: 0 5px 15px rgba(214, 48, 49, 0.3);
            pointer-events: none; /* Tombol halaman aktif gak usah bisa diklik lagi */
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #b2bec3;
            grid-column: 1 / -1;
            background: rgba(255,255,255,0.5);
            border-radius: 16px;
            border: 1px dashed #dfe6e9;
        }

    </style>
</head>
<body>

<div class="container">
    <a href="materi_jp.php" class="btn-back">← Kembali ke Pilihan Level</a>

    <h1>Modul Level <?php echo $level_aktif; ?></h1>
    <p class="subtitle"><?php echo $label_aktif; ?> - Halaman <?php echo $halaman_sekarang; ?> dari <?php echo $total_halaman; ?></p>
    
    <div class="materi-grid">
        <?php
        if ($result_materi->num_rows > 0) {
            while($row = $result_materi->fetch_assoc()) {
                $warna = !empty($row["warna_aksen"]) ? $row["warna_aksen"] : '#ff9a9e'; 
                
                echo '
                <div class="materi-card" style="border-left-color: '.$warna.';">
                    <div class="materi-title">'.$row["judul"].'</div>
                    <div class="materi-desc">'.$row["deskripsi"].'</div>
                    <a href="'.$row["link"].'" class="btn-mulai">Mulai Belajar 🚀</a>
                </div>';
            }
        } else {
            echo '
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 10px;">🌸</div>
                <h3>Belum Ada Modul</h3>
                <p>Materi untuk halaman ini sedang dalam tahap penyusunan.</p>
            </div>';
        }
        ?>
    </div>

    <?php if ($total_halaman > 1): ?>
    <div class="pagination">
        <?php
        // Tombol Sebelumnya
        if ($halaman_sekarang > 1) {
            $prev = $halaman_sekarang - 1;
            echo '<a href="?level='.$level_aktif.'&halaman='.$prev.'" class="page-link" title="Sebelumnya">«</a>';
        }

        // Looping Angka Halaman
        for ($i = 1; $i <= $total_halaman; $i++) {
            $active_class = ($i == $halaman_sekarang) ? 'active' : '';
            echo '<a href="?level='.$level_aktif.'&halaman='.$i.'" class="page-link '.$active_class.'">'.$i.'</a>';
        }

        // Tombol Selanjutnya
        if ($halaman_sekarang < $total_halaman) {
            $next = $halaman_sekarang + 1;
            echo '<a href="?level='.$level_aktif.'&halaman='.$next.'" class="page-link" title="Selanjutnya">»</a>';
        }
        ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>