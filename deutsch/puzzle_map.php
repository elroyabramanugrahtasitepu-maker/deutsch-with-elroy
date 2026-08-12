<?php
session_start();

// --- 1. KONEKSI & PROTEKSI ---

$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- LOGIKA CEK COOLDOWN LIHAT JAWABAN ---
$can_see_hint = true;
$remaining_time = "";

$user_q = $conn->query("SELECT last_puzzle_hint FROM users WHERE id = $user_id");
$user_hint_data = $user_q->fetch_assoc();

if ($user_hint_data['last_puzzle_hint']) {
    $last_hint = strtotime($user_hint_data['last_puzzle_hint']);
    $now = time();
    $diff = $now - $last_hint;
    $cooldown = 5 * 3600; 

    if ($diff < $cooldown) {
        $can_see_hint = false;
        $seconds_left = $cooldown - $diff;
        $hours = floor($seconds_left / 3600);
        $minutes = floor(($seconds_left % 3600) / 60);
        $remaining_time = ($hours > 0 ? $hours . "j " : "") . $minutes . "m";
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'see_answer' && $can_see_hint) {
    $conn->query("UPDATE users SET last_puzzle_hint = NOW() WHERE id = $user_id");
    header("Location: puzzle_map.php?show_ans=1");
    exit();
}

// Hitung Progress
$total_soal = $conn->query("SELECT COUNT(*) FROM latihan_satzbau")->fetch_row()[0];
$sudah_selesai = $conn->query("SELECT COUNT(*) FROM user_progress p JOIN latihan_satzbau s ON p.soal_id = s.id WHERE p.user_id = $user_id AND p.is_correct = 1")->fetch_row()[0];
$persentase = ($total_soal > 0) ? ($sudah_selesai / $total_soal) * 100 : 0;

$query = "SELECT * FROM latihan_satzbau 
          WHERE id NOT IN (SELECT soal_id FROM user_progress WHERE user_id = $user_id AND is_correct = 1)
          ORDER BY RAND() LIMIT 1";
$res = $conn->query($query);

if ($res->num_rows == 0) {
    $semua_selesai = true;
} else {
    $semua_selesai = false;
    $row = $res->fetch_assoc();
    $words = explode(',', $row['kata_acak']);
    shuffle($words);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Satzbau-Profi | DeutschAktiv</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #58cc02; 
            --primary-dark: #46a302;
            --bg: #ffffff; 
            --text: #3c3c3c; 
            --gray: #afafaf;
            --light-gray: #e5e5e5; 
            --purple: #ce82ff; 
            --danger: #ff4b4b;
            --sky: #1cb0f6;
        }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            color: var(--text); 
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Progress Bar Modern */
        .header-nav { 
            width: 100%; 
            max-width: 1000px; 
            padding: 25px 20px; 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            margin: 0 auto; 
            box-sizing: border-box;
        }
        .btn-close { text-decoration: none; color: var(--gray); font-size: 1.8rem; transition: 0.2s; }
        .progress-container { flex: 1; height: 16px; background: var(--light-gray); border-radius: 20px; overflow: hidden; }
        .progress-bar { 
            height: 100%; 
            background: linear-gradient(90deg, #58cc02, #93f94a); 
            width: <?= $persentase ?>%; 
            transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1); 
            border-radius: 20px; 
            position: relative;
        }
        .progress-bar::after {
            content: ''; position: absolute; top: 4px; left: 10%; width: 80%; height: 4px;
            background: rgba(255,255,255,0.3); border-radius: 10px;
        }

        .container { 
            flex: 1;
            width: 90%; 
            max-width: 600px; 
            margin: 0 auto; 
            padding-bottom: 180px; 
        }

        .instruction { font-weight: 800; font-size: 1.6rem; margin-bottom: 30px; color: #1e293b; letter-spacing: -0.5px; }

        /* Character & Bubble Section */
        .character-row { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .avatar { width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; border: 3px solid var(--light-gray); }
        
        .speech-bubble {
            position: relative; background: #fff; border: 2px solid var(--light-gray);
            border-radius: 18px; padding: 15px 20px; flex: 1; font-weight: 600; font-size: 1.1rem;
            box-shadow: 0 4px 0 var(--light-gray);
        }
        .speech-bubble::before {
            content: ''; position: absolute; left: -10px; top: 25px;
            width: 15px; height: 15px; background: #fff; border-left: 2px solid var(--light-gray);
            border-bottom: 2px solid var(--light-gray); transform: rotate(45deg);
        }

        /* Work Area */
        .answer-container { 
            min-height: 160px; 
            border-top: 2px solid var(--light-gray);
            border-bottom: 2px solid var(--light-gray);
            margin: 40px 0;
            padding: 20px 0;
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 10px;
        }

        /* Cards Style */
        .word-card { 
            background: white; 
            border: 2px solid var(--light-gray); 
            border-bottom: 4px solid var(--light-gray); 
            padding: 10px 18px; 
            border-radius: 14px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 1.1rem; 
            transition: all 0.1s;
            user-select: none;
            display: inline-block;
        }
        .word-card:active { transform: translateY(2px); border-bottom-width: 2px; }
        .word-card.hidden { background: #e5e5e5; color: #e5e5e5; border-color: #e5e5e5; border-bottom-width: 2px; cursor: default; }

        .pool-area { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }

        /* Bottom Action Bar */
        .footer { 
            position: fixed; bottom: 0; width: 100%; 
            background: white; border-top: 2px solid var(--light-gray); 
            padding: 30px 0; z-index: 1000;
        }
        .footer-content { max-width: 600px; margin: 0 auto; display: flex; gap: 15px; width: 90%; }
        
        .btn-main { 
            height: 55px; border-radius: 16px; border: none; font-weight: 800; 
            cursor: pointer; text-transform: uppercase; letter-spacing: 0.8px;
            transition: all 0.2s; font-size: 1rem;
        }
        .btn-check { 
            flex: 1; background: var(--primary); color: white; 
            box-shadow: 0 4px 0 var(--primary-dark);
        }
        .btn-check:disabled { background: #e5e5e5; color: #afafaf; box-shadow: 0 4px 0 #ccc; cursor: not-allowed; }
        
        .btn-tool { 
            width: 55px; background: white; border: 2px solid var(--light-gray); 
            border-bottom: 4px solid var(--light-gray); color: var(--sky);
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }

        /* Correct/Wrong States */
        .state-correct { background: #d7ffb8 !important; }
        .state-wrong { background: #ffdfe0 !important; }

        .hint-box-premium {
            background: #fdf2ff; border: 2px solid #e9d5ff; border-radius: 15px;
            padding: 12px 20px; color: #7e22ce; font-weight: 700; margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px; animation: bounceIn 0.5s;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.9); opacity: 0; }
            70% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        .shake { animation: shake 0.2s ease-in-out 0s 2; }
    </style>
</head>
<body id="main-body">

<div class="header-nav">
    <a href="latihan.php" class="btn-close"><i class="fa-solid fa-xmark"></i></a>
    <div class="progress-container">
        <div class="progress-bar"></div>
    </div>
    <div style="font-weight: 800; color: #ffc107;"><i class="fa-solid fa-fire"></i> 3</div>
</div>

<div class="container" id="game-container">
    <?php if($semua_selesai): ?>
        <div style="text-align:center; padding: 80px 20px;">
            <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" width="120" style="margin-bottom:20px;">
            <h1 style="font-weight: 800; font-size: 2.2rem; margin:0;">Super Kerja!</h1>
            <p style="color: var(--gray); font-size: 1.2rem; margin-top:10px;">Semua tantangan hari ini sudah rata.</p>
            <a href="latihan.php" class="btn-main btn-check" style="text-decoration:none; display:inline-flex; align-items:center; padding: 0 40px; margin-top:30px;">Lanjutkan</a>
        </div>
    <?php else: ?>
        <div class="instruction">Susun kalimat ini ke Bahasa Jerman</div>
        
        <div class="character-row">
            <div class="avatar">👨‍🏫</div>
            <div class="speech-bubble">
                <?= htmlspecialchars($row['terjemahan']) ?>
            </div>
        </div>

        <?php if(isset($_GET['show_ans'])): ?>
            <div class="hint-box-premium">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span><?= $row['kalimat_benar'] ?></span>
            </div>
        <?php endif; ?>

        <div id="answer-area" class="answer-container">
            <!-- Tempat kata yang dipilih muncul -->
        </div>

        <div id="pool-area" class="pool-area">
            <?php foreach($words as $index => $w): ?>
                <div class="word-card" id="word-<?= $index ?>" onclick="selectWord(this)"><?= htmlspecialchars(trim($w)) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if(!$semua_selesai): ?>
<div class="footer" id="dynamic-footer">
    <div class="footer-content">
        <button onclick="resetPuzzle()" class="btn-main btn-tool" title="Reset"><i class="fa-solid fa-rotate-left"></i></button>
        
        <form method="POST" style="display: contents;">
            <button type="submit" name="action" value="see_answer" class="btn-main btn-tool" 
                <?= !$can_see_hint ? 'disabled' : '' ?>>
                <?php if($can_see_hint): ?>
                    <i class="fa-solid fa-lightbulb" style="color: #eab308;"></i>
                <?php else: ?>
                    <span style="font-size: 0.65rem; font-weight: 800; color: #94a3b8;"><?= $remaining_time ?></span>
                <?php endif; ?>
            </button>
        </form>

        <button id="check-btn" onclick="checkAnswer()" class="btn-main btn-check" disabled>Periksa</button>
    </div>
</div>
<?php endif; ?>

<script>
function selectWord(el) {
    if (el.classList.contains('hidden')) return;
    
    const answerArea = document.getElementById('answer-area');
    const clone = el.cloneNode(true);
    clone.dataset.originId = el.id;
    
    // Suara klik ringan (opsional)
    if(window.speechSynthesis) {
        const msg = new SpeechSynthesisUtterance(el.innerText);
        msg.lang = 'de-DE';
        msg.rate = 1.2;
        window.speechSynthesis.speak(msg);
    }

    clone.onclick = function() {
        document.getElementById(this.dataset.originId).classList.remove('hidden');
        this.remove();
        updateCheckButton();
    };

    answerArea.appendChild(clone);
    el.classList.add('hidden');
    updateCheckButton();
}

function resetPuzzle() {
    document.getElementById('answer-area').innerHTML = "";
    document.querySelectorAll('.word-card').forEach(el => el.classList.remove('hidden'));
    updateCheckButton();
}

function updateCheckButton() {
    const hasWords = document.querySelectorAll('#answer-area .word-card').length > 0;
    document.getElementById('check-btn').disabled = !hasWords;
}

function checkAnswer() {
    const btn = document.getElementById('check-btn');
    const footer = document.getElementById('dynamic-footer');
    const userWords = Array.from(document.querySelectorAll('#answer-area .word-card')).map(el => el.innerText.trim());
    const userSentence = userWords.join(' ');
    const correctAnswer = "<?= addslashes($row['kalimat_benar']) ?>";

    if (userSentence === correctAnswer) {
        // SUCCESS STATE
        footer.classList.add('state-correct');
        btn.style.background = "#58cc02";
        btn.style.boxShadow = "0 4px 0 #46a302";
        btn.innerText = "Luar Biasa!";
        
        const formData = new FormData();
        formData.append('soal_id', '<?= $row['id'] ?>');
        formData.append('is_correct', '1');
        
        fetch('proses_jawaban_puzzle.php', { method: 'POST', body: formData }).then(() => {
            setTimeout(() => location.href = "puzzle_map.php", 1000);
        });
    } else {
        // ERROR STATE
        document.getElementById('game-container').classList.add('shake');
        footer.classList.add('state-wrong');
        btn.style.background = "#ff4b4b";
        btn.style.boxShadow = "0 4px 0 #ea2b2b";
        btn.innerText = "Coba lagi!";
        
        setTimeout(() => {
            document.getElementById('game-container').classList.remove('shake');
            footer.classList.remove('state-wrong');
            btn.style.background = "";
            btn.style.boxShadow = "";
            btn.innerText = "Periksa";
        }, 1200);
    }
}
</script>
</body>
</html>