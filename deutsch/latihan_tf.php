<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Koneksi Database
$host = "localhost"; $user = "u960862048_roy"; $pass = "Caracter_Cs321"; $db = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// ==========================================
// LOGIKA AJAX: SIMPAN PROGRES & STATISTIK (B/S)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_progress') {
        $next_page   = (int)$_POST['next_page'];
        $correct_ids = isset($_POST['correct_ids']) ? json_decode($_POST['correct_ids'], true) : [];

        // 1. Simpan Unlocked Page (id_tf = 0 khusus untuk penanda halaman)
        $conn->query("INSERT INTO latihan_en_tf_progress (user_id, id_tf, unlocked_page) VALUES ($user_id, 0, $next_page) ON DUPLICATE KEY UPDATE unlocked_page = GREATEST(unlocked_page, $next_page)");

        // 2. Simpan Jawaban Benar per Soal (id_tf > 0)
        if (!empty($correct_ids) && is_array($correct_ids)) {
            foreach ($correct_ids as $q_id) {
                $q_id = (int)$q_id;
                $conn->query("INSERT INTO latihan_en_tf_progress (user_id, id_tf, is_correct) VALUES ($user_id, $q_id, 1) ON DUPLICATE KEY UPDATE is_correct = 1");
            }
        }

        // 3. Update Statistik Harian & Log Aktivitas
        $today = date('Y-m-d');
        $conn->query("INSERT INTO user_daily_stats (user_id, log_date, exercises_completed) VALUES ($user_id, '$today', 10) ON DUPLICATE KEY UPDATE exercises_completed = exercises_completed + 10");

        $scroll_num = max(1, $next_page - 1);
        $desc = "Selesai 10 soal Elder's Wisdom / Scales of Truth (Scroll $scroll_num)";
        $stmt_log = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, language) VALUES (?, 'exercise', ?, 'en')");
        if ($stmt_log) {
            $stmt_log->bind_param("is", $user_id, $desc);
            $stmt_log->execute();
        }

        exit("Progress Saved"); 
    } 
    elseif ($_POST['action'] == 'reset_progress') {
        $target_page = (int)$_POST['target_page'];
        $conn->query("UPDATE latihan_en_tf_progress SET unlocked_page = $target_page WHERE user_id = $user_id AND id_tf = 0");
        exit("Progress Reset");
    }
}

// ==========================================
// PENGATURAN HALAMAN & ANTI-CHEAT
// ==========================================
$totalResultCount = $conn->query("SELECT COUNT(*) AS total FROM latihan_en_tf");
$rowCount = $totalResultCount->fetch_assoc();
$totalQuestions = $rowCount['total'];
$limit = 10; 
$pages = ceil($totalQuestions / $limit);
if($pages == 0) $pages = 1;

$prog_sql = $conn->query("SELECT unlocked_page FROM latihan_en_tf_progress WHERE user_id = $user_id AND id_tf = 0 AND unlocked_page IS NOT NULL ORDER BY unlocked_page DESC LIMIT 1");
$unlocked_page = 1; 
if ($prog_sql && $prog_sql->num_rows > 0) {
    $prog_row = $prog_sql->fetch_assoc();
    $unlocked_page = (int)$prog_row['unlocked_page'];
}

$safe_redirect_page = ($unlocked_page > $pages) ? $pages : $unlocked_page;
if (!isset($_GET['page'])) { header("Location: ?page=" . $safe_redirect_page); exit(); }

$page = (int)$_GET['page'];
if ($page > $unlocked_page && $totalQuestions > 0) {
    header("Location: ?page=" . $safe_redirect_page);
    exit();
}

$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$sql = "SELECT * FROM latihan_en_tf ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);
$nomor_urut = $start + 1;

// Hitung Persentase Progres untuk Progress Bar
$overall_progress = round(($unlocked_page - 1) / $pages * 100);
if($overall_progress > 100) $overall_progress = 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Elder's Wisdom | English Village</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400&family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<style>
    :root { 
        --wood-dark: #2D241E; --wood-medium: #5D4037; --wood-light: #A1887F; 
        --bg-cream: #FCF8F3; --bg-paper: #F4EBE2; 
        --sky-blue: #4A90E2; --sky-deep: #2171C1;
        --danger: #D32F2F; --success: #388E3C;
        --glass: rgba(255, 255, 255, 0.7);
        --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    * { box-sizing: border-box; }
    body { 
        font-family: 'Nunito', sans-serif; background-color: var(--bg-paper); color: var(--wood-dark); 
        margin: 0; overflow-x: hidden; line-height: 1.6;
        background-image: 
            linear-gradient(rgba(244, 235, 226, 0.8), rgba(244, 235, 226, 0.8)),
            url('https://www.transparenttextures.com/patterns/paper-fibers.png');
    }

    .user-nav { 
        display: flex; justify-content: space-between; padding: 10px 40px; 
        background: var(--glass); backdrop-filter: blur(10px); 
        align-items: center; position: sticky; top: 0; z-index: 1000; 
        border-bottom: 2px solid rgba(74, 144, 226, 0.2);
    }
    .lobby-action a { 
        color: var(--wood-dark); text-decoration: none; font-weight: 800; 
        padding: 10px 20px; border-radius: 50px; background: white; 
        transition: var(--transition); border: 1px solid #ddd;
    }
    .lobby-action a:hover { color: var(--sky-blue); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

    .master-progress-container { width: 100%; height: 6px; background: #E0D6CB; position: fixed; top: 60px; z-index: 999; }
    .master-progress-fill { height: 100%; background: linear-gradient(90deg, var(--sky-blue), #8E44AD); width: <?php echo $overall_progress; ?>%; transition: width 1s ease-in-out; }

    header { padding: 60px 15px 30px; text-align: center; }
    .village-badge { 
        display: inline-block; background: var(--sky-blue); color: white; 
        font-size: 0.75rem; font-weight: 900; padding: 8px 25px; 
        border-radius: 50px; letter-spacing: 3px; margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
    }
    .logo-text { font-family: 'Playfair Display', serif; font-size: 3.5rem; margin: 0; color: var(--wood-dark); }
    .logo-sub { font-family: 'Lora', serif; font-style: italic; color: var(--wood-medium); font-size: 1.1rem; }

    .back-container { width: 90%; max-width: 900px; margin: 30px auto; display: flex; justify-content: space-between; align-items: center; }
    .btn-back { 
        display: inline-flex; align-items: center; gap: 10px; color: var(--wood-medium); 
        text-decoration: none; font-weight: 800; padding: 12px 25px; border-radius: 50px; 
        transition: var(--transition); border: 2px solid transparent; background: white;
    }
    .btn-back:hover { background: var(--sky-blue); color: white; transform: translateX(-5px); }

    .btn-restart { 
        background: white; color: var(--sky-blue); border: 2px solid var(--sky-blue); 
        width: 45px; height: 45px; border-radius: 50%; cursor: pointer; 
        transition: var(--transition); font-size: 1.2rem; display: flex; align-items: center; justify-content: center;
    }
    .btn-restart:hover { background: var(--sky-blue); color: white; transform: rotate(-180deg); }

    .quiz-container { width: 90%; max-width: 900px; margin: 0 auto 80px; position: relative; }
    
    .question-card { 
        background: white; border-radius: var(--radius-lg); padding: 50px; 
        margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.05); 
        box-shadow: 0 10px 30px rgba(58, 46, 38, 0.05); transition: var(--transition);
        position: relative; overflow: hidden;
    }
    .question-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(58, 46, 38, 0.1); }
    .question-card::before { content: '“'; position: absolute; top: 10px; left: 30px; font-size: 8rem; color: rgba(74, 144, 226, 0.05); font-family: serif; }

    .question-text { 
        font-family: 'Lora', serif; font-size: 1.6rem; margin-bottom: 40px; 
        line-height: 1.6; text-align: center; color: var(--wood-dark); font-weight: 500;
    }

    .tf-group { display: flex; gap: 25px; justify-content: center; }
    .tf-radio { display: none; }
    .tf-btn { 
        flex: 1; max-width: 220px; padding: 20px; border-radius: 15px; 
        cursor: pointer; font-weight: 800; font-size: 1.1rem; text-align: center;
        transition: var(--transition); border: 2px solid #EEE; background: #FDFDFD;
        display: flex; flex-direction: column; align-items: center; gap: 10px;
    }
    .tf-btn i { font-size: 1.5rem; opacity: 0.3; }
    
    .tf-radio[value="True"]:checked + .tf-btn { border-color: var(--success); color: var(--success); background: #F1F8F1; }
    .tf-radio[value="True"]:checked + .tf-btn i { opacity: 1; }
    
    .tf-radio[value="False"]:checked + .tf-btn { border-color: var(--danger); color: var(--danger); background: #FFF5F5; }
    .tf-radio[value="False"]:checked + .tf-btn i { opacity: 1; }

    .tf-btn:hover:not(.disabled) { border-color: var(--sky-blue); color: var(--sky-blue); transform: scale(1.02); }

    .feedback-area { 
        margin-top: 30px; padding: 20px; border-radius: 12px; 
        display: none; animation: fadeIn 0.5s ease; text-align: center;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .submit-wrapper { position: sticky; bottom: 30px; text-align: center; z-index: 100; }
    .btn-main { 
        background: var(--wood-dark); color: white; border: none; 
        padding: 20px 60px; font-weight: 800; font-size: 1.2rem; 
        border-radius: 50px; cursor: pointer; transition: var(--transition);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: inline-flex; align-items: center; gap: 15px;
    }
    .btn-main:hover { background: var(--sky-blue); transform: scale(1.05); box-shadow: 0 15px 30px rgba(74, 144, 226, 0.4); }

    .overlay { 
        display: none; position: fixed; inset: 0; background: rgba(45, 36, 30, 0.9); 
        backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;
    }
    .modal { 
        background: white; padding: 50px; border-radius: 30px; text-align: center; 
        max-width: 500px; width: 100%; border: 8px solid var(--bg-paper);
    }
    .score-circle { 
        width: 120px; height: 120px; border: 5px solid var(--sky-blue); 
        border-radius: 50%; display: flex; align-items: center; 
        justify-content: center; margin: 0 auto 30px; font-size: 2.5rem; font-weight: 900; color: var(--sky-blue);
    }
    .btn-modal { 
        display: block; width: 100%; padding: 18px; border-radius: 50px; 
        font-weight: 800; text-decoration: none; margin-top: 15px; border: none; cursor: pointer;
    }
    
    @media (max-width: 600px) {
        .tf-group { flex-direction: column; align-items: center; }
        .tf-btn { width: 100%; max-width: none; }
        .logo-text { font-size: 2.5rem; }
    }
</style>
</head>
<body>

<div class="user-nav">
    <div class="lobby-action"><a href="index.php"><i class="fa-solid fa-house-chimney-window"></i> Village</a></div>
    <div class="nav-flags"><i class="fa-solid fa-feather-pointed" style="color: var(--sky-blue); font-size: 1.5rem;"></i></div>
    <div class="user-actions">
        <a href="logout.php" class="user-link" style="color: var(--wood-medium); font-weight: 800; text-decoration: none;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Leave</a>
    </div>
</div>

<div class="master-progress-container">
    <div class="master-progress-fill"></div>
</div>

<header>
    <div class="village-badge">SACRED KNOWLEDGE</div>
    <h1 class="logo-text">Elder's Wisdom</h1>
    <p class="logo-sub">Distinguish truth from deception in the ancient scrolls.</p>
</header>

<div class="back-container">
    <a href="latihan_en.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Barn</a>
    <div style="display:flex; align-items:center; gap:15px;">
        <span style="font-weight: 900; letter-spacing: 1px; color: var(--wood-medium);">SCROLL <?php echo $page; ?> OF <?php echo $pages; ?></span>
        <?php if ($unlocked_page > $pages): ?>
            <button onclick="document.getElementById('restartModal').style.display='flex'" class="btn-restart"><i class="fa-solid fa-rotate-right"></i></button>
        <?php endif; ?>
    </div>
</div>

<div class="quiz-container">
    <form id="wisdomForm" onsubmit="assessWisdom(event)">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="question-card" id="card_<?php echo $row['id']; ?>">
                    <div class="question-text"><?php echo htmlspecialchars($row['statement']); ?></div>
                    
                    <div class="tf-group">
                        <input type="radio" name="q_<?php echo $row['id']; ?>" value="True" id="t_<?php echo $row['id']; ?>" class="tf-radio" required>
                        <label class="tf-btn" for="t_<?php echo $row['id']; ?>">
                            <i class="fa-solid fa-circle-check"></i> TRUE
                        </label>

                        <input type="radio" name="q_<?php echo $row['id']; ?>" value="False" id="f_<?php echo $row['id']; ?>" class="tf-radio">
                        <label class="tf-btn" for="f_<?php echo $row['id']; ?>">
                            <i class="fa-solid fa-circle-xmark"></i> FALSE
                        </label>
                    </div>

                    <input type="hidden" id="ans_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['correct_answer']); ?>">
                    <input type="hidden" id="exp_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['explanation']); ?>">
                    <div class="feedback-area" id="fb_<?php echo $row['id']; ?>"></div>
                </div>
            <?php endwhile; ?>

            <div class="submit-wrapper">
                <button type="submit" class="btn-main" id="submitBtn">
                    <span>Submit Your Wisdom</span> <i class="fa-solid fa-scroll"></i>
                </button>
            </div>
        <?php else: ?>
            <div class="question-card" style="text-align: center;">
                <i class="fa-solid fa-book-open" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                <h2>The library is empty.</h2>
                <p>No scrolls found in this section.</p>
            </div>
        <?php endif; ?>
    </form>
</div>

<div id="resultModal" class="overlay">
    <div class="modal">
        <h2 id="mTitle" style="font-family: 'Playfair Display', serif; font-size: 2rem;">Assessment</h2>
        <div class="score-circle" id="mScore">0/10</div>
        <p id="mMsg" style="font-weight: 700; color: var(--wood-medium); margin-bottom: 30px;"></p>
        
        <button onclick="location.reload()" class="btn-modal" id="btnRetry" style="background: var(--wood-medium); color: white; display:none;">Try Again</button>
        
        <?php $nextP = $page + 1; ?>
        <?php if($nextP <= $pages): ?>
            <a href="?page=<?php echo $nextP; ?>" class="btn-modal" id="btnNext" style="background: var(--sky-blue); color: white; display:none; text-decoration: none;">Continue Journey</a>
        <?php else: ?>
            <a href="latihan_en.php" class="btn-modal" id="btnFinish" style="background: var(--success); color: white; display:none; text-decoration: none;">Return as Master</a>
        <?php endif; ?>
    </div>
</div>

<div id="restartModal" class="overlay">
    <div class="modal">
        <h2>Time Travel</h2>
        <p>Pick a scroll to revisit.</p>
        <select id="jumpPage" style="width:100%; padding:15px; border-radius:15px; border:2px solid var(--sky-blue); margin-bottom: 20px; font-weight: bold;">
            <?php for($i=1; $i<=$pages; $i++) echo "<option value='$i'>Scroll $i</option>"; ?>
        </select>
        <button onclick="jumpTo()" class="btn-modal" style="background: var(--sky-blue); color: white;">Teleport</button>
        <button onclick="document.getElementById('restartModal').style.display='none'" class="btn-modal" style="background: #eee;">Cancel</button>
    </div>
</div>

<script>
function jumpTo() {
    const p = document.getElementById('jumpPage').value;
    const fd = new FormData(); fd.append('action', 'reset_progress'); fd.append('target_page', p);
    fetch('latihan_tf.php', {method:'POST', body:fd}).then(() => window.location.href='?page='+p);
}

function assessWisdom(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn'); btn.disabled = true;
    btn.style.opacity = '0.5';
    
    let score = 0;
    let correctQuestionIds = [];
    const cards = document.querySelectorAll('.question-card');

    cards.forEach(card => {
        const id = card.id.split('_')[1];
        if(!id) return;

        const selectedEl = document.querySelector(`input[name="q_${id}"]:checked`);
        if(!selectedEl) return;

        // Normalisasi huruf besar/kecil & variasi teks (true/1/t vs false/0/f)
        const selectedStr = selectedEl.value.trim().toLowerCase();
        const correctStr = String(document.getElementById('ans_'+id).value).trim().toLowerCase();

        const isSelectedTrue = (selectedStr === 'true' || selectedStr === '1' || selectedStr === 't');
        const isCorrectTrue  = (correctStr === 'true' || correctStr === '1' || correctStr === 't');

        const exp = document.getElementById('exp_'+id).value;
        const fb = document.getElementById('fb_'+id);

        fb.style.display = 'block';
        if (isSelectedTrue === isCorrectTrue) {
            score++;
            correctQuestionIds.push(id);
            fb.innerHTML = `<div style="color:var(--success); font-weight:800;"><i class="fa-solid fa-check-double"></i> Wisdom Confirmed</div><div style="font-size:0.9rem; margin-top:5px;">${exp}</div>`;
            fb.style.background = 'rgba(56, 142, 60, 0.08)';
        } else {
            fb.innerHTML = `<div style="color:var(--danger); font-weight:800;"><i class="fa-solid fa-circle-exclamation"></i> Illusion Detected</div><div style="font-size:0.9rem; margin-top:5px;">${exp}</div>`;
            fb.style.background = 'rgba(211, 47, 47, 0.08)';
        }
    });

    setTimeout(() => {
        const modal = document.getElementById('resultModal');
        document.getElementById('mScore').innerText = `${score}/${cards.length}`;
        
        if (score >= 8) {
            document.getElementById('mTitle').innerText = "Elder is Pleased";
            document.getElementById('mMsg').innerText = "The scrolls have revealed their secrets to you.";
            if(document.getElementById('btnNext')) document.getElementById('btnNext').style.display = 'block';
            if(document.getElementById('btnFinish')) document.getElementById('btnFinish').style.display = 'block';
            
            // Simpan Progres + Poin Soal ke Database via AJAX
            const fd = new FormData(); 
            fd.append('action', 'save_progress'); 
            fd.append('next_page', <?php echo $page+1; ?>);
            fd.append('correct_ids', JSON.stringify(correctQuestionIds));
            
            fetch('latihan_tf.php', {method:'POST', body:fd});
        } else {
            document.getElementById('mTitle').innerText = "Path Obscured";
            document.getElementById('mMsg').innerText = "You must meditate further on these truths.";
            document.getElementById('btnRetry').style.display = 'block';
        }
        modal.style.display = 'flex';
    }, 1000);
}
</script>
</body>
</html>