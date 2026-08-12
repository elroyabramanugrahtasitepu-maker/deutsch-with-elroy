<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// 1. KONEKSI DATABASE

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// 2. LOGIKA SUBMIT JAWABAN & SKORING
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_exam'])) {
    $total_poin = 0;
    $jawaban_benar_count = 0;
    $total_soal = 0;
    $waktu_terpakai = isset($_POST['waktu_terpakai']) ? (int)$_POST['waktu_terpakai'] : 0;

    if (isset($_POST['ans']) && is_array($_POST['ans'])) {
        foreach($_POST['ans'] as $id_soal => $user_ans) {
            $id_soal = (int)$id_soal;
            $user_ans_clean = trim(mb_strtolower($user_ans, 'UTF-8'));
            
            // Query verifikasi jawaban dari database
            $stmt = $conn->prepare("SELECT jawaban_benar, poin FROM kompetisi_soal WHERE id = ?");
            $stmt->bind_param("i", $id_soal);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            if ($res) {
                $total_soal++;
                $db_ans_clean = trim(mb_strtolower($res['jawaban_benar'], 'UTF-8'));
                if ($user_ans_clean === $db_ans_clean && $user_ans_clean !== '') {
                    $total_poin += (int)$res['poin'];
                    $jawaban_benar_count++;
                }
            }
            $stmt->close();
        }
    }

    // Update poin user dan akumulasi skor untuk Dashboard/Leaderboard
    $stmt_update = $conn->prepare("UPDATE users SET total_poin = total_poin + ? WHERE id = ?");
    $stmt_update->bind_param("ii", $total_poin, $user_id);
    $stmt_update->execute();

    // Simpan riwayat ujian ke session untuk ditampilkan di modal hasil / dashboard
    $_SESSION['last_exam_result'] = [
        'poin' => $total_poin,
        'benar' => $jawaban_benar_count,
        'total' => $total_soal,
        'durasi' => $waktu_terpakai
    ];

    header("Location: kompetisi.php?status=success");
    exit();
}

// 3. AMBIL SEMUA SOAL DARI DATABASE (ACAK)
$query_soal = "SELECT * FROM kompetisi_soal ORDER BY RAND()";
$result = $conn->query($query_soal);
$soal_list = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { 
        $soal_list[] = $row; 
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deutsch Meisterschaft | Arena Tournament</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --gold: #FFCC00;
            --red: #DD0000;
            --black: #0a0a0c;
            --card-bg: rgba(22, 22, 28, 0.85);
            --border: rgba(255, 204, 0, 0.18);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --accent-glow: rgba(255, 204, 0, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--black);
            color: var(--text-main);
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(221, 0, 0, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 100%, rgba(255, 204, 0, 0.08) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* --- START SCREEN OVERLAY --- */
        #start-screen {
            position: fixed; inset: 0;
            background: rgba(10, 10, 12, 0.96);
            backdrop-filter: blur(15px);
            z-index: 9999;
            display: flex; flex-direction: column; 
            justify-content: center; align-items: center;
            text-align: center; padding: 20px;
        }

        .arena-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255, 204, 0, 0.1); border: 1px solid var(--gold);
            color: var(--gold); padding: 6px 16px; border-radius: 50px;
            font-size: 0.8rem; font-weight: 800; letter-spacing: 2px;
            margin-bottom: 20px; text-transform: uppercase;
        }

        .title-hero {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900; letter-spacing: 4px; color: #fff;
            text-shadow: 0 0 30px rgba(255,255,255,0.2);
        }

        .title-hero span { color: var(--gold); }

        .start-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-start {
            background: linear-gradient(135deg, var(--gold) 0%, #d4a000 100%);
            color: #000; border: none; padding: 18px 42px;
            font-family: 'Orbitron', sans-serif; font-size: 1.05rem;
            font-weight: 900; border-radius: 12px; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 30px var(--accent-glow);
            display: inline-flex; align-items: center; gap: 10px;
            text-decoration: none;
        }

        .btn-start:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 0 50px rgba(255, 204, 0, 0.5);
        }

        .btn-start-back {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid var(--border);
            padding: 18px 28px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-start-back:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            border-color: #fff;
        }

        /* --- TOP STICKY BAR --- */
        .top-nav {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(10, 10, 12, 0.85); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border); display: none;
        }

        .nav-content {
            max-width: 1200px; margin: 0 auto;
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 24px;
        }

        .btn-nav-back {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            color: var(--gold);
            width: 38px;
            height: 38px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            font-size: 0.95rem;
        }

        .btn-nav-back:hover {
            background: var(--red);
            color: #fff;
            border-color: var(--red);
        }

        .timer-box {
            font-family: 'Orbitron', sans-serif; font-size: 1.5rem;
            color: var(--gold); font-weight: 800;
            background: rgba(0,0,0,0.5); padding: 8px 18px;
            border-radius: 8px; border: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }

        .progress-bar-container {
            width: 100%; height: 4px; background: rgba(255,255,255,0.05);
        }

        #progress-bar {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, var(--red), var(--gold));
            transition: width 0.3s ease;
        }

        /* --- ARENA LAYOUT --- */
        .arena-wrapper {
            max-width: 1200px; margin: 30px auto; padding: 0 20px;
            display: none; gap: 24px;
        }

        .main-quiz-area { flex: 1; }

        .side-palette {
            width: 320px; position: sticky; top: 90px; height: fit-content;
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 20px; backdrop-filter: blur(10px);
        }

        /* --- QUESTION CARDS --- */
        .soal-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 28px; margin-bottom: 24px;
            backdrop-filter: blur(10px); transition: border-color 0.3s;
        }

        .soal-card:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 20px rgba(255, 204, 0, 0.1);
        }

        .card-header-meta {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 16px;
        }

        .q-badge {
            background: rgba(255, 204, 0, 0.15); color: var(--gold);
            font-size: 0.75rem; font-weight: 800; padding: 6px 14px;
            border-radius: 50px; text-transform: uppercase;
            border: 1px solid rgba(255, 204, 0, 0.2);
        }

        .q-text {
            font-size: 1.15rem; font-weight: 600; line-height: 1.6;
            color: var(--text-main); margin-bottom: 20px;
            white-space: pre-line;
        }

        .input-answer {
            width: 100%; background: rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 10px;
            padding: 14px 18px; color: var(--gold); font-size: 1.1rem;
            font-weight: 600; outline: none; transition: all 0.3s;
        }

        .input-answer:focus {
            border-color: var(--gold); background: #000;
            box-shadow: 0 0 15px rgba(255, 204, 0, 0.15);
        }

        /* --- PILIHAN GANDA (OPTION CARDS) --- */
        .pg-options-grid {
            display: flex; flex-direction: column; gap: 12px; margin-top: 15px;
        }

        .pg-option-card {
            display: flex; align-items: center; gap: 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 2px solid rgba(255, 255, 255, 0.08);
            padding: 14px 18px; border-radius: 12px;
            cursor: pointer; transition: all 0.25s ease;
            user-select: none;
        }

        .pg-option-card:hover {
            background: rgba(255, 204, 0, 0.08);
            border-color: rgba(255, 204, 0, 0.4);
        }

        .pg-option-card input[type="radio"] {
            display: none;
        }

        .pg-letter-badge {
            width: 34px; height: 34px; border-radius: 8px;
            background: rgba(255, 255, 255, 0.1); color: var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 0.95rem;
            transition: all 0.25s; flex-shrink: 0;
        }

        .pg-option-text {
            font-size: 1.05rem; font-weight: 600; color: var(--text-main);
        }

        .pg-option-card:has(input[type="radio"]:checked) {
            background: rgba(255, 204, 0, 0.15);
            border-color: var(--gold);
            box-shadow: 0 0 15px rgba(255, 204, 0, 0.2);
        }

        .pg-option-card:has(input[type="radio"]:checked) .pg-letter-badge {
            background: var(--gold); color: #000;
        }

        /* --- GERMAN UMLAUT TOOLBAR --- */
        .umlaut-bar {
            display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; align-items: center;
        }

        .umlaut-btn {
            background: rgba(255,255,255,0.08); border: 1px solid var(--border);
            color: var(--text-main); font-weight: 700; padding: 6px 12px;
            border-radius: 6px; cursor: pointer; transition: 0.2s;
        }

        .umlaut-btn:hover {
            background: var(--gold); color: #000;
        }

        /* --- PALETTE GRID --- */
        .grid-numbers {
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
            margin-top: 16px; max-height: 320px; overflow-y: auto; padding-right: 4px;
        }

        .num-box {
            aspect-ratio: 1; display: flex; align-items: center;
            justify-content: center; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
            font-size: 0.85rem; font-weight: 700; color: var(--text-muted);
            text-decoration: none; cursor: pointer; transition: 0.2s;
        }

        .num-box.answered {
            background: var(--gold); color: #000; border-color: var(--gold);
        }

        .btn-submit-exam {
            width: 100%; margin-top: 20px; background: var(--red);
            color: #fff; border: none; padding: 16px; border-radius: 10px;
            font-family: 'Orbitron', sans-serif; font-size: 0.95rem;
            font-weight: 800; cursor: pointer; transition: all 0.3s;
            letter-spacing: 1px;
        }

        .btn-submit-exam:hover {
            background: #ff1a1a; box-shadow: 0 0 20px rgba(221, 0, 0, 0.4);
        }

        @media (max-width: 900px) {
            .arena-wrapper { flex-direction: column; }
            .side-palette { width: 100%; position: static; order: -1; }
        }
    </style>
</head>
<body>

<!-- 1. START OVERLAY SCREEN -->
<div id="start-screen">
    <div class="arena-badge"><i class="fa-solid fa-trophy"></i> Offizielle Prüfung</div>
    <h1 class="title-hero">DEUTSCH <span>ARENA</span></h1>
    <p style="color: var(--text-muted); max-width: 500px; margin-top: 10px; line-height: 1.6;">
        Uji kemampuan bahasa Jermanmu secara real-time. Poin jawaban benar akan diakumulasikan langsung ke <strong>Leaderboard Utama</strong>.
    </p>

    <div class="start-actions">
        <!-- TOMBOL KEMBALI DI LAYAR UTAMA -->
        <a href="kompetisi.php" class="btn-start-back">
            <i class="fa-solid fa-arrow-left"></i> KEMBALI
        </a>
        <button class="btn-start" onclick="startArena()">
            <i class="fa-solid fa-play"></i> JETZT STARTEN
        </button>
    </div>
</div>

<!-- 2. TOP STICKY NAVIGATION -->
<div class="top-nav" id="topNav">
    <div class="nav-content">
        <div style="display: flex; align-items: center; gap: 14px;">
            <!-- TOMBOL KEMBALI KETIKA UJIAN BERLANGSUNG -->
            <button type="button" class="btn-nav-back" onclick="exitArena()" title="Kembali ke Dashboard">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div style="font-family: 'Orbitron'; font-weight: 800; font-size: 1rem; color: #fff;">
                ARENA <span style="color:var(--red)">//</span> MEISTERSCHAFT
            </div>
        </div>

        <div class="timer-box">
            <i class="fa-regular fa-clock" style="font-size: 1.1rem;"></i>
            <span id="timer-display">30:00</span>
        </div>
    </div>
    <div class="progress-bar-container">
        <div id="progress-bar"></div>
    </div>
</div>

<!-- 3. MAIN QUIZ ARENA -->
<div class="arena-wrapper" id="arenaMain">
    <main class="main-quiz-area">
        <form id="arenaForm" method="POST" action="">
            <input type="hidden" name="submit_exam" value="1">
            <input type="hidden" name="waktu_terpakai" id="waktu_terpakai_input" value="0">

            <?php if (!empty($soal_list)): ?>
                <?php foreach($soal_list as $index => $s): 
                    $q_type = strtolower($s['tipe'] ?? 'essai');
                    $raw_question = $s['pertanyaan'];
                    
                    // PARSER PILIHAN GANDA (Deteksi A), B), C), D))
                    $options = [];
                    $clean_q_text = $raw_question;
                    
                    if ($q_type === 'pilihan_ganda' || preg_match('/A\)\s*/i', $raw_question)) {
                        $parts = preg_split('/(?=\b[A-D]\))/i', $raw_question);
                        $clean_q_text = array_shift($parts); // Ambil teks soal utama
                        
                        foreach ($parts as $part) {
                            if (preg_match('/^([A-D])\)\s*(.*)/is', trim($part), $m)) {
                                $options[$m[1]] = trim($m[2]);
                            }
                        }
                    }
                ?>
                    <div class="soal-card" id="q-card-<?= $index + 1 ?>">
                        <div class="card-header-meta">
                            <span class="q-badge"><?= strtoupper(str_replace('_', ' ', $s['tipe'] ?? 'GRAMMATIK')) ?></span>
                            <span style="font-size: 0.85rem; color: var(--gold); font-weight: 700;">
                                +<?= $s['poin'] ?? 10 ?> POIN
                            </span>
                        </div>

                        <!-- Teks Pertanyaan Utama -->
                        <div class="q-text">
                            <strong style="color: var(--gold); margin-right: 8px;"><?= $index + 1 ?>.</strong><?= htmlspecialchars(trim($clean_q_text)) ?>
                        </div>

                        <!-- Audio Player untuk Soal Horen -->
                        <?php if($q_type === 'horen' && !empty($s['file_audio'])): ?>
                            <div style="margin-bottom: 15px;">
                                <audio controls style="width: 100%; filter: invert(90%);">
                                    <source src="uploads/audio/<?= $s['file_audio'] ?>" type="audio/mpeg">
                                </audio>
                            </div>
                        <?php endif; ?>

                        <!-- OPSI JAWABAN -->
                        <?php if(!empty($options)): ?>
                            <!-- PILIHAN GANDA (Radio Buttons) -->
                            <div class="pg-options-grid">
                                <?php foreach($options as $key => $opt_text): ?>
                                    <label class="pg-option-card">
                                        <input type="radio" 
                                               name="ans[<?= $s['id'] ?>]" 
                                               value="<?= $key ?>" 
                                               onchange="handleInputProgress(<?= $index + 1 ?>)">
                                        <div class="pg-letter-badge"><?= $key ?></div>
                                        <div class="pg-option-text"><?= htmlspecialchars($opt_text) ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- ESSAI & SUSUN KALIMAT (Input Teks) -->
                            <input type="text" 
                                   id="input-q-<?= $index + 1 ?>"
                                   name="ans[<?= $s['id'] ?>]" 
                                   class="input-answer" 
                                   placeholder="Tulis jawaban di sini..." 
                                   autocomplete="off" 
                                   oninput="handleInputProgress(<?= $index + 1 ?>)">

                            <!-- Virtual Umlaut Keyboard -->
                            <div class="umlaut-bar">
                                <span style="font-size: 0.75rem; color: var(--text-muted); margin-right: 4px;">Karakter Khusus:</span>
                                <?php foreach(['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'] as $char): ?>
                                    <button type="button" class="umlaut-btn" onclick="insertChar('input-q-<?= $index + 1 ?>', '<?= $char ?>')"><?= $char ?></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="soal-card" style="text-align: center; padding: 50px;">
                    <i class="fa-solid fa-triangle-exclamation fa-3x" style="color: var(--gold); margin-bottom: 15px;"></i>
                    <h3>Belum Ada Soal Tersedia</h3>
                    <p style="color: var(--text-muted); margin-top: 8px;">Silakan hubungi administrator untuk memperbarui database latihan kompetisi.</p>
                </div>
            <?php endif; ?>
        </form>
    </main>

    <!-- SIDEBAR NAVIGATOR -->
    <aside class="side-palette">
        <h4 style="font-family: 'Orbitron'; font-size: 0.9rem; color: var(--gold); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            NAVIGASI SOAL
            <span id="answered-count" style="font-size: 0.8rem; color: #fff;">0/<?= count($soal_list) ?></span>
        </h4>

        <div class="grid-numbers">
            <?php for($i = 1; $i <= count($soal_list); $i++): ?>
                <a href="#q-card-<?= $i ?>" class="num-box" id="nav-num-<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>

        <button type="button" class="btn-submit-exam" onclick="confirmSubmit()">
            <i class="fa-solid fa-paper-plane"></i> SUBMIT ARENA
        </button>
    </aside>
</div>

<script>
    const totalSoal = <?= count($soal_list) ?>;
    // Waktu default diatur 1800 detik (30 Menit)
    let timeRemaining = Math.max(600, totalSoal * 12); 
    let timerInterval = null;

    function startArena() {
        document.getElementById('start-screen').style.opacity = '0';
        setTimeout(() => {
            document.getElementById('start-screen').style.display = 'none';
            document.getElementById('topNav').style.display = 'block';
            document.getElementById('arenaMain').style.display = 'flex';
            runTimer();
        }, 300);
    }

    function exitArena() {
        if (confirm("Apakah Anda yakin ingin keluar dari Arena Kompetisi? Progres pengerjaan saat ini tidak akan disimpan.")) {
            window.location.href = "kompetisi.php";
        }
    }

    function runTimer() {
        const timerDisplay = document.getElementById('timer-display');
        const waktuInput = document.getElementById('waktu_terpakai_input');
        const initialTime = timeRemaining;

        timerInterval = setInterval(() => {
            timeRemaining--;
            let mins = Math.floor(timeRemaining / 60);
            let secs = timeRemaining % 60;

            timerDisplay.textContent = 
                (mins < 10 ? '0' + mins : mins) + ":" + 
                (secs < 10 ? '0' + secs : secs);

            waktuInput.value = initialTime - timeRemaining;

            if (timeRemaining <= 60) {
                timerDisplay.style.color = "var(--red)";
            }

            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                alert('Waktu telah habis! Jawaban Anda akan dikirimkan otomatis.');
                document.getElementById('arenaForm').submit();
            }
        }, 1000);
    }

    function handleInputProgress(qIndex) {
        const card = document.getElementById(`q-card-${qIndex}`);
        if (!card) return;

        const textInput = card.querySelector('input[type="text"]');
        const radioChecked = card.querySelector('input[type="radio"]:checked');
        const navBox = document.getElementById(`nav-num-${qIndex}`);

        let isAnswered = false;
        if (textInput && textInput.value.trim() !== '') {
            isAnswered = true;
        } else if (radioChecked) {
            isAnswered = true;
        }

        if (isAnswered) {
            navBox.classList.add('answered');
        } else {
            navBox.classList.remove('answered');
        }

        updateTotalProgress();
    }

    function updateTotalProgress() {
        let answeredCount = 0;
        for (let i = 1; i <= totalSoal; i++) {
            const card = document.getElementById(`q-card-${i}`);
            if (!card) continue;
            const textInput = card.querySelector('input[type="text"]');
            const radioChecked = card.querySelector('input[type="radio"]:checked');
            if ((textInput && textInput.value.trim() !== '') || radioChecked) {
                answeredCount++;
            }
        }

        document.getElementById('answered-count').textContent = `${answeredCount}/${totalSoal}`;
        const percent = totalSoal > 0 ? (answeredCount / totalSoal) * 100 : 0;
        document.getElementById('progress-bar').style.width = percent + "%";
    }

    function insertChar(inputId, char) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const text = input.value;
        
        input.value = text.substring(0, start) + char + text.substring(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + 1;
        
        const qIndex = inputId.replace('input-q-', '');
        handleInputProgress(qIndex);
    }

    function confirmSubmit() {
        let answeredCount = 0;
        for (let i = 1; i <= totalSoal; i++) {
            const card = document.getElementById(`q-card-${i}`);
            if (!card) continue;
            const textInput = card.querySelector('input[type="text"]');
            const radioChecked = card.querySelector('input[type="radio"]:checked');
            if ((textInput && textInput.value.trim() !== '') || radioChecked) {
                answeredCount++;
            }
        }

        if (confirm(`Anda baru menjawab ${answeredCount} dari ${totalSoal} soal. Yakin ingin menyelesaikan ujian?`)) {
            document.getElementById('arenaForm').submit();
        }
    }
</script>

</body>
</html>
<?php $conn->close(); ?>