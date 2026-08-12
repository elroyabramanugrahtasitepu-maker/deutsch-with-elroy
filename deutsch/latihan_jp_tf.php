<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_pulau = 'tf_n5'; // Identifier khusus untuk pulau True/False

// Koneksi Database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// --- CEK TOTAL SOAL DI DATABASE ---
$query_total = $conn->query("SELECT COUNT(id) as total FROM latihan_jp_tf WHERE level='N5'");
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

    // Ambil kunci jawaban
    $sql_kunci = "SELECT id, jawaban_benar FROM latihan_jp_tf WHERE level='N5'";
    $result_kunci = $conn->query($sql_kunci);
    
    $kunci_jawaban = [];
    while($row = $result_kunci->fetch_assoc()) {
        $kunci_jawaban[$row['id']] = $row['jawaban_benar']; // 'true' atau 'false'
    }

    // Hitung benar dan salah
    $no_urut = 1;
    foreach ($jawaban_user as $id_soal => $jawaban) {
        if (isset($kunci_jawaban[$id_soal]) && $kunci_jawaban[$id_soal] === $jawaban) {
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
        $pesan_hasil = "Luar biasa Kapten! Kemudi kapal sangat presisi (Minimal 8 Tepat). Pelayaran bisa dilanjutkan!";
        $next_offset = $offset + $limit; 
        
        if ($next_offset < $total_all_soal) {
            simpanJejak($conn, $user_id, $nama_pulau, $next_offset);
        } else {
            simpanJejak($conn, $user_id, $nama_pulau, 0); 
        }
    } else {
        $pesan_hasil = "Awas karang! Banyak keputusan kemudi yang salah! Syarat minimum 8 tepat belum tercapai. Periksa kembali arahnya.";
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
$sql = "SELECT * FROM latihan_jp_tf WHERE level='N5' ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wheel of Truth | Grand Line Trials</title>

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
    .quiz-container::before { content: '\f654'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 2.5rem; color: var(--ocean-blue); text-shadow: 2px 2px 0px #fff; }

    .question-block { margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px dashed var(--map-edge); }
    .question-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .question-number { font-family: 'Lora', serif; font-weight: 700; font-size: 1.2rem; color: var(--ocean-blue); margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 1px; }
    .question-text { font-size: 1.25rem; font-weight: 700; margin: 0 0 20px 0; line-height: 1.6; color: var(--ink-dark); }

    /* --- RADIO BUTTON STYLING KHUSUS TRUE/FALSE --- */
    .options-group { display: flex; flex-direction: row; gap: 20px; }
    .option-label { flex: 1; display: flex; align-items: center; justify-content: center; padding: 15px 20px; border: 2px solid var(--ink-light); border-radius: 4px; cursor: pointer; transition: var(--transition); font-weight: 800; background: transparent; font-size: 1.1rem; }
    .option-label:hover { background: rgba(2, 119, 189, 0.05); border-color: var(--ocean-blue); transform: translateY(-3px); }
    
    .option-label input[type="radio"] { display: none; }
    .option-label input[type="radio"]:checked + .option-text { color: var(--paper-light); }
    
    /* Warna saat terpilih */
    .label-true input[type="radio"]:checked + .option-text { color: white; }
    .label-true:has(input[type="radio"]:checked) { background: var(--success-green); border-color: var(--success-green); }
    
    .label-false input[type="radio"]:checked + .option-text { color: white; }
    .label-false:has(input[type="radio"]:checked) { background: var(--ink-red); border-color: var(--ink-red); }

    .option-text i { margin-right: 8px; font-size: 1.2rem; }

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
        .options-group { flex-direction: column; gap: 10px; }
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
    <div class="island-badge"><i class="fa-solid fa-compass"></i> Trial 3</div>
    <h1 class="title-text">Wheel of Truth</h1>
    <?php if ($total_all_soal > 0): ?>
        <p class="progress-info">Navigasi Kompas: Pernyataan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_all_soal) ?> dari <?= $total_all_soal ?></p>
    <?php endif; ?>
</header>

<div class="back-container">
    <a href="latihan_jp.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retreat</a>
</div>

<div class="quiz-container">

    <?php if ($show_result): ?>
        <div class="result-box <?= $lulus ? 'success' : 'failed' ?>">
            <h2><?= $lulus ? '<i class="fa-solid fa-dharmachakra"></i> Arah Benar!' : '<i class="fa-solid fa-triangle-exclamation"></i> Kapal Melenceng!' ?></h2>
            <div class="result-stats">
                <span class="stat-benar">Tepat: <?= $benar ?></span>
                <span class="stat-salah">Keliru: <?= $salah ?></span>
            </div>
            
            <div class="evaluasi-container">
                <div class="evaluasi-title"><i class="fa-solid fa-list-check"></i> Rincian Navigasi:</div>
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
                    <a href="latihan_jp_tf.php?start=<?= $next_offset ?>" class="btn-action btn-next">Maju ke Area Selanjutnya <i class="fa-solid fa-arrow-right"></i></a>
                <?php else: ?>
                    <div class="restart-box">
                        <h3 style="color: var(--success-green); font-family: 'Lora', serif; font-size: 1.8rem; margin-top: 0;">🎉 Ujung Lautan Tercapai! 🎉</h3>
                        <p style="color: var(--ink-dark); font-weight: bold;">Luar biasa! Anda telah berhasil menavigasi seluruh <?= $total_all_soal ?> pernyataan!</p>
                        
                        <form action="latihan_jp_tf.php" method="GET">
                            <label for="start" style="font-weight: 900; color: var(--ink-dark);">Pilih area untuk dinavigasi ulang:</label><br>
                            <select name="start" id="start" class="select-rute">
                                <?php for ($i = 0; $i < $total_all_soal; $i += $limit): ?>
                                    <option value="<?= $i ?>">Pernyataan <?= $i + 1 ?> - <?= min($i + $limit, $total_all_soal) ?></option>
                                <?php endfor; ?>
                            </select><br>
                            <button type="submit" class="btn-action btn-restart"><i class="fa-solid fa-compass"></i> Putar Ulang Kemudi</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <a href="latihan_jp_tf.php?start=<?= $offset ?>" class="btn-action btn-retry"><i class="fa-solid fa-rotate-right"></i> Putar Ulang di Area Ini</a>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <?php if ($result->num_rows > 0): ?>
            <form action="latihan_jp_tf.php" method="POST">
                <input type="hidden" name="current_start" value="<?= $offset ?>">
                
                <?php 
                $no = 1; 
                while($row = $result->fetch_assoc()): 
                ?>
                    <div class="question-block">
                        <span class="question-number">Pernyataan #<?= $no; ?></span>
                        <p class="question-text"><?= nl2br(htmlspecialchars($row['pernyataan'])); ?></p>
                        
                        <div class="options-group">
                            <label class="option-label label-true">
                                <input type="radio" name="jawaban[<?= $row['id']; ?>]" value="true" required>
                                <span class="option-text"><i class="fa-regular fa-circle"></i> MARU (Benar)</span>
                            </label>
                            <label class="option-label label-false">
                                <input type="radio" name="jawaban[<?= $row['id']; ?>]" value="false">
                                <span class="option-text"><i class="fa-solid fa-xmark"></i> BATSU (Salah)</span>
                            </label>
                        </div>
                    </div>
                <?php 
                $no++;
                endwhile; 
                ?>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-dharmachakra"></i> Kunci Kemudi & Evaluasi</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-dharmachakra" style="color: var(--ocean-blue);"></i>
                <p>Kompas masih kosong! Belum ada pernyataan True/False yang ditambahkan ke database untuk level N5.</p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>