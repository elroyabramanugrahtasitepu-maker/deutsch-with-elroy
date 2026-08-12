<?php
session_start();

// Matikan exception otomatis MySQLi agar query yang salah tidak menyebabkan Fatal Error 500
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}



$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { 
    die("Koneksi Database Gagal: " . $conn->connect_error); 
}
$conn->set_charset("utf8mb4");

$user_id = (int)$_SESSION['user_id'];

/* =========================================================
   HELPER AMAN: MENCEGAH PHP CRASH / ERROR 500
========================================================= */
function safe_get_val($conn, $sql) {
    try {
        $res = $conn->query($sql);
        if ($res && $row = $res->fetch_row()) {
            return (int)($row[0] ?? 0);
        }
    } catch (Throwable $e) {
        return 0;
    }
    return 0;
}

/* =========================================================
   HELPER OTOMATIS: DETEKSI KOLOM TABEL PROGRESS BAHASA
========================================================= */
function get_progress_count($conn, $table, $user_id) {
    try {
        $check_tbl = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$check_tbl || $check_tbl->num_rows == 0) return 0;

        $cols_res = $conn->query("SHOW COLUMNS FROM `$table`");
        if (!$cols_res) return 0;

        $cols = [];
        while ($c = $cols_res->fetch_assoc()) {
            $cols[] = strtolower($c['Field']);
        }

        $id_col = null;
        $possible_cols = ['soal_id', 'id_soal', 'question_id', 'latihan_id', 'id_pg', 'id_tf', 'id_match', 'id_essay'];
        foreach ($possible_cols as $p) {
            if (in_array($p, $cols)) {
                $id_col = $p;
                break;
            }
        }

        if ($id_col) {
            $sql = "SELECT COUNT(DISTINCT `$id_col`) FROM `$table` WHERE `user_id` = $user_id AND `is_correct` = 1";
        } else {
            $sql = "SELECT COUNT(*) FROM `$table` WHERE `user_id` = $user_id AND `is_correct` = 1";
        }

        return safe_get_val($conn, $sql);
    } catch (Throwable $e) {
        return 0;
    }
}

/* =========================================================
   1. OTOMATIS BUAT TABEL LOG & STATISTIK JIKA BELUM ADA
========================================================= */
$conn->query("CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    language VARCHAR(10) DEFAULT 'de',
    duration_seconds INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS user_daily_stats (
    user_id INT(11) NOT NULL,
    log_date DATE NOT NULL,
    time_spent_seconds INT DEFAULT 0,
    exercises_completed INT DEFAULT 0,
    stories_read INT DEFAULT 0,
    PRIMARY KEY (user_id, log_date)
)");

/* =========================================================
   2. ENDPOINT AJAX: CATAT AKTIVITAS & DURASI WAKTU
========================================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    if ($_POST['ajax_action'] == 'ping_duration') {
        $seconds = isset($_POST['seconds']) ? (int)$_POST['seconds'] : 0;
        if ($seconds > 0) {
            $conn->query("INSERT INTO user_daily_stats (user_id, log_date, time_spent_seconds) 
                          VALUES ($user_id, '$today', $seconds) 
                          ON DUPLICATE KEY UPDATE time_spent_seconds = time_spent_seconds + $seconds");
        }
        echo json_encode(['status' => 'success']);
        exit();
    }
    
    if ($_POST['ajax_action'] == 'log_event') {
        $type = $conn->real_escape_string($_POST['type'] ?? 'general');
        $desc = $conn->real_escape_string($_POST['desc'] ?? '');
        $lang = $conn->real_escape_string($_POST['lang'] ?? 'de');
        
        $conn->query("INSERT INTO user_activity_logs (user_id, activity_type, description, language) 
                      VALUES ($user_id, '$type', '$desc', '$lang')");
        
        if ($type == 'read_story') {
            $conn->query("INSERT INTO user_daily_stats (user_id, log_date, stories_read) 
                          VALUES ($user_id, '$today', 1) 
                          ON DUPLICATE KEY UPDATE stories_read = stories_read + 1");
        } elseif ($type == 'exercise') {
            $conn->query("INSERT INTO user_daily_stats (user_id, log_date, exercises_completed) 
                          VALUES ($user_id, '$today', 1) 
                          ON DUPLICATE KEY UPDATE exercises_completed = exercises_completed + 1");
        }
        echo json_encode(['status' => 'logged']);
        exit();
    }
}

/* =========================================================
   3. LOGIKA UPDATE PROFIL (NAMA & FOTO)
========================================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_nama'])) {
        $nama = $conn->real_escape_string($_POST['nama']);
        $conn->query("UPDATE users SET nama = '$nama' WHERE id = $user_id");
        $_SESSION['nama'] = $nama;
    }
    
    if (isset($_FILES['foto']['name']) && $_FILES['foto']['name'] != "") {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = "profile_" . $user_id . "_" . time() . ".jpg";
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $file_name)) {
            $conn->query("UPDATE users SET foto = '$file_name' WHERE id = $user_id");
        }
    }
}

$user_res = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_data = ($user_res && $user_res->num_rows > 0) ? $user_res->fetch_assoc() : ['nama' => 'User', 'foto' => ''];
$foto_path = (!empty($user_data['foto']) && file_exists("uploads/" . $user_data['foto'])) 
             ? "uploads/" . $user_data['foto'] 
             : "https://ui-avatars.com/api/?name=" . urlencode($user_data['nama'] ?? 'User') . "&background=ee4d2d&color=fff&size=300";

/* =========================================================
   4. LOGIKA RINGKASAN HARI INI
========================================================= */
$today_date = date('Y-m-d');
$today_seconds = safe_get_val($conn, "SELECT time_spent_seconds FROM user_daily_stats WHERE user_id = $user_id AND log_date = '$today_date'");
$today_minutes = round($today_seconds / 60);
$today_exercises = safe_get_val($conn, "SELECT exercises_completed FROM user_daily_stats WHERE user_id = $user_id AND log_date = '$today_date'");
$today_stories = safe_get_val($conn, "SELECT stories_read FROM user_daily_stats WHERE user_id = $user_id AND log_date = '$today_date'");

/* =========================================================
   5. PERHITUNGAN PROGRES PER 3 FAKULTAS (JERMAN, INGGRIS, JEPANG)
========================================================= */

// --- 5A. FAKULTAS JERMAN ---
$stats_de_config = [
    ['label' => 'PG', 'table' => 'latihan_soal', 'where' => 'tipe="pilihan_ganda"'],
    ['label' => 'ESSAI', 'table' => 'latihan_soal', 'where' => 'tipe="essai"'],
    ['label' => 'ARTIKEL', 'table' => 'latihan_artikel', 'where' => '1=1'],
    ['label' => 'MODAL', 'table' => 'latihan_modalverben', 'where' => '1=1'],
    ['label' => 'PUZZLE', 'table' => 'latihan_satzbau', 'where' => '1=1']
];
$stats_de = []; $chart_de = []; $total_benar_de = 0;
foreach ($stats_de_config as $s) {
    $tot = safe_get_val($conn, "SELECT COUNT(*) FROM {$s['table']} WHERE {$s['where']}");
    $q = "SELECT COUNT(DISTINCT p.soal_id) FROM user_progress p JOIN {$s['table']} t ON p.soal_id = t.id WHERE p.user_id = $user_id AND p.is_correct = 1";
    if ($s['label'] == 'PG') $q .= " AND t.tipe = 'pilihan_ganda'";
    if ($s['label'] == 'ESSAI') $q .= " AND t.tipe = 'essai'";
    $ben = safe_get_val($conn, $q);
    $stats_de[] = ['label' => $s['label'], 'total' => $tot, 'benar' => $ben];
    $chart_de[] = $ben;
    $total_benar_de += $ben;
}

// --- 5B. FAKULTAS INGGRIS ---
$stats_en_config = [
    ['label' => 'PG', 'main' => 'latihan_en_pg', 'prog' => 'latihan_en_pg_progress'],
    ['label' => 'B/S', 'main' => 'latihan_en_tf', 'prog' => 'latihan_en_tf_progress'],
    ['label' => 'MATCH', 'main' => 'latihan_en_match', 'prog' => 'latihan_en_match_progress'],
    ['label' => 'ESSAY', 'main' => 'latihan_en_essay', 'prog' => 'latihan_en_essay_progress']
];
$stats_en = []; $chart_en = []; $total_benar_en = 0;
foreach ($stats_en_config as $s) {
    $tot = safe_get_val($conn, "SELECT COUNT(*) FROM {$s['main']}");
    $ben = get_progress_count($conn, $s['prog'], $user_id);
    $stats_en[] = ['label' => $s['label'], 'total' => $tot, 'benar' => $ben];
    $chart_en[] = $ben;
    $total_benar_en += $ben;
}

// --- 5C. FAKULTAS JEPANG ---
$stats_jp_config = [
    ['label' => 'PG', 'main' => 'latihan_jp_pg', 'prog' => 'latihan_jp_pg_progress'],
    ['label' => 'B/S', 'main' => 'latihan_jp_tf', 'prog' => 'latihan_jp_tf_progress'],
    ['label' => 'MATCH', 'main' => 'latihan_jp_match', 'prog' => 'latihan_jp_match_progress'],
    ['label' => 'ESSAY', 'main' => 'latihan_jp_essay', 'prog' => 'latihan_jp_essay_progress']
];
$stats_jp = []; $chart_jp = []; $total_benar_jp = 0;
foreach ($stats_jp_config as $s) {
    $tot = safe_get_val($conn, "SELECT COUNT(*) FROM {$s['main']}");
    $ben = get_progress_count($conn, $s['prog'], $user_id);
    $stats_jp[] = ['label' => $s['label'], 'total' => $tot, 'benar' => $ben];
    $chart_jp[] = $ben;
    $total_benar_jp += $ben;
}

// TOTAL SOAL BENAR DARI KETIGA FAKULTAS
$total_soal_benar = $total_benar_de + $total_benar_en + $total_benar_jp;

// --- GRAFIK MINGGUAN ---
$weekly_labels = [];
$weekly_minutes = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $weekly_labels[] = date('d M', strtotime($d));
    $sec = safe_get_val($conn, "SELECT time_spent_seconds FROM user_daily_stats WHERE user_id = $user_id AND log_date = '$d'");
    $weekly_minutes[] = round($sec / 60);
}

// Riwayat Aktivitas Terbaru
$recent_logs = $conn->query("SELECT * FROM user_activity_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 8");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | DeutschAktiv</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --shopee-orange: #ee4d2d; 
            --bg-gray: #f5f5f5; 
            --border: #dbdbdb; 
            --text-dark: #333; 
        }

        * { box-sizing: border-box; }

        html, body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-gray); 
            margin: 0; 
            padding: 0; 
            color: var(--text-dark);
            width: 100%;
            overflow-x: hidden;
        }
        
        .top-nav { 
            background: var(--shopee-orange); 
            padding: 12px 5%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            color: white; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            width: 100%;
        }
        .logo { font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; text-decoration: none; color: white; }

        .wrapper { 
            display: grid; 
            grid-template-columns: 240px minmax(0, 1fr); 
            gap: 20px; 
            padding: 20px 5%; 
            max-width: 1400px; 
            margin: auto; 
            width: 100%;
        }

        .sidebar { background: white; border-radius: 6px; padding: 20px 0; height: fit-content; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .sidebar-group { margin-bottom: 15px; border-bottom: 1px solid #f5f5f5; padding-bottom: 10px; }
        .sidebar-title { padding: 10px 25px; font-weight: 700; font-size: 0.85rem; color: #333; }
        .sidebar-item { padding: 12px 25px; display: flex; align-items: center; gap: 15px; color: #555; text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
        .sidebar-item:hover { color: var(--shopee-orange); background: #fafafa; }
        .sidebar-item.active { color: var(--shopee-orange); border-left: 4px solid var(--shopee-orange); padding-left: 21px; background: #fffcfb; font-weight: 600; }

        .main-panel { 
            background: white; 
            border-radius: 6px; 
            padding: 25px; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
            min-width: 0; 
            overflow: hidden;
        }
        .panel-header { border-bottom: 1px solid #efefef; padding-bottom: 15px; margin-bottom: 25px; }
        .panel-header h2 { margin: 0; font-size: 1.3rem; font-weight: 700; }

        .today-banner {
            background: linear-gradient(135deg, #FF6B6B 0%, #EE4D2D 100%);
            color: white; padding: 20px; border-radius: 8px; margin-bottom: 25px;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px;
            box-shadow: 0 4px 15px rgba(238, 77, 45, 0.2);
            width: 100%;
        }
        .today-card { background: rgba(255, 255, 255, 0.18); padding: 15px; border-radius: 6px; backdrop-filter: blur(5px); }
        .today-card .val { font-size: 1.5rem; font-weight: 800; display: block; }
        .today-card .lbl { font-size: 0.78rem; font-weight: 600; opacity: 0.95; }

        .faculty-tabs {
            display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #efefef; padding-bottom: 10px;
        }
        .tab-btn {
            padding: 10px 20px; border: none; background: #f5f5f5; color: #666; font-weight: 700;
            border-radius: 6px; cursor: pointer; transition: 0.3s; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;
        }
        .tab-btn:hover { background: #eee; color: #333; }
        .tab-btn.active { background: var(--shopee-orange); color: white; box-shadow: 0 4px 10px rgba(238, 77, 45, 0.3); }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .form-content { 
            display: grid; 
            grid-template-columns: minmax(0, 1fr) 260px; 
            gap: 30px; 
            min-width: 0;
        }

        form { min-width: 0; }

        .info-row { 
            display: grid; 
            grid-template-columns: 120px minmax(0, 1fr); 
            gap: 20px; 
            margin-bottom: 20px; 
            align-items: flex-start; 
            min-width: 0;
        }
        .label-text { text-align: right; font-size: 0.88rem; color: rgba(85, 85, 85, 0.85); padding-top: 10px; font-weight: 600; }
        .input-field { border: 1px solid var(--border); padding: 12px; border-radius: 4px; width: 100%; outline: none; transition: 0.3s; font-size: 0.9rem; }
        .input-field:focus { border-color: var(--shopee-orange); }

        .avatar-box { text-align: center; border-left: 1px solid #efefef; padding: 0 10px; display: flex; flex-direction: column; align-items: center; }
        .avatar-circle { width: 110px; height: 110px; border-radius: 50%; border: 2px solid #eee; margin-bottom: 15px; object-fit: cover; }
        .btn-upload { background: white; border: 1px solid var(--border); padding: 8px 16px; font-size: 0.85rem; cursor: pointer; border-radius: 4px; font-weight: 600; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 8px; margin-bottom: 15px; width: 100%; }
        .stat-card { border: 1px solid #f0f0f0; padding: 10px 5px; text-align: center; border-radius: 6px; background: #fafafa; }
        .stat-card .val { font-weight: 700; color: var(--shopee-orange); font-size: 1rem; display: block; }
        .stat-card .lbl { font-size: 0.68rem; color: #888; font-weight: 700; }

        .btn-simpan { background: var(--shopee-orange); color: white; border: none; padding: 12px 35px; cursor: pointer; border-radius: 4px; font-weight: 700; font-size: 0.9rem; transition: 0.2s; }
        .btn-simpan:hover { background: #d73a1d; }

        .timeline-container { margin-top: 25px; border-top: 1px solid #efefef; padding-top: 20px; width: 100%; }
        .timeline-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 15px; color: #333; display: flex; align-items: center; gap: 8px; }
        .timeline-list { list-style: none; padding: 0; margin: 0; }
        .timeline-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 5px; border-bottom: 1px dashed #eee; font-size: 0.85rem; }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-tag { padding: 3px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .tag-story { background: #E3F2FD; color: #1976D2; }
        .tag-exercise { background: #E8F5E9; color: #2E7D32; }

        .chart-box {
            background: #fff; 
            padding: 12px; 
            border: 1px solid #f0f0f0; 
            border-radius: 6px; 
            position: relative; 
            width: 100%; 
            min-width: 0; 
            overflow: hidden;
        }

        @media (max-width: 900px) {
            .wrapper { grid-template-columns: 1fr; padding: 15px 3%; }
            .form-content { grid-template-columns: 1fr; gap: 20px; }
            .avatar-box { border-left: none; border-top: 1px solid #efefef; padding-top: 25px; order: -1; }
            .info-row { grid-template-columns: 90px minmax(0, 1fr); gap: 15px; }
            .label-text { text-align: left; }
            .faculty-tabs { flex-wrap: wrap; }
            .tab-btn { flex: 1; text-align: center; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="logo"><i class="fa-solid fa-shop"></i> DeutschAktiv Center</a>
    <div style="font-size: 0.85rem;"><i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars($user_data['nama'] ?? 'User') ?></div>
</div>

<div class="wrapper">
    <div class="sidebar">
        <div class="sidebar-group">
            <div class="sidebar-title">Akun Saya</div>
            <a href="user_profile.php" class="sidebar-item active"><i class="fa-regular fa-user"></i> Profil & Aktivitas</a>
            <a href="logout.php" class="sidebar-item" style="color: #ee4d2d;"><i class="fa-solid fa-power-off"></i> Keluar</a>
        </div>
        <div class="sidebar-group">
            <div class="sidebar-title">Navigasi Utama</div>
            <a href="index.php" class="sidebar-item"><i class="fa-solid fa-house"></i> Grand Lobby</a>
            <a href="english.php" class="sidebar-item"><i class="fa-solid fa-language"></i> Fakultas Inggris</a>
            <a href="deutsch.php" class="sidebar-item"><i class="fa-solid fa-book"></i> Fakultas Jerman</a>
            <a href="japan.php" class="sidebar-item"><i class="fa-solid fa-torii-gate"></i> Fakultas Jepang</a>
        </div>
    </div>

    <div class="main-panel">
        <div class="panel-header">
            <h2>Profil & Catatan Belajar</h2>
            <p style="margin: 5px 0 0 0; color: #777; font-size: 0.85rem;">Pantau durasi belajar, pengerjaan soal, dan pembacaan cerita per fakultas bahasa.</p>
        </div>

        <!-- BANNER AKTIVITAS HARI INI -->
        <div class="today-banner">
            <div class="today-card">
                <span class="val"><?= $today_minutes ?> <small style="font-size:0.85rem">menit</small></span>
                <span class="lbl"><i class="fa-solid fa-stopwatch"></i> Durasi Hari Ini</span>
            </div>
            <div class="today-card">
                <span class="val"><?= $today_exercises ?></span>
                <span class="lbl"><i class="fa-solid fa-pen-to-square"></i> Soal Dikerjakan</span>
            </div>
            <div class="today-card">
                <span class="val"><?= $today_stories ?></span>
                <span class="lbl"><i class="fa-solid fa-book-open"></i> Cerita/Buku Dibaca</span>
            </div>
            <div class="today-card">
                <span class="val"><?= $total_soal_benar ?></span>
                <span class="lbl"><i class="fa-solid fa-circle-check"></i> Total Jawaban Benar</span>
            </div>
        </div>

        <div class="form-content">
            <form action="" method="POST">
                <div class="info-row">
                    <div class="label-text">Nama Lengkap</div>
                    <input type="text" name="nama" class="input-field" value="<?= htmlspecialchars($user_data['nama'] ?? 'User') ?>" required>
                </div>
                
                <div class="info-row">
                    <div class="label-text">Progres Bahasa</div>
                    <div style="width: 100%; min-width: 0;">
                        
                        <!-- TOMBOL TAB 3 FAKULTAS -->
                        <div class="faculty-tabs">
                            <button type="button" class="tab-btn active" onclick="switchFaculty(event, 'jerman')">
                                <i class="fa-solid fa-book"></i> Jerman
                            </button>
                            <button type="button" class="tab-btn" onclick="switchFaculty(event, 'inggris')">
                                <i class="fa-solid fa-language"></i> Inggris
                            </button>
                            <button type="button" class="tab-btn" onclick="switchFaculty(event, 'jepang')">
                                <i class="fa-solid fa-torii-gate"></i> Jepang
                            </button>
                        </div>

                        <!-- TAB 1: JERMAN -->
                        <div id="tab-jerman" class="tab-content active">
                            <div class="stats-grid">
                                <?php foreach($stats_de as $ds): ?>
                                <div class="stat-card">
                                    <span class="val"><?= $ds['benar'] ?>/<?= $ds['total'] ?></span>
                                    <span class="lbl"><?= $ds['label'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="chart-box" style="height: 200px;">
                                <canvas id="chartJerman"></canvas>
                            </div>
                        </div>

                        <!-- TAB 2: INGGRIS -->
                        <div id="tab-inggris" class="tab-content">
                            <div class="stats-grid">
                                <?php foreach($stats_en as $ds): ?>
                                <div class="stat-card">
                                    <span class="val"><?= $ds['benar'] ?>/<?= $ds['total'] ?></span>
                                    <span class="lbl"><?= $ds['label'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="chart-box" style="height: 200px;">
                                <canvas id="chartInggris"></canvas>
                            </div>
                        </div>

                        <!-- TAB 3: JEPANG -->
                        <div id="tab-jepang" class="tab-content">
                            <div class="stats-grid">
                                <?php foreach($stats_jp as $ds): ?>
                                <div class="stat-card">
                                    <span class="val"><?= $ds['benar'] ?>/<?= $ds['total'] ?></span>
                                    <span class="lbl"><?= $ds['label'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="chart-box" style="height: 200px;">
                                <canvas id="chartJepang"></canvas>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="info-row">
                    <div class="label-text">Grafik 7 Hari</div>
                    <div class="chart-box" style="height: 180px;">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>

                <div class="info-row">
                    <div></div>
                    <button type="submit" name="update_nama" class="btn-simpan">Simpan Profil</button>
                </div>
            </form>

            <div class="avatar-box">
                <img src="<?= $foto_path ?>" class="avatar-circle">
                <form action="" method="POST" enctype="multipart/form-data" id="form-foto">
                    <input type="file" id="foto-upload" name="foto" style="display:none" onchange="document.getElementById('form-foto').submit()">
                    <label for="foto-upload" class="btn-upload">Pilih Gambar</label>
                </form>
                <div style="font-size: 0.75rem; color: #999; margin-top: 15px; line-height: 1.5;">
                    Ukuran gambar: maks. 1 MB<br>Format gambar: .JPEG, .PNG
                </div>
            </div>
        </div>

        <!-- TIMELINE RIWAYAT AKTIVITAS TERKINI -->
        <div class="timeline-container">
            <div class="timeline-title"><i class="fa-solid fa-clock-rotate-left" style="color: var(--shopee-orange);"></i> Riwayat Aktivitas Terkini</div>
            <ul class="timeline-list">
                <?php if ($recent_logs && $recent_logs->num_rows > 0): ?>
                    <?php while($log = $recent_logs->fetch_assoc()): ?>
                        <li class="timeline-item">
                            <div>
                                <span class="timeline-tag <?= ($log['activity_type'] == 'read_story') ? 'tag-story' : 'tag-exercise' ?>">
                                    <?= strtoupper(str_replace('_', ' ', $log['activity_type'])) ?> (<?= strtoupper($log['language']) ?>)
                                </span>
                                <span style="margin-left: 8px; font-weight: 600; color: #444;">
                                    <?= htmlspecialchars($log['description']) ?>
                                </span>
                            </div>
                            <span style="color: #999; font-size: 0.75rem;">
                                <?= date('d M Y, H:i', strtotime($log['created_at'])) ?>
                            </span>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="timeline-item" style="color: #999;">Belum ada aktivitas kerekam hari ini. Mulailah membaca atau mengerjakan soal!</li>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</div>

<script>
function switchFaculty(e, faculty) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

    e.currentTarget.classList.add('active');
    document.getElementById('tab-' + faculty).classList.add('active');
}

// --- CHART 1: FAKULTAS JERMAN ---
new Chart(document.getElementById('chartJerman').getContext('2d'), {
    type: 'line', 
    data: {
        labels: ['PG', 'ESSAI', 'ARTIKEL', 'MODAL', 'PUZZLE'],
        datasets: [{
            label: 'Jawaban Benar',
            data: <?= json_encode($chart_de) ?>,
            borderColor: '#ee4d2d',
            backgroundColor: 'rgba(238, 77, 45, 0.1)',
            borderWidth: 3, fill: true, tension: 0.4, pointRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
});

// --- CHART 2: FAKULTAS INGGRIS ---
new Chart(document.getElementById('chartInggris').getContext('2d'), {
    type: 'line', 
    data: {
        labels: ['PG', 'B/S', 'MATCH', 'ESSAY'],
        datasets: [{
            label: 'Jawaban Benar',
            data: <?= json_encode($chart_en) ?>,
            borderColor: '#003049',
            backgroundColor: 'rgba(0, 48, 73, 0.1)',
            borderWidth: 3, fill: true, tension: 0.4, pointRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
});

// --- CHART 3: FAKULTAS JEPANG ---
new Chart(document.getElementById('chartJepang').getContext('2d'), {
    type: 'line', 
    data: {
        labels: ['PG', 'B/S', 'MATCH', 'ESSAY'],
        datasets: [{
            label: 'Jawaban Benar',
            data: <?= json_encode($chart_jp) ?>,
            borderColor: '#D32F2F',
            backgroundColor: 'rgba(211, 47, 47, 0.1)',
            borderWidth: 3, fill: true, tension: 0.4, pointRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
});

// --- CHART 4: MINGGUAN DURASI ---
new Chart(document.getElementById('weeklyChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($weekly_labels) ?>,
        datasets: [{
            label: 'Durasi (Menit)',
            data: <?= json_encode($weekly_minutes) ?>,
            backgroundColor: '#FF6B6B',
            borderRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
});

// --- PING OTOMATIS CATAT WAKTU BELAJAR SETIAP 30 DETIK ---
setInterval(() => {
    fetch('user_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax_action=ping_duration&seconds=30'
    });
}, 30000);
</script>

</body>
</html>