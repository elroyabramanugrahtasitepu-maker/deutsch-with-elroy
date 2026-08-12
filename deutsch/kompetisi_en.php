<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$uid = $_SESSION['user_id'];

/* ==========================================
   1. INISIALISASI TABEL SKOR (SINKRONISASI SEMUA USER)
========================================== */
$conn->query("CREATE TABLE IF NOT EXISTS kompetisi_en_scores (
    user_id INT(11) PRIMARY KEY,
    score INT(11) DEFAULT 0
)");

// KUNCI PERBAIKAN: Masukkan SEMUA user yang ada di tabel 'users' ke dalam leaderboard dengan skor awal 0
// Kecuali admin (agar admin tidak ikut masuk papan peringkat)
$conn->query("INSERT IGNORE INTO kompetisi_en_scores (user_id, score) 
              SELECT id, 0 FROM users WHERE role != 'admin'");

/* ==========================================
   2. LOGIKA MENJAWAB SOAL & POIN
========================================== */
$pesan_notif = "";
if (isset($_POST['submit_answer'])) {
    $q_id = (int)$_POST['question_id'];
    $user_answer = $conn->real_escape_string($_POST['answer']);
    
    // Ambil kunci jawaban dari database
    $cek_jawaban = $conn->query("SELECT * FROM latihan_en_pg WHERE id = $q_id");
    
    if ($cek_jawaban && $cek_jawaban->num_rows > 0) {
        $row = $cek_jawaban->fetch_assoc();
        // Deteksi nama kolom jawaban
        $kunci_db = $row['jawaban'] ?? $row['answer'] ?? $row['kunci'] ?? '';
        $kunci = strtolower(trim($kunci_db));
        
        if (strtolower($user_answer) == $kunci) {
            // JAWABAN BENAR: Tambah 10 poin
            $conn->query("UPDATE kompetisi_en_scores SET score = score + 10 WHERE user_id = $uid");
            $pesan_notif = "<div class='alert alert-success'><i class='fa-solid fa-check'></i> <b>Brilliant!</b> Your answer is correct. You earned +10 points!</div>";
        } else {
            // JAWABAN SALAH: Kurangi 2 poin (jangan sampai minus)
            $conn->query("UPDATE kompetisi_en_scores SET score = GREATEST(0, score - 2) WHERE user_id = $uid");
            $pesan_notif = "<div class='alert alert-danger'><i class='fa-solid fa-xmark'></i> <b>Incorrect!</b> The correct path was ".strtoupper($kunci).". You lost 2 points.</div>";
        }
    }
}

/* ==========================================
   3. AMBIL DATA UNTUK DITAMPILKAN
========================================== */
// Ambil 1 soal Pilihan Ganda secara acak
$soal_acak = $conn->query("SELECT * FROM latihan_en_pg ORDER BY RAND() LIMIT 1")->fetch_assoc();

// MAPPING KOLOM OTOMATIS
if ($soal_acak) {
    $teks_tanya = $soal_acak['pertanyaan'] ?? $soal_acak['question'] ?? $soal_acak['soal'] ?? "Error: Kolom pertanyaan tidak ditemukan di database!";
    $opsi_a = $soal_acak['opsi_a'] ?? $soal_acak['option_a'] ?? $soal_acak['a'] ?? '';
    $opsi_b = $soal_acak['opsi_b'] ?? $soal_acak['option_b'] ?? $soal_acak['b'] ?? '';
    $opsi_c = $soal_acak['opsi_c'] ?? $soal_acak['option_c'] ?? $soal_acak['c'] ?? '';
    $opsi_d = $soal_acak['opsi_d'] ?? $soal_acak['option_d'] ?? $soal_acak['d'] ?? '';
}

// Ambil skor user saat ini
$my_score_query = $conn->query("SELECT score FROM kompetisi_en_scores WHERE user_id = $uid");
$my_score = ($my_score_query && $my_score_query->num_rows > 0) ? $my_score_query->fetch_assoc()['score'] : 0;

// Ambil Top 10 Leaderboard
$leaderboard = $conn->query("
    SELECT u.nama, k.score 
    FROM kompetisi_en_scores k 
    JOIN users u ON k.user_id = u.id 
    ORDER BY k.score DESC, u.nama ASC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Festival Duel | English Village</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --wood-dark: #3A2E26;    
            --wood-medium: #7A5B45;  
            --wood-light: #C4A484;   
            --bg-cream: #FDFBF7;     
            --bg-paper: #F2EBE1;     
            --leaf-green: #4E7B54;   
            --earth-orange: #C86B3C; 
            --sky-blue: #5B8FB9;     
            --radius-md: 12px;
            --shadow-soft: 0 8px 24px rgba(58, 46, 38, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            font-family: 'Nunito', sans-serif; 
            background-color: var(--bg-paper); 
            color: var(--wood-dark); 
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23noise)' opacity='0.05'/%3E%3C/svg%3E");
        }

        .user-nav { display: flex; justify-content: space-between; padding: 15px 40px; background: var(--bg-cream); align-items: center; border-bottom: 3px solid var(--wood-light); }
        .lobby-action a { color: var(--wood-dark); text-decoration: none; font-weight: 800; display: flex; align-items: center; gap: 8px; padding: 10px 20px; background: var(--bg-paper); border-radius: var(--radius-md); }
        
        header { padding: 40px 15px 20px; text-align: center; }
        .logo-text { font-family: 'Lora', serif; font-size: 3.5rem; color: var(--earth-orange); font-weight: 700; margin-bottom: 10px; }
        
        .arena-container { width: 95%; max-width: 1200px; margin: 20px auto 60px; display: grid; grid-template-columns: 350px 1fr; gap: 30px; }

        .leaderboard-box { background: var(--wood-dark); border-radius: var(--radius-md); padding: 25px; color: var(--bg-cream); border: 4px solid var(--wood-medium); box-shadow: var(--shadow-soft); height: fit-content; position: sticky; top: 20px; }
        .leaderboard-title { font-family: 'Lora', serif; font-size: 1.5rem; text-align: center; border-bottom: 2px dashed var(--wood-medium); padding-bottom: 15px; margin-bottom: 20px; color: var(--wood-light); }
        
        .rank-list { list-style: none; }
        .rank-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(196, 164, 132, 0.2); font-weight: 600; align-items: center; }
        .rank-item:last-child { border-bottom: none; }
        .rank-1 { color: #FFD700; font-size: 1.1rem; font-weight: 800; }
        .rank-2 { color: #C0C0C0; font-size: 1.05rem; }
        .rank-3 { color: #CD7F32; font-size: 1.05rem; }
        
        .my-score-box { background: var(--earth-orange); padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; font-weight: 800; font-size: 1.2rem; }

        .question-box { background: var(--bg-cream); border-radius: var(--radius-md); padding: 40px; box-shadow: var(--shadow-soft); border: 2px solid var(--wood-light); }
        .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; font-weight: 800; color: var(--leaf-green); border-bottom: 2px solid var(--bg-paper); padding-bottom: 15px; }
        
        .question-text { font-family: 'Lora', serif; font-size: 1.5rem; color: var(--wood-dark); line-height: 1.6; margin-bottom: 40px; text-align: center; }

        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .option-label { display: flex; align-items: center; padding: 20px; background: var(--bg-paper); border: 2px solid var(--wood-light); border-radius: var(--radius-md); cursor: pointer; transition: 0.3s; font-weight: 700; font-size: 1.1rem; }
        .option-label:hover { background: #e6ddcf; transform: translateY(-3px); }
        .option-label input[type="radio"] { margin-right: 15px; transform: scale(1.5); accent-color: var(--earth-orange); }
        
        .btn-submit { background: var(--earth-orange); color: white; border: none; padding: 15px 40px; font-size: 1.2rem; font-weight: 800; border-radius: 30px; cursor: pointer; width: 100%; margin-top: 40px; transition: 0.3s; font-family: 'Nunito', sans-serif; }
        .btn-submit:hover { background: #a6562e; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(200, 107, 60, 0.4); }

        .alert { padding: 15px 20px; border-radius: var(--radius-md); margin-bottom: 25px; font-weight: 600; text-align: center; font-size: 1.1rem; animation: slideDown 0.4s ease; }
        .alert-success { background: #dcedc8; color: #33691e; border: 2px solid #aed581; }
        .alert-danger { background: #ffcdd2; color: #b71c1c; border: 2px solid #e57373; }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) {
            .arena-container { grid-template-columns: 1fr; }
            .options-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="user-nav">
    <div class="lobby-action">
        <a href="english.php"><i class="fa-solid fa-arrow-left"></i> Back to Village</a>
    </div>
</div>

<header>
    <h1 class="logo-text"><i class="fa-solid fa-fire-flame-curved"></i> Festival Duel</h1>
    <p style="font-weight: 600; color: var(--wood-medium); font-size: 1.1rem;">Answer correctly to earn points and become the Village Champion!</p>
</header>

<div class="arena-container">
    
    <div class="leaderboard-box">
        <h2 class="leaderboard-title"><i class="fa-solid fa-trophy"></i> Top Villagers</h2>
        <ul class="rank-list">
            <?php 
            $rank = 1;
            if ($leaderboard && $leaderboard->num_rows > 0) {
                while($lb = $leaderboard->fetch_assoc()) {
                    $rankClass = "";
                    if($rank == 1) $rankClass = "rank-1";
                    elseif($rank == 2) $rankClass = "rank-2";
                    elseif($rank == 3) $rankClass = "rank-3";
                    
                    echo "<li class='rank-item $rankClass'>";
                    echo "<span>#$rank " . htmlspecialchars($lb['nama']) . "</span>";
                    echo "<span>" . $lb['score'] . " pts</span>";
                    echo "</li>";
                    $rank++;
                }
            } else {
                echo "<li style='text-align:center; color: var(--wood-light);'>No champions yet.</li>";
            }
            ?>
        </ul>

        <div class="my-score-box">
            Your Score: <?= $my_score ?> pts
        </div>
    </div>

    <div class="question-box">
        <?= $pesan_notif ?>

        <?php if ($soal_acak): ?>
            <div class="question-header">
                <span><i class="fa-solid fa-scroll"></i> Question of the Moment</span>
                <span style="color: var(--earth-orange);"><i class="fa-solid fa-bolt"></i> +10 Pts / -2 Pts</span>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="question_id" value="<?= $soal_acak['id'] ?>">
                
                <div class="question-text">
                    "<?= nl2br(htmlspecialchars($teks_tanya)) ?>"
                </div>

                <div class="options-grid">
                    <?php if(!empty($opsi_a)): ?>
                    <label class="option-label">
                        <input type="radio" name="answer" value="a" required> A. <?= htmlspecialchars($opsi_a) ?>
                    </label>
                    <?php endif; ?>

                    <?php if(!empty($opsi_b)): ?>
                    <label class="option-label">
                        <input type="radio" name="answer" value="b" required> B. <?= htmlspecialchars($opsi_b) ?>
                    </label>
                    <?php endif; ?>

                    <?php if(!empty($opsi_c)): ?>
                    <label class="option-label">
                        <input type="radio" name="answer" value="c" required> C. <?= htmlspecialchars($opsi_c) ?>
                    </label>
                    <?php endif; ?>

                    <?php if(!empty($opsi_d)): ?>
                    <label class="option-label">
                        <input type="radio" name="answer" value="d" required> D. <?= htmlspecialchars($opsi_d) ?>
                    </label>
                    <?php endif; ?>
                </div>

                <button type="submit" name="submit_answer" class="btn-submit">
                    Submit Answer <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        <?php else: ?>
            <div style="text-align:center; padding: 50px 0;">
                <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--wood-light); margin-bottom: 20px;"></i>
                <h3 style="font-family: 'Lora', serif; color: var(--wood-medium);">The chest is empty!</h3>
                <p>No questions are currently available in the database.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>