<?php
session_start();
$host = "localhost"; $user = "u960862048_roy"; $pass = "Caracter_Cs321"; $db = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_POST['match_id']) && !isset($_GET['match_id'])) {
    header("Location: kompetisi.php"); exit();
}

$match_id = (int)($_POST['match_id'] ?? $_GET['match_id']);
$uid = $_SESSION['user_id'];

// --- LOGIKA AJAX CHECK (Dipanggil oleh JavaScript) ---
if (isset($_GET['check_ajax'])) {
    header('Content-Type: application/json');
    $match = $conn->query("SELECT * FROM kompetisi_matches WHERE id = $match_id")->fetch_assoc();
    $is_u1 = ($match['user_1'] == $uid);
    $skor_lawan = $is_u1 ? $match['skor_2'] : $match['skor_1'];
    
    echo json_encode([
        'status' => $match['status'],
        'skor_lawan' => $skor_lawan
    ]);
    exit();
}

// --- LOGIKA HITUNG SKOR (Hanya dijalankan saat POST dari Battle) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answers = $_POST['ans'] ?? [];
    $skor_anda = 0;
    foreach($answers as $key => $user_ans) {
        $parts = explode('_', $key);
        $tipe = $parts[0];
        $id_soal = $parts[1];
        $tabel = ($tipe == 'artikel') ? 'latihan_artikel' : (($tipe == 'modal') ? 'latihan_modalverben' : 'latihan_horen');
        $cek = $conn->query("SELECT jawaban FROM $tabel WHERE id = $id_soal")->fetch_assoc();
        if($cek && $cek['jawaban'] == $user_ans) { $skor_anda += 10; }
    }

    $match = $conn->query("SELECT * FROM kompetisi_matches WHERE id = $match_id")->fetch_assoc();
    $is_user_1 = ($match['user_1'] == $uid);
    $kolom_skor = $is_user_1 ? "skor_1" : "skor_2";
    $conn->query("UPDATE kompetisi_matches SET $kolom_skor = $skor_anda WHERE id = $match_id");

    // Re-fetch data terbaru
    $match = $conn->query("SELECT * FROM kompetisi_matches WHERE id = $match_id")->fetch_assoc();
    if($match['skor_1'] > 0 && $match['skor_2'] > 0) {
        $conn->query("UPDATE kompetisi_matches SET status = 'finished' WHERE id = $match_id");
    }
} else {
    // Jika user refresh halaman ini setelah beres tanding
    $match = $conn->query("SELECT * FROM kompetisi_matches WHERE id = $match_id")->fetch_assoc();
}

$is_user_1 = ($match['user_1'] == $uid);
$skor_anda = $is_user_1 ? $match['skor_1'] : $match['skor_2'];
$skor_lawan = $is_user_1 ? $match['skor_2'] : $match['skor_1'];
$opponent_id = $is_user_1 ? $match['user_2'] : $match['user_1'];
$opponent_name = $conn->query("SELECT nama FROM users WHERE id = $opponent_id")->fetch_assoc()['nama'] ?? 'Lawan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Battle Result | DeutschAktiv</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #ffcf00; --red: #ae0001; --neon: #00f2ff; }
        body { font-family: 'Poppins', sans-serif; background: #0f0f0f; color: white; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow-x: hidden; }
        
        .result-card { 
            width: 90%; max-width: 700px; background: #1a1a1a; padding: 40px; 
            border-radius: 24px; border: 2px solid rgba(255,207,0,0.3);
            box-shadow: 0 0 50px rgba(0,0,0,0.8), inset 0 0 20px rgba(255,207,0,0.05);
            text-align: center; position: relative;
        }

        .battle-title { font-size: 2.5rem; font-weight: 900; margin-bottom: 30px; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); text-shadow: 0 0 15px rgba(255,207,0,0.4); }

        .battle-grid { display: grid; grid-template-columns: 1fr 100px 1fr; align-items: center; margin: 40px 0; gap: 20px; }
        
        .player-box { background: rgba(255,255,255,0.05); padding: 25px; border-radius: 15px; border-bottom: 4px solid #333; transition: 0.3s; }
        .player-box.active { border-color: var(--gold); background: rgba(255,207,0,0.05); }
        .player-box small { font-size: 0.7rem; color: #888; text-transform: uppercase; letter-spacing: 2px; }
        .player-box strong { display: block; font-size: 1.2rem; margin: 5px 0; }
        
        .skor-val { font-size: 3.5rem; font-weight: 900; color: var(--gold); margin-top: 10px; }
        
        .vs-circle { width: 60px; height: 60px; background: var(--red); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; margin: 0 auto; box-shadow: 0 0 20px rgba(174,0,1,0.5); }

        #winner-area { margin-top: 30px; height: 100px; display: flex; align-items: center; justify-content: center; }
        
        .winner-box { 
            background: linear-gradient(45deg, var(--red), #eb4d4b); padding: 20px 50px; 
            border-radius: 50px; font-weight: 900; font-size: 1.8rem; 
            box-shadow: 0 10px 30px rgba(174,0,1,0.4);
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .waiting-text { color: #888; font-style: italic; animation: pulse 1.5s infinite; }

        @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }
        @keyframes bounceIn { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.05); } 70% { transform: scale(0.9); } 100% { transform: scale(1); opacity: 1; } }

        .btn-back { 
            display: inline-block; margin-top: 40px; padding: 15px 40px; 
            background: transparent; color: var(--gold); text-decoration: none; 
            font-weight: 800; border-radius: 12px; border: 2px solid var(--gold);
            transition: 0.3s;
        }
        .btn-back:hover { background: var(--gold); color: black; box-shadow: 0 0 20px rgba(255,207,0,0.3); }
    </style>
</head>
<body>

<div class="result-card">
    <div class="battle-title"><i class="fa-solid fa-trophy"></i> Result</div>
    
    <div class="battle-grid">
        <div class="player-box active">
            <small>You</small>
            <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
            <div class="skor-val"><?= $skor_anda ?></div>
        </div>

        <div class="vs-circle">VS</div>

        <div class="player-box" id="opponent-box">
            <small>Opponent</small>
            <strong id="opp-name"><?= htmlspecialchars($opponent_name) ?></strong>
            <div class="skor-val" id="opp-skor">
                <?= ($skor_lawan == 0 && $match['status'] != 'finished') ? '...' : $skor_lawan ?>
            </div>
        </div>
    </div>

    <div id="winner-area">
        <?php if($match['status'] == 'finished'): ?>
            <div class="winner-box">
                <?php 
                if($skor_anda > $skor_lawan) echo "🏆 YOU WIN!";
                elseif($skor_anda < $skor_lawan) echo "💀 YOU LOSE!";
                else echo "🤝 DRAW!";
                ?>
            </div>
        <?php else: ?>
            <div class="waiting-text"><i class="fa-solid fa-spinner fa-spin"></i> Lawan sedang berjuang, jangan beranjak...</div>
        <?php endif; ?>
    </div>

    <a href="kompetisi.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> BACK TO ARENA</a>
</div>

<script>
    const matchId = <?= $match_id ?>;
    const currentSkorAnda = <?= $skor_anda ?>;

    function checkOpponentProgress() {
        fetch(`proses_battle.php?match_id=${matchId}&check_ajax=1`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'finished' || data.skor_lawan > 0) {
                // Update Skor Lawan
                document.getElementById('opp-skor').innerText = data.skor_lawan;
                document.getElementById('opponent-box').classList.add('active');

                // Update Winner Area
                let winnerArea = document.getElementById('winner-area');
                let resultText = "";
                
                if (currentSkorAnda > data.skor_lawan) resultText = "🏆 YOU WIN!";
                else if (currentSkorAnda < data.skor_lawan) resultText = "💀 YOU LOSE!";
                else resultText = "🤝 DRAW!";

                winnerArea.innerHTML = `<div class="winner-box">${resultText}</div>`;
                
                // Berhenti polling
                clearInterval(pollInterval);
            }
        });
    }

    // Jalankan polling setiap 3 detik jika status belum finished
    <?php if($match['status'] != 'finished'): ?>
    const pollInterval = setInterval(checkOpponentProgress, 3000);
    <?php endif; ?>
</script>

</body>
</html>