<?php
session_start();

// --- 1. KONEKSI DATABASE ---


if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 2. AMBIL ID MATERI DARI URL ---
$id_materi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$materi = null;

if ($id_materi > 0) {
    $stmt = $conn->prepare("SELECT * FROM materi_fr WHERE id = ?");
    $stmt->bind_param("i", $id_materi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $materi = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $materi ? htmlspecialchars($materi['judul']) . " - Deutsch With Elroy" : "Materi Tidak Ditemukan"; ?>
    </title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
   <style>
        /* --- ANIMASI MUNCUL PERLAHAN --- */
        @keyframes smoothFadeIn {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes bgFade {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        /* TEMA PRANCIS & PARIS BACKGROUND */
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #e0eaff; /* Trik 1: Warna cadangan biru lembut untuk cegah kilat putih */
            background-image: url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0; 
            padding: 60px 20px; 
            color: #2c3e50;
            animation: bgFade 0.4s ease-in-out; /* Trik 2: Transisi background mulus */
        }

        /* --- UI/UX: Reading Progress Bar --- */
        .progress-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 1000;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, #0055A4, #EF4135); 
            width: 0%;
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        /* Container Artikel Bergaya Medium/Notion */
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: #ffffff; 
            border-radius: 20px;
            padding: 50px 60px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            position: relative;
            animation: smoothFadeIn 0.5s ease-out forwards; /* Trik 3: Konten meluncur naik perlahan */
        }

        /* Tombol Kembali Modern */
        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 8px 20px;
            background: #f8f9fa;
            color: #636e72;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            margin-bottom: 40px;
        }

        .btn-back:hover {
            background: #e0eaff;
            color: #0055A4;
            transform: translateX(-5px);
        }

        /* Header Materi */
        .materi-header {
            margin-bottom: 40px;
            position: relative;
        }

        .materi-title { 
            color: #1a1a1a; 
            margin-top: 0;
            margin-bottom: 20px; 
            font-weight: 700;
            font-size: 2.8rem;
            line-height: 1.3;
            letter-spacing: -0.5px;
        }

        /* Aksen Bendera Prancis di bawah judul */
        .french-accent-line {
            height: 6px;
            width: 80px;
            background: linear-gradient(to right, #0055A4 33%, #f1f2f6 33%, #f1f2f6 66%, #EF4135 66%);
            border-radius: 3px;
            margin-bottom: 25px;
        }

        .materi-meta {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .badge {
            background: #f1f2f6;
            color: #2d3436;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .badge-level {
            background: #e0eaff;
            color: #0055A4;
        }

        /* --- UI/UX: Area Konten Bacaan --- */
        .materi-content {
            font-family: 'Lora', serif; 
            font-size: 1.2rem;
            line-height: 2;
            color: #34495e;
        }

        .text-main p {
            margin-bottom: 25px;
        }

        /* --- UI/UX: Kotak Highlight TRICK BELAJAR --- */
        .trick-box {
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            border-left: 6px solid #f1c40f; 
            border-radius: 0 16px 16px 0;
            padding: 25px 30px;
            margin: 40px 0;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
            font-family: 'Poppins', sans-serif; 
        }

        .trick-header {
            font-size: 1.1rem;
            font-weight: 700;
            color: #d35400;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trick-body {
            font-size: 1.05rem;
            color: #2c3e50;
            line-height: 1.8;
        }

        /* Responsif untuk HP */
        @media (max-width: 768px) {
            body { padding: 20px 10px; }
            .container { padding: 40px 25px; border-radius: 16px; }
            .materi-title { font-size: 2rem; }
            .materi-content { font-size: 1.1rem; }
            .trick-box { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="progress-container">
    <div class="progress-bar" id="myBar"></div>
</div>

<div class="container">
    
    <?php if ($materi): ?>
        
        <a href="lihat_materi_fr.php?level=<?php echo urlencode($materi['level']); ?>" class="btn-back">
            ← Revenir (Kembali)
        </a>

        <div class="materi-header">
            <h1 class="materi-title"><?php echo htmlspecialchars($materi['judul']); ?></h1>
            <div class="french-accent-line"></div>
            <div class="materi-meta">
                <span class="badge badge-level">Niveau <?php echo htmlspecialchars($materi['level']); ?></span>
                <span class="badge"><?php echo htmlspecialchars($materi['kategori']); ?></span>
            </div>
        </div>

        <div class="materi-content">
            <?php 
                $isi_mentah = htmlspecialchars($materi['isi']);
                
                // LOGIKA UI/UX: Memisahkan teks biasa dengan "Trick Belajar" agar tampil di kotak khusus
                if (strpos($isi_mentah, '💡 TRICK BELAJAR:') !== false) {
                    $parts = explode('💡 TRICK BELAJAR:', $isi_mentah);
                    $teks_utama = nl2br(trim($parts[0]));
                    $teks_trick = nl2br(trim($parts[1]));
                    
                    echo "<div class='text-main'>{$teks_utama}</div>";
                    echo "
                    <div class='trick-box'>
                        <div class='trick-header'>💡 Astuce (Trik Belajar)</div>
                        <div class='trick-body'>{$teks_trick}</div>
                    </div>";
                } else {
                    // Jika tidak ada kata trick belajar, tampilkan normal
                    echo "<div class='text-main'>" . nl2br($isi_mentah) . "</div>";
                }
            ?>
        </div>

    <?php else: ?>
        
        <div class="not-found" style="text-align:center; padding: 50px;">
            <h1 style="color:#d63031; font-size: 2.5rem; margin-bottom: 10px;">Materi Tidak Ditemukan</h1>
            <p style="color:#636e72; font-size: 1.1rem; margin-bottom: 30px;">Maaf, materi yang kamu cari mungkin sudah dihapus atau ID tidak valid.</p>
            <a href="materi_fr.php" class="btn-back">Kembali ke Pilihan Level</a>
        </div>

    <?php endif; ?>

</div>

<script>
    window.onscroll = function() {myFunction()};

    function myFunction() {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var scrolled = (winScroll / height) * 100;
        document.getElementById("myBar").style.width = scrolled + "%";
    }
</script>

</body>
</html>

<?php
$conn->close();
?>
