<?php
// ==========================================
// BAGIAN PHP ASLI MILIKMU (TIDAK DIUBAH SAMA SEKALI)
// ==========================================
session_start();
$host = "localhost";
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321";
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$level = 'HOREN';
$tipe = 'pilihan_ganda';

$res_done = $conn->query("SELECT soal_id FROM user_progress WHERE user_id = $uid AND soal_id IN (SELECT id FROM latihan_horen)");
$already_done_ids = [];
if($res_done) {
    while($row = $res_done->fetch_assoc()) { $already_done_ids[] = $row['soal_id']; }
}
$exclude_ids = !empty($already_done_ids) ? implode(",", $already_done_ids) : "0";

$query_soal = $conn->query("SELECT * FROM latihan_horen WHERE id NOT IN ($exclude_ids) ORDER BY id ASC LIMIT 1");
$s = $query_soal->fetch_assoc();

$res_total = $conn->query("SELECT COUNT(*) as total FROM latihan_horen");
$total_soal = $res_total->fetch_assoc()['total'] ?? 0;
$res_count = $conn->query("SELECT COUNT(*) as total FROM user_progress WHERE user_id = $uid AND soal_id IN (SELECT id FROM latihan_horen)");
$done_count = $res_count->fetch_assoc()['total'] ?? 0;
$progress_percent = ($total_soal > 0) ? ($done_count / $total_soal) * 100 : 0;
// ==========================================
// AKHIR BAGIAN PHP ASLI
// ==========================================
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hören Lektion | DeutschAktiv</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            --game-blue: #3b82f6;
            --game-blue-dark: #2563eb;
            --game-yellow: #facc15;
            --game-yellow-dark: #eab308;
            --game-green: #22c55e;
            --game-green-dark: #16a34a;
            --game-red: #ff4b4b;
            --game-red-dark: #dc2626;
            --bg-sky: #e0f2fe;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; font-family: 'Nunito', sans-serif; }

        body { 
            background: linear-gradient(180deg, #bae6fd 0%, #e0f2fe 100%);
            margin: 0; display: flex; flex-direction: column; min-height: 100vh; color: var(--text-dark);
            overflow-x: hidden;
        }

        /* ================= ANIMASI BACKGROUND RAME ================= */
        .floating-bg { position: fixed; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .float-letter { position: absolute; font-size: 4rem; font-weight: 900; color: rgba(255,255,255,0.4); animation: floatUp linear infinite; }
        .float-letter:nth-child(1) { left: 10%; animation-duration: 15s; animation-delay: 0s; }
        .float-letter:nth-child(2) { left: 85%; animation-duration: 20s; animation-delay: 2s; font-size: 6rem; }
        .float-letter:nth-child(3) { left: 50%; animation-duration: 18s; animation-delay: 5s; font-size: 3rem; }
        .float-letter:nth-child(4) { left: 25%; animation-duration: 22s; animation-delay: 8s; font-size: 5rem; }
        .float-letter:nth-child(5) { left: 70%; animation-duration: 16s; animation-delay: 1s; font-size: 4.5rem; }
        @keyframes floatUp { 0% { transform: translateY(100vh) rotate(0deg); opacity: 0; } 20% { opacity: 1; } 80% { opacity: 1; } 100% { transform: translateY(-20vh) rotate(360deg); opacity: 0; } }

        /* ================= NAVIGASI ================= */
        .top-nav { position: relative; z-index: 10; padding: 20px 0; }
        .nav-content { width: 92%; max-width: 800px; margin: 0 auto; display: flex; align-items: center; gap: 20px; background: white; padding: 15px 25px; border-radius: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-bottom: 4px solid #e2e8f0; }
        
        .btn-back { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 15px; background: #f1f5f9; color: var(--text-muted); text-decoration: none; font-size: 1.4rem; transition: 0.2s; border-bottom: 4px solid #cbd5e1; }
        .btn-back:hover { background: #e2e8f0; transform: translateY(2px); border-bottom-width: 2px; margin-bottom: 2px; }

        .progress-container { flex: 1; }
        .progress-label { font-size: 0.9rem; font-weight: 900; color: var(--game-blue); display: flex; justify-content: space-between; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;}
        .progress-bg { height: 16px; background: #e2e8f0; border-radius: 50px; overflow: hidden; box-shadow: inset 0 3px 6px rgba(0,0,0,0.1); }
        .progress-fill { height: 100%; width: <?= $progress_percent ?>%; background: var(--game-green); border-radius: 50px; transition: width 0.5s ease-out; position: relative; }
        .progress-fill::after { content: ''; position: absolute; top: 2px; left: 2px; right: 2px; height: 4px; background: rgba(255,255,255,0.4); border-radius: 10px; }

        /* ================= MAIN CARD ================= */
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px 20px 50px; position: relative; z-index: 10; }

        .horen-card { 
            background: white; width: 100%; max-width: 600px; padding: 40px; 
            border-radius: 35px; box-shadow: 0 20px 40px rgba(59, 130, 246, 0.15); 
            border-bottom: 10px solid #e2e8f0; text-align: center;
            animation: bounceIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .question-title { font-size: 1.5rem; font-weight: 900; color: var(--text-dark); margin-bottom: 20px; line-height: 1.4; padding: 15px; background: #f8fafc; border-radius: 20px; border: 2px dashed #cbd5e1; }

        /* ================= ANIMASI GURU (MASCOT) & BUBBLE ================= */
        .mascot-container { display: flex; flex-direction: column; align-items: center; position: relative; margin-bottom: 30px; margin-top: 10px; }
        
        .mascot-emoji { font-size: 6.5rem; line-height: 1; filter: drop-shadow(0 10px 10px rgba(0,0,0,0.15)); animation: floatMascot 3s ease-in-out infinite; position: relative; z-index: 2; }
        
        /* State Animasi Mascot */
        .mascot-emoji.talking { animation: talkBounce 0.4s infinite alternate; }
        .mascot-emoji.happy { animation: jumpHappy 0.6s ease-in-out infinite alternate; }
        .mascot-emoji.sad { animation: shakeSad 0.5s ease-in-out; }

        /* Speech Bubble (Player Audio) */
        .speech-bubble { 
            background: var(--game-blue); padding: 20px 30px; border-radius: 30px; position: relative; 
            margin-top: 15px; display: flex; align-items: center; gap: 20px; border-bottom: 6px solid var(--game-blue-dark);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); z-index: 1; width: 100%; max-width: 400px; justify-content: center;
        }
        /* Segitiga Bubble */
        .speech-bubble::before { 
            content: ''; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); 
            border-width: 0 15px 20px 15px; border-style: solid; border-color: transparent transparent var(--game-blue) transparent; 
        }

        .sound-waves { display: flex; gap: 5px; height: 35px; align-items: center; justify-content: center; opacity: 0.3; transition: 0.3s; }
        .speech-bubble.is-playing .sound-waves { opacity: 1; }
        .wave-bar { width: 8px; background: white; border-radius: 10px; height: 8px; }
        .speech-bubble.is-playing .wave-bar { animation: soundBounce 0.6s infinite ease-in-out alternate; }
        .speech-bubble.is-playing .wave-bar:nth-child(1) { animation-delay: 0.1s; }
        .speech-bubble.is-playing .wave-bar:nth-child(2) { animation-delay: 0.3s; }
        .speech-bubble.is-playing .wave-bar:nth-child(3) { animation-delay: 0s; }
        .speech-bubble.is-playing .wave-bar:nth-child(4) { animation-delay: 0.4s; }
        .speech-bubble.is-playing .wave-bar:nth-child(5) { animation-delay: 0.2s; }

        .btn-speak { width: 65px; height: 65px; border-radius: 50%; background: var(--game-yellow); color: #000; border: none; font-size: 2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; border-bottom: 4px solid var(--game-yellow-dark); transition: 0.2s; }
        .btn-speak:active { transform: translateY(4px); border-bottom-width: 0px; margin-top: 4px; }
        
        .btn-stop { width: 45px; height: 45px; border-radius: 50%; background: rgba(255,255,255,0.2); color: white; border: none; font-size: 1.2rem; cursor: pointer; transition: 0.2s; }
        .btn-stop:hover { background: var(--game-red); transform: scale(1.1); }

        .btn-toggle-text { background: none; border: none; color: var(--text-muted); font-weight: 800; font-size: 0.9rem; margin-top: 15px; cursor: pointer; text-decoration: underline; text-underline-offset: 4px; transition: 0.2s;}
        .btn-toggle-text:hover { color: var(--game-blue); }

        /* Transkrip */
        .hint-text { display: none; padding: 20px; background: #fffbeb; border-radius: 20px; margin-top: 15px; font-weight: 800; font-size: 1.1rem; text-align: center; color: #b45309; border: 3px dashed #fcd34d; animation: popIn 0.3s forwards;}

        /* ================= PILIHAN JAWABAN (Efek 3D Game) ================= */
        .options-grid { margin-top: 10px; }
        .option-box { 
            display: flex; align-items: center; padding: 18px 25px; background: white; 
            border: 2px solid #e2e8f0; border-radius: 20px; margin-bottom: 15px; cursor: pointer; 
            font-weight: 800; width: 100%; color: var(--text-dark); text-align: left; font-size: 1.15rem;
            border-bottom: 6px solid #e2e8f0; transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .option-box:hover { border-color: #cbd5e1; border-bottom-color: #cbd5e1; transform: translateY(-2px); }
        .option-box:active { transform: translateY(4px); border-bottom-width: 2px; margin-bottom: 19px; }
        
        /* State Terpilih */
        .option-box.selected { border-color: var(--game-blue); border-bottom-color: var(--game-blue-dark); background: #eff6ff; }
        
        /* State Benar / Salah */
        .option-box.is-correct { background: #f0fdf4 !important; border-color: var(--game-green) !important; border-bottom-color: var(--game-green-dark) !important; color: #166534; animation: popCorrect 0.4s; }
        .option-box.is-wrong { background: #fef2f2 !important; border-color: var(--game-red) !important; border-bottom-color: var(--game-red-dark) !important; color: #991b1b; animation: shakeWrong 0.4s; opacity: 0.8;}

        .opt-label { width: 40px; height: 40px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 1.1rem; font-weight: 900; color: var(--text-muted); border: 2px solid #e2e8f0; }
        .selected .opt-label { background: var(--game-blue); color: white; border-color: var(--game-blue-dark); }
        .is-correct .opt-label { background: var(--game-green); color: white; border-color: var(--game-green-dark); }
        .is-wrong .opt-label { background: var(--game-red); color: white; border-color: var(--game-red-dark); }

        /* ================= BUTTON CEK / LANJUT ================= */
        .btn-check { 
            margin-top: 15px; width: 100%; padding: 20px; border-radius: 25px; border: none; 
            background: #e2e8f0; color: #94a3b8; font-weight: 900; font-size: 1.3rem; 
            cursor: not-allowed; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 6px solid #cbd5e1; transition: 0.2s;
        }
        .btn-check.ready { background: var(--game-blue); color: white; cursor: pointer; border-bottom-color: var(--game-blue-dark); }
        .btn-check.ready:hover { filter: brightness(1.1); }
        .btn-check.ready:active { transform: translateY(6px); border-bottom-width: 0px; margin-bottom: 6px; }
        
        .btn-check.next-stage { background: var(--game-green); color: white; cursor: pointer; border-bottom-color: var(--game-green-dark); }
        .btn-check.next-stage:active { transform: translateY(6px); border-bottom-width: 0px; margin-bottom: 6px; }

        /* ================= FEEDBACK MESSAGE ================= */
        .feedback-msg { margin-bottom: 20px; font-weight: 900; display: none; padding: 20px; border-radius: 20px; font-size: 1.2rem; text-align: center; animation: popIn 0.4s; }
        .msg-correct { display: block; color: var(--game-green-dark); background: #dcfce7; border: 3px solid var(--game-green); }
        .msg-wrong { display: block; color: var(--game-red-dark); background: #fee2e2; border: 3px solid var(--game-red); }

        /* ================= ANIMASI KEYFRAMES ================= */
        @keyframes bounceIn { 0% { opacity: 0; transform: scale(0.8) translateY(50px); } 60% { opacity: 1; transform: scale(1.05) translateY(-10px); } 100% { transform: scale(1) translateY(0); } }
        @keyframes floatMascot { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
        @keyframes talkBounce { 0% { transform: scale(1) translateY(0); } 100% { transform: scale(1.05) translateY(-8px); } }
        @keyframes jumpHappy { 0% { transform: translateY(0) scale(1); } 50% { transform: translateY(-30px) scale(1.1) rotate(10deg); } 100% { transform: translateY(0) scale(1); } }
        @keyframes shakeSad { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-15px) rotate(-10deg); } 75% { transform: translateX(15px) rotate(10deg); } }
        @keyframes soundBounce { 0% { height: 8px; } 100% { height: 35px; } }
        @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 80% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(1); opacity: 1; } }
        @keyframes popCorrect { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        @keyframes shakeWrong { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-10px); } 75% { transform: translateX(10px); } }

        /* End Screen */
        .end-screen { padding: 40px 0; animation: bounceIn 0.6s forwards; }
        .end-icon { font-size: 7rem; margin-bottom: 20px; animation: jumpHappy 1s infinite alternate; }
        .end-title { font-weight: 900; font-size: 2.5rem; color: var(--game-yellow-dark); margin: 0 0 15px 0;}
        .end-btn { display:inline-block; background:var(--game-blue); color:white; font-weight:900; text-decoration:none; padding:20px 40px; border-radius:25px; margin-top:30px; border-bottom: 6px solid var(--game-blue-dark); font-size: 1.2rem;}
        .end-btn:active { transform: translateY(6px); border-bottom-width: 0px; }

        @media (max-width: 600px) { .horen-card { padding: 30px 20px; } .mascot-emoji { font-size: 5rem; } .option-box { padding: 15px 20px; font-size: 1.1rem; } .question-title { font-size: 1.3rem; } }
    </style>
</head>
<body>

<div class="floating-bg">
    <div class="float-letter">Ä</div>
    <div class="float-letter">Ö</div>
    <div class="float-letter">ß</div>
    <div class="float-letter">Ü</div>
    <div class="float-letter">Ä</div>
</div>

<div class="top-nav">
    <div class="nav-content">
        <a href="latihan.php" class="btn-back"><i class="fa-solid fa-xmark"></i></a>
        <div class="progress-container">
            <div class="progress-label">
                <span>Latihan Mendengar</span>
                <span><?= $done_count ?> / <?= $total_soal ?></span>
            </div>
            <div class="progress-bg"><div class="progress-fill"></div></div>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="horen-card">
        <?php if($s): ?>
            
            <div class="question-title">
                "<?= htmlspecialchars($s['instruksi']) ?>"
            </div>
            
            <div class="mascot-container">
                <div class="mascot-emoji" id="mascotChar">👩‍🏫</div>
                
                <div class="speech-bubble" id="playerBubble">
                    <button type="button" id="playBtn" class="btn-speak" onclick='speak(<?= json_encode($s["pertanyaan"]) ?>)'>
                        <i class="fa-solid fa-play"></i>
                    </button>
                    
                    <div class="sound-waves">
                        <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
                    </div>

                    <button type="button" id="stopBtn" class="btn-stop" onclick="stopSpeak()">
                        <i class="fa-solid fa-stop"></i>
                    </button>
                </div>
                
                <button type="button" class="btn-toggle-text" onclick="toggleText()">Tidak bisa mendengar? Lihat Teks</button>
                
                <div id="hintBox" class="hint-text">"<?= htmlspecialchars($s['pertanyaan']) ?>"</div>
            </div>

            <div id="feedback" class="feedback-msg"></div>

            <form id="ansForm" action="proses_jawaban.php" method="POST">
                <input type="hidden" name="level" value="HOREN">
                <input type="hidden" name="soal_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="user_ans" id="userAns">

                <div class="options-grid">
                    <?php foreach(['a','b','c','d'] as $o): ?>
                        <?php if(!empty($s['opsi_'.$o])): ?>
                            <div class="option-box" data-val="<?= $o ?>" onclick="selectOpt(this)">
                                <div class="opt-label"><?= strtoupper($o) ?></div>
                                <span><?= htmlspecialchars($s['opsi_'.$o]) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="btnAction" class="btn-check" onclick="handleAction()">CEK JAWABAN</button>
            </form>
        <?php else: ?>
            <div class="end-screen">
                <div class="end-icon">🎉🦉🎉</div>
                <h2 class="end-title">Wunderbar!</h2>
                <p style="color:var(--text-muted); font-size:1.2rem; font-weight:800;">Kamu sudah menyelesaikan semua level mendengar!</p>
                <a href="latihan.php?level=HOREN&action=reset" class="end-btn">MAIN LAGI <i class="fa-solid fa-rotate-right" style="margin-left:8px;"></i></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let synth = window.speechSynthesis;
const correctAns = "<?= $s['jawaban'] ?? '' ?>";
let isChecked = false;

// Element Target
const mascot = document.getElementById('mascotChar');
const bubble = document.getElementById('playerBubble');
const playBtn = document.getElementById('playBtn');

function speak(text) {
    synth.cancel();
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'de-DE'; u.rate = 0.85;
    
    u.onstart = () => { 
        bubble.classList.add('is-playing');
        mascot.classList.add('talking');
        playBtn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
    };
    
    u.onend = () => { 
        bubble.classList.remove('is-playing');
        mascot.classList.remove('talking');
        playBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
    };
    
    synth.speak(u);
}

function stopSpeak() { 
    synth.cancel(); 
    bubble.classList.remove('is-playing');
    mascot.classList.remove('talking');
    playBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
}

function toggleText() { 
    const box = document.getElementById('hintBox'); 
    box.style.display = (box.style.display === "block") ? "none" : "block";
}

function selectOpt(el) {
    if(isChecked) return; // Kunci jika sudah di cek
    
    document.querySelectorAll('.option-box').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    
    document.getElementById('userAns').value = el.dataset.val;
    
    const btn = document.getElementById('btnAction');
    btn.classList.add('ready');
}

function handleAction() {
    const userAns = document.getElementById('userAns').value;
    if(!userAns) return;

    if(!isChecked) {
        // STATE: CEK JAWABAN
        isChecked = true;
        const allOpts = document.querySelectorAll('.option-box');
        const feedback = document.getElementById('feedback');
        
        stopSpeak(); // Matikan audio jika masih nyala
        
        allOpts.forEach(opt => {
            const val = opt.dataset.val;
            
            // Kunci tombol agar tidak bisa ditekan lagi
            opt.style.pointerEvents = "none"; 

            if(val === correctAns) {
                opt.classList.add('is-correct');
                opt.classList.remove('selected');
            }
            else if(val === userAns) {
                opt.classList.add('is-wrong');
            }
        });

        if(userAns === correctAns) {
            feedback.innerHTML = "✨ Super! Jawabanmu benar.";
            feedback.className = "feedback-msg msg-correct";
            
            // Ganti mascot jadi senang
            mascot.innerText = "🤩";
            mascot.classList.add('happy');
        } else {
            feedback.innerHTML = "😅 Ops! Jawaban yang benar: " + correctAns.toUpperCase();
            feedback.className = "feedback-msg msg-wrong";
            
            // Ganti mascot jadi kaget/sedih
            mascot.innerText = "🤦‍♀️";
            mascot.classList.add('sad');
        }

        const btn = document.getElementById('btnAction');
        btn.innerHTML = "LANJUTKAN <i class='fa-solid fa-forward-step' style='margin-left:8px;'></i>";
        btn.className = "btn-check next-stage";
    } else {
        // STATE: KIRIM FORM
        document.getElementById('ansForm').submit();
    }
}
</script>
</body>
</html>