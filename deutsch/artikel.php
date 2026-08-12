<?php
session_start();

// --- 1. KONEKSI DATABASE ---
$host = "localhost";
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321";
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];

// Ambil data peta yang sedang dibuka
$user_query = $conn->query("SELECT current_artikel_map FROM users WHERE id = $uid");
$user_data = $user_query->fetch_assoc();
$unlocked_map = $user_data['current_artikel_map'] ?? 1;
$map_id = isset($_GET['map']) ? (int)$_GET['map'] : $unlocked_map;

// --- 2. LOGIKA PENGAMBILAN SOAL (Anti-Blank) ---
$query_soal = $conn->query("SELECT * FROM latihan_artikel 
    WHERE map_id = $map_id 
    AND id NOT IN (SELECT soal_id FROM user_progress WHERE user_id = $uid AND is_correct = 1)
    ORDER BY id ASC 
    LIMIT 50");

$count_current = ($query_soal) ? $query_soal->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Global Expedition | Eduventure</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            /* Tema Eduventure Global */
            --primary: #0284c7;       
            --primary-light: #38bdf8;
            --primary-dark: #0369a1;
            
            --success: #10b981;       
            --success-dark: #059669;
            --success-bg: #dcfce7;
            
            --wrong: #ef4444;         
            --wrong-dark: #dc2626;
            --wrong-bg: #fee2e2;
            
            --bg-sky: #e0f2fe;        /* Langit biru muda */
            --card-bg: rgba(255, 255, 255, 0.85); /* Agak transparan (Glass) */
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
        }
        
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: linear-gradient(to bottom, var(--bg-sky) 0%, #f8fafc 100%); 
            margin: 0; color: var(--text-main); overflow-x: hidden; 
            min-height: 100vh;
        }

        /* =========================================
           SCENERY BACKGROUND (KOTA & PEMANDANGAN)
           ========================================= */
        .scenery-layer {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; pointer-events: none; overflow: hidden;
        }
        
        /* Matahari & Awan */
        .sun { position: absolute; top: 5%; right: 10%; font-size: 6rem; color: #fde047; opacity: 0.8; animation: spinSun 20s linear infinite; filter: drop-shadow(0 0 20px rgba(253, 224, 71, 0.5)); }
        .cloud { position: absolute; color: #ffffff; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.05)); }
        .c1 { top: 15%; left: -20%; font-size: 5rem; animation: floatObj 45s linear infinite; opacity: 0.9;}
        .c2 { top: 8%; left: -10%; font-size: 7rem; animation: floatObj 60s linear infinite 15s; opacity: 0.7;}
        .c3 { top: 25%; left: -30%; font-size: 4rem; animation: floatObj 35s linear infinite 5s; opacity: 0.8;}

        /* Pesawat Terbang */
        .plane { position: absolute; top: 20%; font-size: 2.5rem; color: #94a3b8; opacity: 0.6; animation: flyPlane 30s linear infinite; }

        /* Siluet Kota & Desa di Bawah */
        .skyline {
            position: absolute; bottom: 0; left: 0; width: 100%; height: 250px;
            display: flex; align-items: flex-end; justify-content: space-around;
            padding: 0 2%; opacity: 0.15; /* Sengaja pudar biar soal tetap kebaca */
            color: #475569;
        }
        .skyline i { margin: 0 -15px; filter: drop-shadow(0 10px 0 rgba(0,0,0,0.1)); }
        .s-tree { font-size: 7rem; color: #10b981; }
        .s-house { font-size: 6rem; color: #64748b; }
        .s-church { font-size: 10rem; color: #475569; }
        .s-city { font-size: 12rem; color: #94a3b8; }
        .s-mountain { font-size: 14rem; color: #cbd5e1; position: absolute; bottom: 0; left: 10%; z-index: -1; }

        @keyframes spinSun { 100% { transform: rotate(360deg); } }
        @keyframes floatObj { from { transform: translateX(0); } to { transform: translateX(120vw); } }
        @keyframes flyPlane { 
            0% { transform: translate(-20vw, 10vh) rotate(15deg); } 
            100% { transform: translate(120vw, -10vh) rotate(15deg); } 
        }

        /* =========================================
           UI KUIS UTAMA
           ========================================= */
        .duo-nav { 
            height: 80px; display: flex; align-items: center; padding: 0 25px; gap: 25px; 
            max-width: 800px; margin: 0 auto; position: sticky; top: 0; z-index: 50; 
            background: transparent;
        }
        .progress-container { flex-grow: 1; height: 18px; background: rgba(226, 232, 240, 0.8); border-radius: 20px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); backdrop-filter: blur(5px); }
        #p-bar { height: 100%; width: 0%; background: linear-gradient(90deg, var(--primary-light), var(--primary)); border-radius: 20px; transition: 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; }
        #p-bar::after { content: ''; position: absolute; top: 2px; left: 2px; right: 2px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 10px; }
        
        .exit-btn { color: #64748b; text-decoration: none; font-size: 1.8rem; transition: 0.2s; background: rgba(255,255,255,0.5); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; backdrop-filter: blur(5px); }
        .exit-btn:hover { color: white; background: var(--wrong); transform: scale(1.1); }

        .container { max-width: 550px; margin: 0 auto; padding: 20px; display: flex; flex-direction: column; min-height: calc(100vh - 80px); position: relative; z-index: 10; }

        /* --- MASKOT & ANIMASI --- */
        .char-area { height: 180px; width: 100%; display: flex; justify-content: center; align-items: center; position: relative; margin-bottom: 20px; }
        .char-box { width: 160px; height: 160px; z-index: 2; transition: transform 0.3s; }
        .char-box img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 15px 20px rgba(0,0,0,0.15)); }
        
        .bubble { 
            position: absolute; left: 62%; top: 15px; background: #fff; border: 2px solid var(--border-color); 
            padding: 15px 25px; border-radius: 20px 20px 20px 5px; font-weight: 800; display: none; z-index: 5;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); color: var(--primary-dark);
            animation: pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes pop { from { transform: scale(0) rotate(-10deg); } to { transform: scale(1) rotate(0); } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-25px) scale(1.05); } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-15px) rotate(-5deg); } 40% { transform: translateX(15px) rotate(5deg); } 60% { transform: translateX(-10px); } 80% { transform: translateX(10px); } }

        /* --- KARTU SOAL (GLASSMORPHISM) --- */
        .q-card { display: none; width: 100%; text-align: center; }
        .q-card.active { display: block; animation: slideIn 0.4s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .q-header { color: var(--primary); font-weight: 900; text-transform: uppercase; margin-bottom: 20px; font-size: 1rem; letter-spacing: 2px; display: flex; align-items: center; justify-content: center; gap: 10px; text-shadow: 0 2px 5px rgba(255,255,255,0.8); }
        
        .q-content-wrapper {
            margin-bottom: 35px; display: flex; flex-direction: column; align-items: center;
            background: var(--card-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 35px 20px; border-radius: 28px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08); border: 1px solid rgba(255,255,255,0.5);
        }

        .q-text { 
            font-size: 2.5rem; font-weight: 900; margin-bottom: 15px; color: var(--text-main); 
            display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap;
        }
        .q-text span { color: var(--primary); border-bottom: 4px dashed var(--border-color); padding-bottom: 2px; min-width: 60px; display: inline-block; }

        .q-translate { 
            font-size: 1.1rem; color: var(--text-muted); font-weight: 700; 
            padding: 8px 20px; border-radius: 12px; background: rgba(241, 245, 249, 0.8);
            display: inline-flex; align-items: center; gap: 10px; border: 1px solid #e2e8f0;
        }

        /* --- TOMBOL OPSI --- */
        .option-grid { display: grid; gap: 15px; width: 100%; margin-top: 10px; }
        .opt-btn { 
            background: var(--card-bg); backdrop-filter: blur(10px);
            border: 2px solid var(--border-color); border-bottom: 6px solid var(--border-color); 
            border-radius: 20px; padding: 20px; font-weight: 800; font-size: 1.3rem; cursor: pointer; transition: 0.15s; text-align: center;
            color: var(--text-main); font-family: 'Plus Jakarta Sans', sans-serif;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
        }
        .opt-btn:hover { background: #ffffff; border-color: #94a3b8; border-bottom-color: #94a3b8; transform: translateY(-2px); border-bottom-width: 8px; }
        .opt-btn:active { transform: translateY(4px); border-bottom-width: 2px; }
        
        .opt-btn.correct { background: var(--success-bg) !important; border-color: var(--success) !important; color: var(--success-dark) !important; border-bottom-color: var(--success-dark) !important; }
        .opt-btn.wrong { background: var(--wrong-bg) !important; border-color: var(--wrong) !important; color: var(--wrong-dark) !important; border-bottom-color: var(--wrong-dark) !important; }

        /* --- FEEDBACK SHEET --- */
        .feedback-sheet { position: fixed; bottom: 0; left: 0; right: 0; padding: 35px 25px; transform: translateY(100%); transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 1000; }
        .feedback-sheet.show { transform: translateY(0); }
        .feedback-sheet.is-correct { background: var(--success-bg); box-shadow: 0 -10px 40px rgba(16, 185, 129, 0.2); }
        .feedback-sheet.is-wrong { background: var(--wrong-bg); box-shadow: 0 -10px 40px rgba(239, 68, 68, 0.2); }

        .sheet-content { max-width: 600px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
        .status-box { display: flex; align-items: center; gap: 20px; }
        .status-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .continue-btn { 
            padding: 18px 50px; border-radius: 20px; border: none; font-weight: 900; text-transform: uppercase; 
            cursor: pointer; color: #fff; font-size: 1.1rem; letter-spacing: 1px;
            box-shadow: 0 6px 0 rgba(0,0,0,0.15); transition: 0.2s; font-family: inherit;
        }
        .continue-btn:active { transform: translateY(6px); box-shadow: none; }

        .btn-finish { background: var(--primary); color: white; padding: 18px 40px; border-radius: 16px; font-weight: 800; text-decoration: none; display: inline-block; margin-top: 25px; box-shadow: 0 6px 0 var(--primary-dark); transition: 0.2s; text-transform: uppercase; letter-spacing: 1px; }
        .btn-finish:active { transform: translateY(6px); box-shadow: none; }
    </style>
</head>
<body>

<div class="scenery-layer">
    <i class="fa-solid fa-sun sun"></i>
    <i class="fa-solid fa-cloud cloud c1"></i>
    <i class="fa-solid fa-cloud cloud c2"></i>
    <i class="fa-solid fa-cloud cloud c3"></i>
    <i class="fa-solid fa-plane plane"></i>
    
    <div class="skyline">
        <i class="fa-solid fa-mountain s-mountain"></i>
        <i class="fa-solid fa-tree s-tree"></i>
        <i class="fa-solid fa-house-chimney s-house"></i>
        <i class="fa-solid fa-church s-church"></i>
        <i class="fa-solid fa-tree s-tree" style="font-size:5rem; opacity:0.8;"></i>
        <i class="fa-solid fa-city s-city"></i>
        <i class="fa-solid fa-tree s-tree" style="font-size:8rem;"></i>
    </div>
</div>

<nav class="duo-nav">
    <a href="artikel_map.php" class="exit-btn"><i class="fa-solid fa-xmark"></i></a>
    <div class="progress-container"><div id="p-bar"></div></div>
</nav>

<div class="container">
    <div id="quiz-area" style="width: 100%;">
        
        <div class="char-area">
            <div id="char-container" class="char-box">
                <img id="char-img" src="ANIMASI_MARAH_SENANG/SENANG.png" alt="Maskot">
            </div>
            <div id="bubble" class="bubble">Gute Reise! ✈️</div>
        </div>

        <?php if($count_current > 0): ?>
            <form action="cek_map.php" method="POST" id="duoForm">
                <input type="hidden" name="map_id" value="<?= $map_id ?>">
                <?php $i = 0; while($s = $query_soal->fetch_assoc()): ?>
                    <div class="q-card <?= ($i == 0) ? 'active' : '' ?>" id="card-<?= $i ?>" data-ans="<?= $s['jawaban'] ?>">
                        <div class="q-header"><i class="fa-solid fa-earth-americas"></i> Tentukan Artikel</div>
                        
                        <div class="q-content-wrapper">
                            <div class="q-text">
                                <span>___</span> 
                                <?= htmlspecialchars($s['pertanyaan']) ?>
                            </div>
                            
                            <div class="q-translate">
                                 <i class="fa-solid fa-language" style="color: var(--primary);"></i>
                                 <?= !empty($s['terjemahan']) ? htmlspecialchars($s['terjemahan']) : '...' ?>
                            </div>
                        </div>

                        <div class="option-grid">
                            <button type="button" class="opt-btn" onclick="checkAns(this, 'a', <?= $i ?>)">Der</button>
                            <button type="button" class="opt-btn" onclick="checkAns(this, 'b', <?= $i ?>)">Die</button>
                            <button type="button" class="opt-btn" onclick="checkAns(this, 'c', <?= $i ?>)">Das</button>
                            <input type="hidden" name="ans[<?= $s['id'] ?>]" id="input-<?= $i ?>">
                        </div>
                    </div>
                <?php $i++; endwhile; ?>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 40px; background: rgba(255,255,255,0.9); padding: 40px; border-radius: 24px; box-shadow: 0 15px 30px rgba(0,0,0,0.05); backdrop-filter: blur(10px);">
                <i class="fa-solid fa-medal" style="font-size: 5rem; color: var(--primary); margin-bottom: 20px; filter: drop-shadow(0 10px 10px rgba(2, 132, 199, 0.3));"></i>
                <h2 style="color: var(--text-main); font-size: 2rem; margin: 0 0 10px 0;">Wunderbar! ✨</h2>
                <p style="color: var(--text-muted); font-size: 1.1rem;">Semua misi di area ini telah berhasil kamu taklukkan.</p>
                <a href="artikel_map.php" class="btn-finish">Kembali ke Peta</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="feedback-sheet" id="sheet">
    <div class="sheet-content">
        <div class="status-box">
            <div id="status-icon" class="status-icon"></div>
            <div>
                <h2 id="status-text" style="margin:0; font-size:1.6rem; font-weight: 900;">Hebat!</h2>
                <p id="status-sub" style="margin: 5px 0 0 0; font-size: 0.9rem; font-weight: 600;"></p>
            </div>
        </div>
        <button class="continue-btn" id="sheet-btn" onclick="nextQuestion()">Lanjutkan</button>
    </div>
</div>

<audio id="snd-correct" src="https://www.myinstants.com/media/sounds/duolingo-correct.mp3"></audio>
<audio id="snd-wrong" src="https://www.myinstants.com/media/sounds/wrong-answer-129215.mp3"></audio>

<script>
    let current = 0;
    const totalSoal = <?= $count_current ?>;
    const charImg = document.getElementById('char-img');
    const bubble = document.getElementById('bubble');
    const sheet = document.getElementById('sheet');

    const imgHappy  = "ANIMASI_MARAH_SENANG/SENANG.png"; 
    const imgAngry  = "ANIMASI_MARAH_SENANG/MARAH.png"; 

    function checkAns(btn, choice, idx) {
        const card = document.getElementById('card-' + idx);
        if(card.classList.contains('done')) return;
        card.classList.add('done');

        const correct = card.getAttribute('data-ans');
        document.getElementById('input-' + idx).value = choice;
        sheet.classList.add('show');

        // Reset animasi karakter
        charImg.style.animation = "none";
        void charImg.offsetWidth; 

        if (choice === correct) {
            btn.classList.add('correct');
            document.getElementById('snd-correct').play();
            charImg.src = imgHappy;
            charImg.style.animation = "bounce 0.6s ease";
            bubble.innerText = "Richtig! ✨";
            bubble.style.display = "block";
            bubble.style.color = "var(--success-dark)";
            bubble.style.borderColor = "var(--success)";
            
            sheet.className = "feedback-sheet show is-correct";
            document.getElementById('status-icon').innerHTML = "🌟";
            document.getElementById('status-icon').style.color = "var(--success-dark)";
            document.getElementById('status-text').innerText = "Tepat Sekali!";
            document.getElementById('status-text').style.color = "var(--success-dark)";
            document.getElementById('status-sub').innerText = "Jawaban kamu sempurna.";
            document.getElementById('status-sub').style.color = "var(--success-dark)";
            document.getElementById('sheet-btn').style.backgroundColor = "var(--success)";
            document.getElementById('sheet-btn').style.boxShadow = "0 6px 0 var(--success-dark)";
        } else {
            btn.classList.add('wrong');
            document.getElementById('snd-wrong').play();
            charImg.src = imgAngry;
            charImg.style.animation = "shake 0.5s ease";
            bubble.innerText = "Falsch! ✈️";
            bubble.style.display = "block";
            bubble.style.color = "var(--wrong-dark)";
            bubble.style.borderColor = "var(--wrong)";
            
            sheet.className = "feedback-sheet show is-wrong";
            document.getElementById('status-icon').innerHTML = "✖️";
            document.getElementById('status-icon').style.color = "var(--wrong-dark)";
            document.getElementById('status-text').innerText = "Kurang Tepat!";
            document.getElementById('status-text').style.color = "var(--wrong-dark)";
            document.getElementById('status-sub').innerText = "Kunci jawaban: " + correct.toUpperCase();
            document.getElementById('status-sub').style.color = "var(--wrong-dark)";
            document.getElementById('sheet-btn').style.backgroundColor = "var(--wrong)";
            document.getElementById('sheet-btn').style.boxShadow = "0 6px 0 var(--wrong-dark)";
        }
    }

    function nextQuestion() {
        sheet.classList.remove('show');
        bubble.style.display = "none";
        charImg.src = imgHappy; 
        
        setTimeout(() => {
            if (current < totalSoal - 1) {
                document.getElementById('card-' + current).classList.remove('active');
                current++;
                document.getElementById('card-' + current).classList.add('active');
                const progressPercent = ((current / totalSoal) * 100);
                document.getElementById('p-bar').style.width = progressPercent + '%';
            } else {
                document.getElementById('p-bar').style.width = '100%';
                setTimeout(() => document.getElementById('duoForm').submit(), 600);
            }
        }, 300);
    }
</script>
</body>
</html>