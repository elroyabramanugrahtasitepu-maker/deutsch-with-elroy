<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_pulau = 'essay_n5'; // Identifier khusus untuk pulau Essay

// Koneksi Database
$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// --- CEK TOTAL SOAL DI DATABASE ---
$query_total = $conn->query("SELECT COUNT(id) as total FROM latihan_jp_essay WHERE level='N5'");
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

// Fungsi untuk menyimpan posisi terakhir user ke database (Sama persis dengan PG)
function simpanJejak($conn, $user_id, $pulau, $offset) {
    $stmt = $conn->prepare("INSERT INTO user_log_pose (user_id, pulau, last_offset) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_offset = ?");
    $stmt->bind_param("isii", $user_id, $pulau, $offset, $offset);
    $stmt->execute();
    $stmt->close();
}

// JIKA FORM DI-SUBMIT (User menjawab soal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jawaban'])) {
    $jawaban_user = $_POST['jawaban'];
    $show_result = true;
    
    // Tangkap posisi saat ini
    $offset = isset($_POST['current_start']) ? (int)$_POST['current_start'] : 0;

    // Ambil kunci jawaban
    $sql_kunci = "SELECT id, kunci_jawaban FROM latihan_jp_essay WHERE level='N5'";
    $result_kunci = $conn->query($sql_kunci);
    
    $kunci_jawaban = [];
    while($row = $result_kunci->fetch_assoc()) {
        $kunci_jawaban[$row['id']] = $row['kunci_jawaban'];
    }

    // Hitung benar dan salah (Pengecekan Text/Essay)
    $no_urut = 1;
    foreach ($jawaban_user as $id_soal => $jawaban) {
        // Hapus spasi (biasa dan full-width Jepang) serta jadikan huruf kecil agar toleran terhadap typo spasi/kapital
        $jawaban_bersih = str_replace([' ', '　'], '', strtolower(trim($jawaban)));
        $kunci_bersih = str_replace([' ', '　'], '', strtolower(trim($kunci_jawaban[$id_soal] ?? '')));

        if ($jawaban_bersih === $kunci_bersih) {
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
        $pesan_hasil = "Luar biasa Kapten! Pahatan sejarah sangat akurat (Minimal 8 Tepat). Pelayaran bisa dilanjutkan!";
        $next_offset = $offset + $limit; 
        
        // Simpan posisi TERBARU ke database jika lulus
        if ($next_offset < $total_all_soal) {
            simpanJejak($conn, $user_id, $nama_pulau, $next_offset);
        } else {
            // Jika sudah tamat semua soal, reset ke 0
            simpanJejak($conn, $user_id, $nama_pulau, 0); 
        }
    } else {
        $pesan_hasil = "Sayang sekali Kapten, banyak guratan yang keliru! Syarat minimum 8 tepat belum tercapai. Silakan perbaiki pahatan Anda.";
        // Jika gagal, simpan posisi SAAT INI agar tidak ngulang dari 0
        simpanJejak($conn, $user_id, $nama_pulau, $offset);
    }
} 
// JIKA HALAMAN BARU DIBUKA (Bukan submit jawaban)
else {
    // Jika user milih rute manual dari tombol (misal setelah tamat)
    if (isset($_GET['start'])) {
        $offset = (int)$_GET['start'];
        simpanJejak($conn, $user_id, $nama_pulau, $offset); 
    } 
    // Jika user baru login/masuk halaman, LOAD posisi terakhir dari database
    else {
        $stmt = $conn->prepare("SELECT last_offset FROM user_log_pose WHERE user_id = ? AND pulau = ?");
        $stmt->bind_param("is", $user_id, $nama_pulau);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $offset = $row['last_offset']; // Lanjut dari log terakhir!
        }
        $stmt->close();
    }
}

// Pastikan offset tidak melebihi total soal
if ($offset >= $total_all_soal && $total_all_soal > 0) {
    $offset = 0;
    simpanJejak($conn, $user_id, $nama_pulau, 0);
}

// --- AMBIL SOAL BERDASARKAN POSISI (OFFSET) ---
$sql = "SELECT * FROM latihan_jp_essay WHERE level='N5' ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poneglyph Log | Grand Line Trials</title>

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
        --stone-bg: #E0E0E0;
        --stone-dark: #757575;
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

    .quiz-container { width: 90%; max-width: 800px; margin: 0 auto 50px; background: var(--stone-bg); border: 4px solid var(--stone-dark); box-shadow: 8px 8px 0px rgba(62, 39, 35, 0.8), inset 0 0 20px rgba(0,0,0,0.1); border-radius: 8px; padding: 30px 40px; position: relative; }
    .quiz-container::before { content: '\f56b'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 2.5rem; color: var(--stone-dark); text-shadow: 2px 2px 0px #fff; }

    .question-block { margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px dashed var(--stone-dark); }
    .question-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .question-number { font-family: 'Lora', serif; font-weight: 700; font-size: 1.2rem; color: var(--ink-dark); margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 1px; }
    .question-text { font-size: 1.25rem; font-weight: 700; margin: 0 0 20px 0; line-height: 1.6; color: var(--ink-dark); }

    /* --- INPUT TEXT KHUSUS ESSAY --- */
    .essay-input { width: 100%; padding: 15px 20px; font-size: 1.15rem; border: 2px solid var(--stone-dark); border-radius: 4px; background: rgba(255, 255, 255, 0.7); color: var(--ink-dark); font-family: 'Nunito', sans-serif; font-weight: 700; transition: var(--transition); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); }
    .essay-input:focus { outline: none; border-color: var(--ink-dark); background: #fff; box-shadow: 0 0 0 3px rgba(117, 117, 117, 0.3); }
    .essay-input::placeholder { color: #A0A0A0; font-weight: 400; font-style: italic; }

    .btn-submit { display: block; width: 100%; margin-top: 40px; background: var(--stone-dark); color: #fff; border: 2px solid var(--stone-dark); padding: 15px; font-size: 1.2rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: var(--transition); border-radius: 4px; font-family: 'Nunito', sans-serif; }
    .btn-submit:hover { background: var(--ink-dark); border-color: var(--ink-dark); box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3); }

    .empty-state { text-align: center; padding: 40px; font-weight: 700; color: var(--stone-dark); }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; }

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
    <div class="island-badge"><i class="fa-solid fa-scroll"></i> Trial 2</div>
    <h1 class="title-text">Poneglyph Log</h1>
    <?php if ($total_all_soal > 0): ?>
        <p class="progress-info">Pahatan Sejarah: Log <?= $offset + 1 ?> - <?= min($offset + $limit, $total_all_soal) ?> dari <?= $total_all_soal ?> Log</p>
    <?php endif; ?>
</header>

<div class="back-container">
    <a href="latihan_jp.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retreat</a>
</div>

<div class="quiz-container">

    <?php if ($show_result): ?>
        <div class="result-box <?= $lulus ? 'success' : 'failed' ?>">
            <h2><?= $lulus ? '<i class="fa-solid fa-khanda"></i> Pahatan Berhasil!' : '<i class="fa-solid fa-triangle-exclamation"></i> Sejarah Gagal Dibaca!' ?></h2>
            <div class="result-stats">
                <span class="stat-benar">Tepat: <?= $benar ?></span>
                <span class="stat-salah">Keliru: <?= $salah ?></span>
            </div>
            
            <div class="evaluasi-container">
                <div class="evaluasi-title"><i class="fa-solid fa-list-check"></i> Rincian Guratan:</div>
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
                    <a href="latihan_jp_essay.php?start=<?= $next_offset ?>" class="btn-action btn-next">Baca Log Selanjutnya <i class="fa-solid fa-arrow-right"></i></a>
                <?php else: ?>
                    <div class="restart-box">
                        <h3 style="color: var(--success-green); font-family: 'Lora', serif; font-size: 1.8rem; margin-top: 0;">🎉 Semua Poneglyph Terbaca! 🎉</h3>
                        <p style="color: var(--ink-dark); font-weight: bold;">Luar biasa! Anda telah berhasil menyalin seluruh <?= $total_all_soal ?> teks sejarah!</p>
                        
                        <form action="latihan_jp_essay.php" method="GET">
                            <label for="start" style="font-weight: 900; color: var(--ink-dark);">Pilih log untuk dipelajari kembali:</label><br>
                            <select name="start" id="start" class="select-rute">
                                <?php for ($i = 0; $i < $total_all_soal; $i += $limit): ?>
                                    <option value="<?= $i ?>">Log <?= $i + 1 ?> - <?= min($i + $limit, $total_all_soal) ?></option>
                                <?php endfor; ?>
                            </select><br>
                            <button type="submit" class="btn-action btn-restart"><i class="fa-solid fa-feather"></i> Tulis Ulang Sejarah</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <a href="latihan_jp_essay.php?start=<?= $offset ?>" class="btn-action btn-retry"><i class="fa-solid fa-rotate-right"></i> Pahat Ulang Log Ini</a>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <?php if ($result->num_rows > 0): ?>
            <form action="latihan_jp_essay.php" method="POST">
                <input type="hidden" name="current_start" value="<?= $offset ?>">
                
                <?php 
                $no = 1; 
                while($row = $result->fetch_assoc()): 
                ?>
                    <div class="question-block">
                        <span class="question-number">Pahatan #<?= $no; ?></span>
                        <p class="question-text"><?= nl2br(htmlspecialchars($row['pertanyaan'])); ?></p>
                        
                        <input type="text" name="jawaban[<?= $row['id']; ?>]" class="essay-input" placeholder="Ketik jawabanmu di sini..." required autocomplete="off">
                    </div>
                <?php 
                $no++;
                endwhile; 
                ?>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-scroll"></i> Kunci Pahatan & Evaluasi</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-scroll" style="color: var(--stone-dark);"></i>
                <p>Batu Poneglyph masih kosong! Belum ada teks sejarah yang ditambahkan ke database.</p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>