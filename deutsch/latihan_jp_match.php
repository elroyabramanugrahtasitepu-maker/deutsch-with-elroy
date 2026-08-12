<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_pulau = 'match_n5'; // Identifier khusus untuk pulau Match

// Koneksi Database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// --- CEK TOTAL SOAL DI DATABASE ---
$query_total = $conn->query("SELECT COUNT(id) as total FROM latihan_jp_match WHERE level='N5'");
$row_total = $query_total->fetch_assoc();
$total_all_soal = $row_total['total'];
$limit = 10;

// --- PROSES CEK JAWABAN & POSISI RUTEMU ---
$show_result = false;
$benar = 0;
$salah = 0;
$pesan_hasil = "";
$lulus = false; // Syarat lulus: >= 8 Benar
$offset = 0;
$evaluasi_detail = []; 

// Fungsi untuk menyimpan posisi terakhir user ke database
function simpanJejak($conn, $user_id, $pulau, $offset) {
    $stmt = $conn->prepare("INSERT INTO user_log_pose (user_id, pulau, last_offset) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_offset = ?");
    $stmt->bind_param("isii", $user_id, $pulau, $offset, $offset);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jawaban'])) {
    $jawaban_user = $_POST['jawaban'];
    $show_result = true;
    
    $offset = isset($_POST['current_start']) ? (int)$_POST['current_start'] : 0;

    // Hitung benar dan salah (Untuk Match, jika ID Soal == ID Jawaban yang dipilih, maka Benar)
    $no_urut = 1;
    foreach ($jawaban_user as $id_soal => $jawaban) {
        if ($id_soal == $jawaban) {
            $benar++;
            $evaluasi_detail[$no_urut] = 'benar';
        } else {
            $salah++;
            $evaluasi_detail[$no_urut] = 'salah';
        }
        $no_urut++;
    }

    // SYARAT LULUS: Minimal 8 Benar
    if ($benar >= 8) {
        $lulus = true;
        $pesan_hasil = "Luar biasa Kapten! Pemasangan kosakata sangat presisi (Minimal 8 Tepat). Pelayaran bisa dilanjutkan!";
        $next_offset = $offset + $limit; 
        
        if ($next_offset < $total_all_soal) {
            simpanJejak($conn, $user_id, $nama_pulau, $next_offset);
        } else {
            simpanJejak($conn, $user_id, $nama_pulau, 0); 
        }
    } else {
        $pesan_hasil = "Awas karang! Banyak kosakata yang tertukar! Syarat minimum 8 tepat belum tercapai. Periksa kembali log pose-mu.";
        simpanJejak($conn, $user_id, $nama_pulau, $offset);
    }
} 
else {
    if (isset($_GET['start'])) {
        $offset = (int)$_GET['start'];
        simpanJejak($conn, $user_id, $nama_pulau, $offset);
    } 
    else {
        $stmt = $conn->prepare("SELECT last_offset FROM user_log_pose WHERE user_id = ? AND pulau = ?");
        $stmt->bind_param("is", $user_id, $nama_pulau);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $offset = $row['last_offset']; 
        }
        $stmt->close();
    }
}

if ($offset >= $total_all_soal && $total_all_soal > 0) {
    $offset = 0;
    simpanJejak($conn, $user_id, $nama_pulau, 0);
}

// --- AMBIL SOAL BERDASARKAN POSISI ---
$sql = "SELECT id, kata_jp, romaji, arti_id FROM latihan_jp_match WHERE level='N5' ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$soal = [];
$pilihan_kanan = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $soal[] = $row;
        // Simpan id dan teks untuk opsi dropdown (diambil dari 10 soal yang muncul saja)
        $pilihan_kanan[] = ['id' => $row['id'], 'teks' => $row['arti_id']]; 
    }
}
// Acak pilihan dropdown agar harus dicocokkan
shuffle($pilihan_kanan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wheel of Match | Grand Line Trials</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
<style>
    :root { 
        --map-paper: #E6C280;    
        --map-edge: #B88645;
        --ink-dark: #3E2723;     
        --ink-light: #5D4037;
        --ink-red: #B71C1C;      
        --ocean-blue: #0277BD;   
        --paper-light: #FDF8E2;
        --success-green: #2E7D32;
        --warning-orange: #E65100;
        --radius-lg: 8px; 
        --transition: all 0.3s ease; 
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Nunito', sans-serif; 
        background-color: var(--map-paper); 
        color: var(--ink-dark); 
        margin: 0; 
        background-image: 
            radial-gradient(circle at center, rgba(255,255,255,0.15) 0%, rgba(139,69,19,0.4) 100%),
            url("data:image/svg+xml,%3Csvg width='150' height='150' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.6' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23noise)' opacity='0.08'/%3E%3C/svg%3E");
        background-attachment: fixed;
    }

    .user-nav { display: flex; justify-content: space-between; padding: 15px 40px; background: rgba(230, 194, 128, 0.9); backdrop-filter: blur(5px); align-items: center; position: sticky; top: 0; z-index: 1000; border-bottom: 3px dashed var(--ink-dark); }
    .lobby-action a { color: var(--ink-dark); text-decoration: none; font-weight: 900; font-size: 1rem; transition: var(--transition); display: flex; align-items: center; gap: 8px; }
    .lobby-action a:hover { color: var(--ink-red); }

    header { padding: 30px 15px 15px; text-align: center; }
    .island-badge { display: inline-block; background: transparent; color: var(--ink-dark); font-size: 1.2rem; font-weight: 900; text-transform: uppercase; padding: 5px 20px; letter-spacing: 3px; margin-bottom: 10px; border-top: 2px solid var(--ink-dark); border-bottom: 2px solid var(--ink-dark); }
    .title-text { font-family: 'Lora', serif; font-size: 3rem; color: var(--ink-dark); margin: 0; font-weight: 700; text-shadow: 2px 2px 0px rgba(255,255,255,0.4); }
    .progress-info { font-weight: 800; color: var(--ink-light); margin-top: 10px; font-size: 1.1rem; }
    
    .back-container { width: 90%; max-width: 800px; margin: 0 auto 20px; display: flex; justify-content: flex-start; }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; background: transparent; color: var(--ink-dark); text-decoration: none; font-weight: 900; padding: 8px 15px; transition: var(--transition); border: 2px dashed var(--ink-dark); border-radius: 4px; }
    .btn-back:hover { background: var(--ink-dark); color: var(--map-paper); transform: translateX(-5px); }

    .quiz-container { width: 90%; max-width: 800px; margin: 0 auto 50px; background: var(--paper-light); border: 3px solid var(--ink-dark); box-shadow: 8px 8px 0px rgba(62, 39, 35, 0.8); border-radius: 4px; padding: 30px 40px; position: relative; }
    .quiz-container::before { content: '\f0c1'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 2.5rem; color: var(--ink-dark); text-shadow: 2px 2px 0px #fff; }

    /* --- STYLING KHUSUS MATCH (DROPDOWN) --- */
    .question-block { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px dashed var(--map-edge); gap: 20px; }
    .question-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .item-kiri { width: 45%; }
    .item-kiri .kanji { font-family: 'Lora', serif; font-size: 1.5rem; font-weight: 800; color: var(--ocean-blue); display: block; margin-bottom: 4px; text-shadow: 1px 1px 0px rgba(0,0,0,0.1); }
    .item-kiri .romaji { font-size: 1rem; color: var(--ink-light); font-weight: 700; letter-spacing: 0.5px; }
    
    .item-kanan { width: 50%; }

    select {
        width: 100%; padding: 12px 15px; border: 2px solid var(--ink-dark); border-radius: 4px; font-size: 1rem; font-family: 'Nunito', sans-serif;
        background-color: var(--map-paper); color: var(--ink-dark); cursor: pointer; transition: 0.3s; outline: none; font-weight: 700;
        box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05);
    }
    select:focus { border-color: var(--ocean-blue); background-color: var(--paper-light); }

    .btn-submit { display: block; width: 100%; margin-top: 40px; background: var(--ink-dark); color: var(--paper-light); border: 2px solid var(--ink-dark); padding: 15px; font-size: 1.2rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: var(--transition); border-radius: 4px; font-family: 'Nunito', sans-serif; }
    .btn-submit:hover { background: var(--ocean-blue); border-color: var(--ocean-blue); box-shadow: 0px 5px 15px rgba(2, 119, 189, 0.4); }

    .empty-state { text-align: center; padding: 40px; font-weight: 700; color: var(--ink-light); }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; color: var(--map-edge); }

    /* --- HASIL (RESULT BOX) --- */
    .result-box { margin-bottom: 40px; padding: 30px; border: 3px dashed var(--ink-dark); border-radius: 4px; text-align: center; background: rgba(255, 255, 255, 0.7); }
    .result-box.success { border-color: var(--success-green); background: rgba(46, 125, 50, 0.1); color: var(--success-green); }
    .result-box.failed { border-color: var(--warning-orange); background: rgba(230, 81, 0, 0.1); color: var(--warning-orange); }
    
    .result-box h2 { font-family: 'Lora', serif; font-size: 2rem; margin-top: 0; }
    .result-stats { display: flex; justify-content: center; gap: 30px; font-size: 1.5rem; font-weight: 900; margin: 15px 0; }
    .stat-benar { color: var(--success-green); }
    .stat-salah { color: var(--ink-red); }

    .evaluasi-container { margin: 20px 0; padding: 15px; background: rgba(255,255,255,0.9); border-radius: 8px; border: 1px solid #ccc; }
    .evaluasi-title { font-weight: 900; margin-bottom: 15px; color: var(--ink-dark); }
    .evaluasi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; }
    .eval-item { padding: 8px 5px; border-radius: 4px; font-weight: 800; font-size: 0.9rem; }
    .eval-item.benar { background: rgba(46, 125, 50, 0.15); color: var(--success-green); border: 1px solid var(--success-green); }
    .eval-item.salah { background: rgba(183, 28, 28, 0.15); color: var(--ink-red); border: 1px solid var(--ink-red); }
    
    .btn-action { display: inline-block; padding: 12px 25px; font-weight: 900; text-transform: uppercase; text-decoration: none; border-radius: 4px; transition: var(--transition); margin-top: 15px; border: 2px solid transparent; cursor: pointer; font-family: 'Nunito', sans-serif; }
    .btn-retry { background: transparent; border-color: var(--warning-orange); color: var(--warning-orange); }
    .btn-retry:hover { background: var(--warning-orange); color: white; }
    
    .btn-next { background: var(--success-green); border-color: var(--success-green); color: white; }
    .btn-next:hover { box-shadow: 0px 5px 15px rgba(46, 125, 50, 0.4); transform: translateY(-3px); }

    /* UI RESTART BOX KHUSUS TAMAT */
    .restart-box { margin-top: 25px; padding: 25px; border-top: 3px solid var(--success-green); }
    .select-rute { padding: 10px 15px; font-size: 1rem; border: 2px solid var(--ink-dark); border-radius: 4px; background: #fff; font-weight: bold; color: var(--ink-dark); margin: 15px 0; width: 100%; max-width: 300px; }
    .btn-restart { background: var(--ink-dark); color: #fff; }
    .btn-restart:hover { background: var(--ocean-blue); border-color: var(--ocean-blue); color: white; transform: scale(1.05); }

    @media (max-width: 768px) {
        .quiz-container { padding: 20px; }
        .title-text { font-size: 2.2rem; }
        .result-stats { gap: 15px; font-size: 1.2rem; }
        .question-block { flex-direction: column; align-items: flex-start; gap: 10px; }
        .item-kiri, .item-kanan { width: 100%; }
    }
</style>
</head>
<body>

<div class="user-nav">
    <div class="lobby-action">
        <a href="japan.php"><i class="fa-solid fa-map-location-dot"></i> Shin Sekai Map</a>
    </div>
</div>

<header>
    <div class="island-badge"><i class="fa-solid fa-link"></i> Trial 4</div>
    <h1 class="title-text">Wheel of Match</h1>
    <?php if ($total_all_soal > 0): ?>
        <p class="progress-info">Navigasi Kompas: Kosakata <?= $offset + 1 ?> - <?= min($offset + $limit, $total_all_soal) ?> dari <?= $total_all_soal ?></p>
    <?php endif; ?>
</header>

<div class="back-container">
    <a href="latihan_jp.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retreat</a>
</div>

<div class="quiz-container">

    <?php if ($show_result): ?>
        <div class="result-box <?= $lulus ? 'success' : 'failed' ?>">
            <h2><?= $lulus ? '<i class="fa-solid fa-anchor"></i> Rantai Terpasang!' : '<i class="fa-solid fa-triangle-exclamation"></i> Rantai Putus!' ?></h2>
            <div class="result-stats">
                <span class="stat-benar">Tepat: <?= $benar ?></span>
                <span class="stat-salah">Keliru: <?= $salah ?></span>
            </div>
            
            <div class="evaluasi-container">
                <div class="evaluasi-title"><i class="fa-solid fa-list-check"></i> Rincian Pemasangan:</div>
                <div class="evaluasi-grid">
                    <?php foreach($evaluasi_detail as $num => $status): ?>
                        <div class="eval-item <?= $status ?>">
                            #<?= $num ?>: <?= $status == 'benar' ? '<i class="fa-solid fa-check"></i> Tepat' : '<i class="fa-solid fa-xmark"></i> Keliru' ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <p style="font-weight: 700; margin-bottom: 25px; color: var(--ink-dark);"><?= $pesan_hasil ?></p>
            
            <?php if ($lulus): ?>
                <?php if ($next_offset < $total_all_soal): ?>
                    <a href="latihan_jp_match.php?start=<?= $next_offset ?>" class="btn-action btn-next">Maju ke Area Selanjutnya <i class="fa-solid fa-arrow-right"></i></a>
                <?php else: ?>
                    <div class="restart-box">
                        <h3 style="color: var(--success-green); font-family: 'Lora', serif; font-size: 1.8rem; margin-top: 0;">🎉 Ujung Lautan Tercapai! 🎉</h3>
                        <p style="color: var(--ink-dark); font-weight: bold;">Luar biasa! Anda telah berhasil menavigasi seluruh <?= $total_all_soal ?> kosakata!</p>
                        
                        <form action="latihan_jp_match.php" method="GET">
                            <label for="start" style="font-weight: 900; color: var(--ink-dark);">Pilih area untuk dinavigasi ulang:</label><br>
                            <select name="start" id="start" class="select-rute">
                                <?php for ($i = 0; $i < $total_all_soal; $i += $limit): ?>
                                    <option value="<?= $i ?>">Kosakata <?= $i + 1 ?> - <?= min($i + $limit, $total_all_soal) ?></option>
                                <?php endfor; ?>
                            </select><br>
                            <button type="submit" class="btn-action btn-restart"><i class="fa-solid fa-compass"></i> Putar Ulang Kemudi</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <a href="latihan_jp_match.php?start=<?= $offset ?>" class="btn-action btn-retry"><i class="fa-solid fa-rotate-right"></i> Putar Ulang di Area Ini</a>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <?php if (count($soal) > 0): ?>
            <form action="latihan_jp_match.php" method="POST">
                <input type="hidden" name="current_start" value="<?= $offset ?>">
                
                <?php 
                $no = 1; 
                foreach($soal as $s): 
                ?>
                    <div class="question-block">
                        <div class="item-kiri">
                            <span class="kanji"><?= $no ?>. <?= htmlspecialchars($s['kata_jp']) ?></span>
                            <?php if(!empty($s['romaji'])): ?>
                                <span class="romaji"><?= htmlspecialchars($s['romaji']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="item-kanan">
                            <select name="jawaban[<?= $s['id'] ?>]" required>
                                <option value="" disabled selected>-- Hubungkan Arti --</option>
                                <?php foreach($pilihan_kanan as $pk): ?>
                                    <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['teks']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php 
                $no++;
                endforeach; 
                ?>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-link"></i> Pasang Rantai & Evaluasi</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-link-slash" style="color: var(--ocean-blue);"></i>
                <p>Log Pose tidak mendeteksi apa-apa! Belum ada soal Match yang ditambahkan ke database untuk level N5.</p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>