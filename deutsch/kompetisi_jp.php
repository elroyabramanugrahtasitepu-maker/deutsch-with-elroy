<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$uid = $_SESSION['user_id'];

/* ==========================================
   1. INISIALISASI TABEL SKOR
========================================== */
$conn->query("CREATE TABLE IF NOT EXISTS kompetisi_jp_scores (
    user_id INT(11) PRIMARY KEY,
    score INT(11) DEFAULT 0
)");

$conn->query("INSERT IGNORE INTO kompetisi_jp_scores (user_id, score) 
              SELECT id, 0 FROM users WHERE role != 'admin'");


/* ==========================================
   2. LOGIKA MENJAWAB SOAL GABUNGAN & POIN
========================================== */
$pesan_notif = "";
if (isset($_POST['submit_kompetisi'])) {
    $benar = 0;
    $salah = 0;

    try {
        if(isset($_POST['tf'])) {
            foreach($_POST['tf'] as $id => $jawaban) {
                $cek = $conn->query("SELECT jawaban_benar FROM latihan_jp_tf WHERE id = " . (int)$id);
                if($cek && $cek->num_rows > 0) {
                    $row = $cek->fetch_assoc();
                    if(strtolower(trim((string)$row['jawaban_benar'])) == strtolower(trim((string)$jawaban))) { $benar++; } else { $salah++; }
                }
            }
        }

        if(isset($_POST['match'])) {
            foreach($_POST['match'] as $id => $jawaban) {
                if($id == $jawaban) { $benar++; } else { $salah++; }
            }
        }

        if(isset($_POST['pg'])) {
            foreach($_POST['pg'] as $id => $jawaban) {
                $cek = $conn->query("SELECT jawaban_benar FROM latihan_jp_pg WHERE id = " . (int)$id);
                if($cek && $cek->num_rows > 0) {
                    $row = $cek->fetch_assoc();
                    if(strtoupper(trim((string)$row['jawaban_benar'])) == strtoupper(trim((string)$jawaban))) { $benar++; } else { $salah++; }
                }
            }
        }

        if(isset($_POST['essay'])) {
            foreach($_POST['essay'] as $id => $jawaban) {
                $cek = $conn->query("SELECT kunci_jawaban FROM latihan_jp_essay WHERE id = " . (int)$id);
                if($cek && $cek->num_rows > 0) {
                    $row = $cek->fetch_assoc();
                    if(strtolower(trim((string)$row['kunci_jawaban'])) == strtolower(trim((string)$jawaban))) { $benar++; } else { $salah++; }
                }
            }
        }

        // Kalkulasi Poin: Benar +20, Salah -10
        $tambahan_poin = ($benar * 20) - ($salah * 10);

        // Update Skor User
        $conn->query("UPDATE kompetisi_jp_scores SET score = score + $tambahan_poin WHERE user_id = $uid");

        // Notifikasi Battle Animasi Seru
        if ($tambahan_poin > 0) {
            $pesan_notif = "<div class='battle-alert alert-win pulse-anim'>
                                <div class='alert-icon'><i class='fa-solid fa-crown fa-bounce'></i></div>
                                <div><b style='font-size:1.5rem;'>HAKI RAJA BANGKIT!</b><br>Serangan telak! $benar Tepat, $salah Meleset. Bounty naik <b style='color:var(--gold-strawhat); font-size:1.3rem;'>+$tambahan_poin Berry</b>!</div>
                            </div>";
        } elseif ($tambahan_poin < 0) {
            $pesan_notif = "<div class='battle-alert alert-lose shake-anim'>
                                <div class='alert-icon'><i class='fa-solid fa-bomb fa-beat'></i></div>
                                <div><b style='font-size:1.5rem;'>BUSTER CALL MENYERANG!</b><br>Kapalmu hancur! $benar Tepat, $salah Meleset. Bounty hangus <b style='color:var(--gold-strawhat); font-size:1.3rem;'>$tambahan_poin Berry</b>.</div>
                            </div>";
        } else {
            $pesan_notif = "<div class='battle-alert alert-tie'>
                                <div class='alert-icon'><i class='fa-solid fa-anchor fa-flip'></i></div>
                                <div><b style='font-size:1.5rem;'>PERTARUNGAN SERI!</b><br>Duel seimbang melawan Shichibukai. $benar Tepat, $salah Meleset. Tidak ada Bounty yang dirampas.</div>
                            </div>";
        }
    } catch (Exception $e) {
        $pesan_notif = "<div class='battle-alert alert-lose'><div><b>Sistem Den Den Mushi Rusak:</b> " . $e->getMessage() . "</div></div>";
    }
}


/* ==========================================
   3. AMBIL DATA UNTUK DITAMPILKAN
========================================== */
$soal_tf = $conn->query("SELECT id, pernyataan FROM latihan_jp_tf WHERE level='N5' ORDER BY RAND() LIMIT 2");
$soal_match = $conn->query("SELECT id, kata_jp, romaji, arti_id FROM latihan_jp_match WHERE level='N5' ORDER BY RAND() LIMIT 3");
$soal_pg = $conn->query("SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d FROM latihan_jp_pg WHERE level='N5' ORDER BY RAND() LIMIT 2");
$soal_essay = $conn->query("SELECT id, pertanyaan FROM latihan_jp_essay WHERE level='N5' ORDER BY RAND() LIMIT 2");

$pilihan_match = [];
if ($soal_match && $soal_match->num_rows > 0) {
    $soal_match_arr = [];
    while($row = $soal_match->fetch_assoc()) {
        $soal_match_arr[] = $row;
        $pilihan_match[] = ['id' => $row['id'], 'teks' => $row['arti_id']];
    }
    shuffle($pilihan_match);
} else {
    $soal_match_arr = [];
}

$my_score_query = $conn->query("SELECT score FROM kompetisi_jp_scores WHERE user_id = $uid");
$my_score = ($my_score_query && $my_score_query->num_rows > 0) ? $my_score_query->fetch_assoc()['score'] : 0;

$leaderboard = $conn->query("
    SELECT u.username, k.score 
    FROM users u 
    LEFT JOIN kompetisi_jp_scores k ON u.id = k.user_id 
    WHERE u.role != 'admin'
    ORDER BY score DESC, u.username ASC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Line Arena | One Piece</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Rye&family=Nunito:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            --pirate-black: #111111;
            --luffy-red: #D32F2F;
            --luffy-dark: #8c1c1c;
            --gold-strawhat: #FFC107;
            --sea-blue: #0A3D62;
            --sea-light: #1A5A87;
            --wanted-paper: #EEDC9A;
            --wanted-dark: #C9A959;
            --wanted-text: #3E2723;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            font-family: 'Nunito', sans-serif; 
            background: linear-gradient(135deg, var(--sea-blue), var(--sea-light));
            color: var(--pirate-black); 
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* ANIMASI LAUTAN DAN KAPAL DI BACKGROUND */
        .ocean-waves {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 150px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 800 100" xmlns="http://www.w3.org/2000/svg"><path d="M0 50 Q 100 0 200 50 T 400 50 T 600 50 T 800 50 L 800 100 L 0 100 Z" fill="rgba(255,255,255,0.15)"/></svg>') repeat-x;
            background-size: 400px 150px;
            animation: waveAnim 12s linear infinite;
            z-index: -2;
        }
        .ocean-waves.layer2 {
            bottom: -20px;
            opacity: 0.1;
            animation: waveAnim 8s linear infinite reverse;
            z-index: -1;
        }

        .sunny-ship {
            position: fixed;
            bottom: 80px;
            left: -150px;
            font-size: 6rem;
            color: rgba(255, 255, 255, 0.2);
            animation: sailAnim 25s linear infinite;
            z-index: -1;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.2));
        }

        @keyframes waveAnim {
            0% { transform: translateX(0); }
            100% { transform: translateX(-400px); }
        }
        @keyframes sailAnim {
            0% { transform: translateX(-150px) rotate(-5deg) translateY(0px); }
            25% { transform: translateX(25vw) rotate(3deg) translateY(-15px); }
            50% { transform: translateX(50vw) rotate(-3deg) translateY(0px); }
            75% { transform: translateX(75vw) rotate(5deg) translateY(-20px); }
            100% { transform: translateX(110vw) rotate(-5deg) translateY(0px); }
        }
        @keyframes swing {
            0% { transform: rotate(-2deg); }
            100% { transform: rotate(2deg); }
        }
        @keyframes spinWheel {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulseHaki {
            0% { box-shadow: 0 0 0 0 rgba(211, 47, 47, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(211, 47, 47, 0); }
            100% { box-shadow: 0 0 0 0 rgba(211, 47, 47, 0); }
        }
        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            10%, 30%, 50%, 70%, 90% {transform: translateX(-5px);}
            20%, 40%, 60%, 80% {transform: translateX(5px);}
        }

        .pulse-anim { animation: pulseHaki 2s infinite; }
        .shake-anim { animation: shake 0.5s; }

        /* NAVBAR */
        .user-nav { display: flex; justify-content: space-between; padding: 15px 40px; background: rgba(17, 17, 17, 0.85); backdrop-filter: blur(10px); border-bottom: 4px solid var(--gold-strawhat); position: relative; z-index: 10; }
        .lobby-action a { color: #fff; text-decoration: none; font-weight: 900; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s;}
        .lobby-action a:hover { color: var(--gold-strawhat); text-shadow: 0 0 15px var(--gold-strawhat); transform: scale(1.05); }

        /* HEADER ANIMASI */
        header { padding: 50px 15px 30px; text-align: center; position: relative; z-index: 10;}
        .logo-text { font-family: 'Rye', serif; font-size: 4.5rem; color: var(--gold-strawhat); font-weight: normal; margin-bottom: 5px; text-shadow: 4px 4px 0px var(--luffy-red), 6px 6px 0px #000; letter-spacing: 3px; display: flex; align-items: center; justify-content: center; gap: 20px;}
        .spin-icon { animation: spinWheel 10s linear infinite; color: var(--luffy-red); text-shadow: 2px 2px 0 #000;}
        .subtitle { font-weight: 900; color: #fff; font-size: 1.3rem; text-transform: uppercase; letter-spacing: 2px; text-shadow: 2px 2px 4px #000; background: rgba(0,0,0,0.5); display: inline-block; padding: 5px 20px; border-radius: 30px;}

        .arena-container { width: 95%; max-width: 1200px; margin: 10px auto 80px; display: grid; grid-template-columns: 360px 1fr; gap: 40px; position: relative; z-index: 10;}

        /* POSTER WANTED MENGAYUN */
        .leaderboard-container { perspective: 1000px; }
        .leaderboard-box { 
            background: var(--wanted-paper); 
            padding: 30px 25px; 
            border: 2px solid #fff;
            box-shadow: inset 0 0 50px rgba(139, 69, 19, 0.4), 10px 10px 20px rgba(0,0,0,0.6); 
            background-image: url("https://www.transparenttextures.com/patterns/old-wall.png"); 
            transform-origin: top center;
            animation: swing 4s ease-in-out infinite alternate;
            position: relative;
        }
        /* Paku Poster */
        .leaderboard-box::before {
            content: ''; position: absolute; top: 10px; left: 50%; transform: translateX(-50%);
            width: 15px; height: 15px; background: #333; border-radius: 50%;
            box-shadow: inset -2px -2px 5px rgba(0,0,0,0.8), 2px 2px 2px rgba(255,255,255,0.4);
        }

        .leaderboard-title { font-family: 'Rye', serif; font-size: 4rem; text-align: center; margin-bottom: -10px; color: var(--wanted-text); letter-spacing: 5px; line-height: 1; text-shadow: 2px 2px 0px rgba(255,255,255,0.5);}
        .wanted-sub { font-family: 'Times New Roman', Times, serif; text-align: center; font-weight: bold; font-size: 1.3rem; letter-spacing: 5px; color: var(--wanted-text); border-bottom: 3px solid var(--wanted-text); padding-bottom: 15px; margin-bottom: 20px;}
        
        .rank-list { list-style: none; }
        .rank-item { display: flex; justify-content: space-between; padding: 12px 5px; border-bottom: 2px dotted var(--wanted-dark); font-weight: 900; align-items: center; font-size: 1.1rem; font-family: 'Times New Roman', Times, serif; text-transform: uppercase; transition: 0.2s;}
        .rank-item:hover { background: rgba(0,0,0,0.08); transform: scale(1.02); padding-left: 10px;}
        .rank-1 { font-size: 1.5rem; color: #000; font-weight: 900; background: linear-gradient(90deg, rgba(255,215,0,0.3) 0%, transparent 100%); }
        .rank-1 .bounty-score { font-size: 1.6rem; color: #b8860b; text-shadow: 1px 1px 0px #fff;}
        
        .bounty-name { flex: 1; margin-left: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .bounty-score { font-family: 'Rye', serif; font-size: 1.3rem; letter-spacing: 1px;}
        
        .my-score-box { background: var(--wanted-text); color: var(--wanted-paper); padding: 15px; border: 3px solid #000; text-align: center; margin-top: 25px; font-weight: 900; font-size: 1.6rem; font-family: 'Rye', serif; letter-spacing: 2px; box-shadow: inset 0 0 10px rgba(0,0,0,0.8);}

        /* DEK KAPAL BATTLE (KUIS) */
        .question-box { background: rgba(253, 253, 253, 0.95); border-radius: 12px; padding: 40px; box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5); border: 4px solid var(--pirate-black); backdrop-filter: blur(5px);}
        
        /* BATTLE ALERTS */
        .battle-alert { display: flex; align-items: center; gap: 20px; padding: 25px; border: 4px solid; margin-bottom: 40px; font-weight: 800; font-size: 1.3rem; box-shadow: 5px 5px 0px #000; border-radius: 8px; text-transform: uppercase;}
        .alert-icon { font-size: 3.5rem; }
        .alert-win { background: var(--luffy-red); color: #fff; border-color: var(--pirate-black); }
        .alert-win .alert-icon { color: var(--gold-strawhat); }
        .alert-lose { background: var(--pirate-black); color: #fff; border-color: var(--luffy-red); }
        .alert-lose .alert-icon { color: var(--luffy-red); }
        .alert-tie { background: #E0E0E0; color: var(--pirate-black); border-color: var(--pirate-black); }

        /* Judul Pulau */
        .section-title { font-family: 'Rye', serif; background: var(--pirate-black); color: var(--gold-strawhat); padding: 12px 25px; display: inline-block; border-radius: 6px; margin-top: 15px; margin-bottom: 25px; font-size: 1.6rem; letter-spacing: 2px; text-transform: uppercase; box-shadow: 4px 4px 0px rgba(0,0,0,0.3); transform: rotate(-1deg);}
        
        .question-block { margin-bottom: 40px; padding-bottom: 30px; border-bottom: 3px dashed #ccc; transition: 0.3s;}
        .question-block:hover { background: rgba(10, 61, 98, 0.03); border-radius: 8px; padding: 15px; border-bottom: 3px solid var(--sea-blue);}
        .q-text { font-size: 1.4rem; font-weight: 900; margin-bottom: 20px; color: var(--pirate-black); }

        /* Inputs & Select */
        input[type="text"], select { width: 100%; padding: 18px; border: 3px solid var(--pirate-black); background: #fff; font-family: 'Nunito'; font-weight: 800; font-size: 1.2rem; color: var(--pirate-black); border-radius: 6px; transition: 0.3s;}
        input[type="text"]:focus, select:focus { outline: none; border-color: var(--luffy-red); box-shadow: 0 0 15px rgba(211, 47, 47, 0.4); transform: scale(1.01);}

        /* Radio Buttons */
        .radio-group { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .radio-label { display: flex; align-items: center; padding: 18px 20px; border: 3px solid #ddd; background: #fff; cursor: pointer; font-weight: 800; transition: 0.2s; font-size: 1.2rem; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);}
        .radio-label:hover { border-color: var(--sea-blue); background: #f0f8ff; transform: translateY(-3px); box-shadow: 0 8px 12px rgba(10,61,98,0.15);}
        .radio-label input { margin-right: 15px; transform: scale(1.6); accent-color: var(--luffy-red); cursor: pointer;}

        /* Submit Button (Gomu Gomu) */
        .btn-submit { display: block; width: 100%; background: var(--luffy-red); color: var(--gold-strawhat); border: 4px solid var(--pirate-black); padding: 25px; font-size: 2rem; font-weight: normal; text-transform: uppercase; cursor: pointer; transition: 0.2s; font-family: 'Rye', serif; margin-top: 50px; box-shadow: 8px 8px 0px var(--pirate-black); letter-spacing: 3px; border-radius: 10px; position: relative; overflow: hidden;}
        .btn-submit::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: rgba(255,255,255,0.2); transform: rotate(45deg); transition: 0.5s; opacity: 0; }
        .btn-submit:hover { background: var(--luffy-dark); transform: translate(3px, 3px); box-shadow: 5px 5px 0px var(--pirate-black); }
        .btn-submit:hover::after { opacity: 1; left: 100%; }
        .btn-submit:active { transform: translate(8px, 8px); box-shadow: 0px 0px 0px var(--pirate-black); }

        @media (max-width: 900px) {
            .arena-container { grid-template-columns: 1fr; }
            .radio-group { grid-template-columns: 1fr; }
            .leaderboard-box { animation: none; transform: none; } /* Matikan ayun di HP biar ga pusing */
            .logo-text { font-size: 2.8rem; }
        }
    </style>
</head>
<body>

<div class="ocean-waves"></div>
<div class="ocean-waves layer2"></div>
<div class="sunny-ship"><i class="fa-solid fa-sailboat"></i></div>

<div class="user-nav">
    <div class="lobby-action">
        <a href="japan.php"><i class="fa-solid fa-ship fa-beat-fade"></i> Kembali ke Kapal Utama</a>
    </div>
</div>

<header>
    <h1 class="logo-text">
        <i class="fa-solid fa-dharmachakra spin-icon"></i> 
        GRAND LINE ARENA 
        <i class="fa-solid fa-dharmachakra spin-icon" style="animation-direction: reverse;"></i>
    </h1>
    <p class="subtitle">Kalahkan Yonko, Rebut Bounty Tertinggi, Jadilah Raja Bajak Laut!</p>
</header>

<div class="arena-container">
    
    <div class="leaderboard-container">
        <div class="leaderboard-box">
            <h2 class="leaderboard-title">WANTED</h2>
            <div class="wanted-sub">DEAD OR ALIVE</div>
            <ul class="rank-list">
                <?php 
                $rank = 1;
                if ($leaderboard && $leaderboard->num_rows > 0) {
                    while($lb = $leaderboard->fetch_assoc()) {
                        $rankClass = "";
                        if($rank == 1) $rankClass = "rank-1";
                        
                        echo "<li class='rank-item $rankClass'>";
                        echo "<span>$rank.</span>";
                        echo "<span class='bounty-name'>" . htmlspecialchars($lb['username']) . "</span>";
                        echo "<span class='bounty-score'><span style='font-family:sans-serif;'>฿</span>" . number_format($lb['score'], 0, ',', '.') . "</span>";
                        echo "</li>";
                        $rank++;
                    }
                } else {
                    echo "<li style='text-align:center; color: var(--wanted-text);'>Lautan masih sepi.</li>";
                }
                ?>
            </ul>

            <div class="my-score-box">
                Bounty-mu:<br>
                ฿ <?= number_format($my_score, 0, ',', '.') ?>
            </div>
        </div>
    </div>

    <div class="question-box">
        <?= $pesan_notif ?>

        <form action="" method="POST">
            
            <?php if($soal_tf && $soal_tf->num_rows > 0): ?>
                <h3 class="section-title"><i class="fa-solid fa-compass"></i> Log Pose: Benar/Salah</h3>
                <?php while($row = $soal_tf->fetch_assoc()): ?>
                    <div class="question-block">
                        <div class="q-text"><?= htmlspecialchars($row['pernyataan']) ?></div>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="tf[<?= $row['id'] ?>]" value="true" required> MARU (Tepat)</label>
                            <label class="radio-label"><input type="radio" name="tf[<?= $row['id'] ?>]" value="false"> BATSU (Keliru)</label>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if(!empty($soal_match_arr)): ?>
                <h3 class="section-title"><i class="fa-solid fa-gem"></i> Harta Karun Poneglyph</h3>
                <?php foreach($soal_match_arr as $row): ?>
                    <div class="question-block" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <div class="q-text" style="flex: 1; min-width: 200px; margin: 0; color: var(--sea-blue); font-size: 2rem;"><?= htmlspecialchars($row['kata_jp']) ?> <br><span style="font-size: 1.1rem; color: #666; font-weight: normal;">(<?= htmlspecialchars($row['romaji']) ?>)</span></div>
                        <div style="flex: 1; min-width: 200px;">
                            <select name="match[<?= $row['id'] ?>]" required>
                                <option value="" disabled selected>-- Cocokkan Emas --</option>
                                <?php foreach($pilihan_match as $pk): ?>
                                    <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['teks']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if($soal_pg && $soal_pg->num_rows > 0): ?>
                <h3 class="section-title"><i class="fa-solid fa-map-location-dot"></i> Peta Rute Bunpou</h3>
                <?php while($row = $soal_pg->fetch_assoc()): ?>
                    <div class="question-block">
                        <div class="q-text"><?= htmlspecialchars($row['pertanyaan']) ?></div>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="pg[<?= $row['id'] ?>]" value="a" required> A. <?= htmlspecialchars($row['opsi_a']) ?></label>
                            <label class="radio-label"><input type="radio" name="pg[<?= $row['id'] ?>]" value="b"> B. <?= htmlspecialchars($row['opsi_b']) ?></label>
                            <label class="radio-label"><input type="radio" name="pg[<?= $row['id'] ?>]" value="c"> C. <?= htmlspecialchars($row['opsi_c']) ?></label>
                            <label class="radio-label"><input type="radio" name="pg[<?= $row['id'] ?>]" value="d"> D. <?= htmlspecialchars($row['opsi_d']) ?></label>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if($soal_essay && $soal_essay->num_rows > 0): ?>
                <h3 class="section-title"><i class="fa-solid fa-pen-nib"></i> Tulis Sejarah</h3>
                <?php while($row = $soal_essay->fetch_assoc()): ?>
                    <div class="question-block">
                        <div class="q-text"><?= htmlspecialchars($row['pertanyaan']) ?></div>
                        <input type="text" name="essay[<?= $row['id'] ?>]" placeholder="Pahat jawabanmu di sini..." required>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if(($soal_tf && $soal_tf->num_rows > 0) || !empty($soal_match_arr) || ($soal_pg && $soal_pg->num_rows > 0) || ($soal_essay && $soal_essay->num_rows > 0)): ?>
                <button type="submit" name="submit_kompetisi" class="btn-submit pulse-anim">
                    <i class="fa-solid fa-skull"></i> GOMU GOMU NO... BAZOOKA! (Submit)
                </button>
            <?php else: ?>
                <div style="text-align:center; padding: 50px 0;">
                    <i class="fa-solid fa-box-open" style="font-size: 5rem; color: var(--pirate-black); margin-bottom: 20px; animation: bounce 2s infinite;"></i>
                    <h3 style="font-family: 'Rye', serif; color: var(--luffy-red); font-size: 3rem;">Harta Karun Kosong!</h3>
                    <p style="font-size: 1.3rem; font-weight: bold;">One Piece belum tersedia di database.</p>
                </div>
            <?php endif; ?>
        </form>
    </div>

</div>

</body>
</html>