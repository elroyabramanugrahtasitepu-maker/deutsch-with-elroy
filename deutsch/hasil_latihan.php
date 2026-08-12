<?php
session_start();

// 1. Koneksi Database

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid   = (int)$_SESSION['user_id'];
$level = trim($_GET['level'] ?? '');
$tipe  = trim($_GET['tipe'] ?? '');

if (empty($level)) {
    header("Location: latihan.php");
    exit();
}

// --- LOGIKA PENENTUAN TABEL & QUERY PREPARED STATEMENT ---
if ($level === 'MODALVERBEN') {
    $table_name = 'latihan_modalverben';
    $stmt = $conn->prepare("SELECT p.*, s.pertanyaan, s.jawaban AS jawaban_benar, s.penjelasan 
                            FROM user_progress p 
                            JOIN $table_name s ON p.soal_id = s.id 
                            WHERE p.user_id = ? 
                            ORDER BY p.created_at DESC LIMIT 15");
    $stmt->bind_param("i", $uid);

} elseif ($level === 'PUZZLE') {
    $table_name = 'latihan_satzbau';
    $stmt = $conn->prepare("SELECT p.*, s.kalimat_acak AS pertanyaan, s.kalimat_benar AS jawaban_benar 
                            FROM user_progress p 
                            JOIN $table_name s ON p.soal_id = s.id 
                            WHERE p.user_id = ? 
                            ORDER BY p.created_at DESC LIMIT 15");
    $stmt->bind_param("i", $uid);

} elseif ($level === 'HOREN') {
    $table_name = 'latihan_horen';
    $stmt = $conn->prepare("SELECT p.*, s.pertanyaan, s.jawaban AS jawaban_benar 
                            FROM user_progress p 
                            JOIN $table_name s ON p.soal_id = s.id 
                            WHERE p.user_id = ? 
                            ORDER BY p.created_at DESC LIMIT 15");
    $stmt->bind_param("i", $uid);

} else {
    $table_name = 'latihan_soal';
    $stmt = $conn->prepare("SELECT p.*, s.pertanyaan, s.jawaban_benar 
                            FROM user_progress p 
                            JOIN $table_name s ON p.soal_id = s.id 
                            WHERE p.user_id = ? AND s.level = ? AND s.tipe = ? 
                            ORDER BY p.created_at DESC LIMIT 15");
    $stmt->bind_param("iss", $uid, $level, $tipe);
}

$stmt->execute();
$result = $stmt->get_result();

$total_soal = $result->num_rows;
$benar = 0;
$data_hasil = [];

while ($row = $result->fetch_assoc()) {
    if ((int)$row['is_correct'] === 1) {
        $benar++;
    }
    $data_hasil[] = $row;
}

$skor = ($total_soal > 0) ? round(($benar / $total_soal) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ergebnis | DeutschAktiv</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ae0001; --secondary: #ffcf00; --dark: #1a1a1a;
            --bg: #f8fafc; --white: #ffffff; --green: #22c55e; --red: #ef4444;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); 
            margin: 0; padding: 20px 0; color: var(--dark);
        }
        .container { width: 92%; max-width: 800px; margin: auto; }
        .score-card {
            background: var(--white); border-radius: 30px; padding: 40px 20px;
            text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #eee; margin-bottom: 30px;
            position: relative; overflow: hidden;
        }
        .score-card::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 8px;
            background: linear-gradient(to right, #000 33%, var(--primary) 33%, var(--primary) 66%, var(--secondary) 66%);
        }
        .score-circle {
            width: 120px; height: 120px; border-radius: 50%; border: 8px solid #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            margin: 20px auto; font-size: 2rem; font-weight: 800;
            color: var(--primary); border-top-color: var(--primary);
        }
        .result-item {
            background: var(--white); border-radius: 20px; padding: 20px;
            margin-bottom: 15px; border-left: 6px solid #ddd;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .correct { border-left-color: var(--green); }
        .wrong { border-left-color: var(--red); }
        .q-text { font-weight: 700; margin-bottom: 10px; display: block; }
        .ans-text { font-size: 0.9rem; margin: 5px 0; }
        
        .explanation-box {
            margin-top: 10px; padding: 12px; background: #f1f5f9;
            border-radius: 10px; font-size: 0.85rem; color: #475569;
            border-left: 3px solid var(--secondary);
        }

        .status-badge {
            font-size: 0.7rem; font-weight: 800; padding: 4px 10px;
            border-radius: 50px; text-transform: uppercase;
        }
        .nav-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn {
            flex: 1; padding: 18px; border-radius: 15px; text-align: center;
            text-decoration: none; font-weight: 800; font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-home { background: var(--dark); color: var(--secondary); }
        .btn-again { background: var(--primary); color: white; }
        @media (max-width: 600px) {
            .nav-group { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="score-card">
        <h3 style="margin:0; color: #64748b;">GESAMTERGEBNIS (<?= htmlspecialchars(strtoupper($level)) ?>)</h3>
        <div class="score-circle"><?= $skor ?>%</div>
        <h2 style="margin: 10px 0;"><?= $skor >= 70 ? 'Ausgezeichnet! 🎉' : 'Gib nicht auf! 💪' ?></h2>
        <p style="color: #64748b; font-size: 0.9rem;">Total progres: <strong><?= $benar ?></strong> benar dari <strong><?= $total_soal ?></strong> soal dikerjakan.</p>
    </div>

    <div class="results-list">
        <?php if ($total_soal > 0): ?>
            <?php foreach ($data_hasil as $index => $res): ?>
                <div class="result-item <?= $res['is_correct'] ? 'correct' : 'wrong' ?>">
                    <span class="q-text"><?= ($index + 1) . ". " . htmlspecialchars($res['pertanyaan']) ?></span>
                    
                    <div class="ans-text">
                        <span style="color: #64748b;">Jawaban kamu:</span> 
                        <strong style="color: <?= $res['is_correct'] ? 'var(--green)' : 'var(--red)' ?>;">
                            <?= htmlspecialchars($res['jawaban_user']) ?>
                        </strong>
                    </div>

                    <?php if (!$res['is_correct']): ?>
                        <div class="ans-text">
                            <span style="color: #64748b;">Kunci jawaban:</span> 
                            <strong style="color: var(--green);"><?= htmlspecialchars($res['jawaban_benar']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($res['penjelasan'])): ?>
                        <div class="explanation-box">
                            <strong>💡 Penjelasan:</strong> <?= htmlspecialchars($res['penjelasan']) ?>
                        </div>
                    <?php endif; ?>

                    <span class="status-badge" style="color: <?= $res['is_correct'] ? 'var(--green)' : 'var(--red)' ?>;">
                        <i class="fa-solid <?= $res['is_correct'] ? 'fa-check' : 'fa-xmark' ?>"></i> 
                        <?= $res['is_correct'] ? 'Richtig' : 'Falsch' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #64748b;">Belum ada data pengerjaan kuis yang ditemukan.</p>
        <?php endif; ?>
    </div>

    <div class="nav-group">
        <a href="latihan.php" class="btn btn-home">ZURÜCK ZUM MENÜ</a>
        <a href="latihan.php?level=<?= urlencode($level) ?>&tipe=<?= urlencode($tipe) ?>" class="btn btn-again">WEITER ÜBEN</a>
    </div>
</div>

</body>
</html>