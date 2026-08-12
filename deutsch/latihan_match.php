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
// LOGIKA AJAX: SIMPAN PROGRES & STATISTIK (MATCH)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_progress') {
        $next_page   = (int)$_POST['next_page'];
        $correct_ids = isset($_POST['correct_ids']) ? json_decode($_POST['correct_ids'], true) : [];

        // 1. Simpan Unlocked Page (id_match = 0 khusus penanda halaman)
        $conn->query("INSERT INTO latihan_en_match_progress (user_id, id_match, unlocked_page) VALUES ($user_id, 0, $next_page) ON DUPLICATE KEY UPDATE unlocked_page = GREATEST(unlocked_page, $next_page)");

        // 2. Simpan Jawaban Benar per Soal (id_match > 0)
        if (!empty($correct_ids) && is_array($correct_ids)) {
            foreach ($correct_ids as $q_id) {
                $q_id = (int)$q_id;
                $conn->query("INSERT INTO latihan_en_match_progress (user_id, id_match, is_correct) VALUES ($user_id, $q_id, 1) ON DUPLICATE KEY UPDATE is_correct = 1");
            }
        }

        // 3. Update Statistik Harian & Log Aktivitas
        $today = date('Y-m-d');
        $conn->query("INSERT INTO user_daily_stats (user_id, log_date, exercises_completed) VALUES ($user_id, '$today', 10) ON DUPLICATE KEY UPDATE exercises_completed = exercises_completed + 10");

        $recipe_num = max(1, $next_page - 1);
        $desc = "Selesai 10 soal Alchemist's Match / The Missing Link (Recipe #$recipe_num)";
        $stmt_log = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, language) VALUES (?, 'exercise', ?, 'en')");
        if ($stmt_log) {
            $stmt_log->bind_param("is", $user_id, $desc);
            $stmt_log->execute();
        }

        exit("Progress Saved"); 
    } 
    elseif ($_POST['action'] == 'reset_progress') {
        $target_page = (int)$_POST['target_page'];
        $conn->query("UPDATE latihan_en_match_progress SET unlocked_page = $target_page WHERE user_id = $user_id AND id_match = 0");
        exit("Progress Reset");
    }
}

// ==========================================
// PENGATURAN HALAMAN & ANTI-CHEAT
// ==========================================
$totalResultCount = $conn->query("SELECT COUNT(*) AS total FROM latihan_en_match");
$rowCount = $totalResultCount->fetch_assoc();
$totalQuestions = $rowCount['total'];
$limit = 10; 
$pages = ceil($totalQuestions / $limit);
if($pages == 0) $pages = 1;

$prog_sql = $conn->query("SELECT unlocked_page FROM latihan_en_match_progress WHERE user_id = $user_id AND id_match = 0 AND unlocked_page IS NOT NULL ORDER BY unlocked_page DESC LIMIT 1");
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
$sql = "SELECT * FROM latihan_en_match ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);

// Persiapkan data Kiri (Term A) dan Kanan (Term B)
$left_items = [];
$right_items = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $left_items[] = ['id' => $row['id'], 'text' => $row['term_a']];
        $right_items[] = ['id' => $row['id'], 'text' => $row['term_b']];
    }
}
// Acak item di sebelah kanan agar tidak sejajar
shuffle($right_items);

$overall_progress = round(($unlocked_page - 1) / $pages * 100);
if($overall_progress > 100) $overall_progress = 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Alchemist's Lab | English Village</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    :root { 
        --stone-dark: #1E2226; --stone-light: #2A3036; 
        --copper: #D35400; --copper-light: #E67E22;
        --emerald: #2ECC71; --emerald-glow: rgba(46, 204, 113, 0.4);
        --amber: #F39C12; --amber-glow: rgba(243, 156, 18, 0.4);
        --danger: #E74C3C; --text-main: #EAECEE;
        --glass-panel: rgba(42, 48, 54, 0.7);
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * { box-sizing: border-box; }

    body { 
        background-color: var(--stone-dark); color: var(--text-main); margin: 0; 
        font-family: 'Nunito', sans-serif;
        background-image: 
            radial-gradient(circle at 50% 0%, #2f363d, var(--stone-dark)),
            url('https://www.transparenttextures.com/patterns/dark-matter.png');
        min-height: 100vh; overflow-x: hidden;
    }

    .nav-bar { 
        display: flex; justify-content: space-between; padding: 15px 40px; 
        background: rgba(30, 34, 38, 0.95); border-bottom: 2px solid var(--copper);
        align-items: center; position: sticky; top: 0; z-index: 1000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.5); backdrop-filter: blur(5px);
    }
    .nav-bar a { color: var(--text-main); text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: var(--transition); }
    .nav-bar a:hover { color: var(--copper-light); }
    .nav-bar .recipe-tag { color: var(--copper); font-family: 'Cinzel', serif; font-weight: bold; letter-spacing: 2px; }
    
    .progress-bar { width: 100%; height: 4px; background: #111; position: fixed; top: 56px; z-index: 999;}
    .progress-fill { height: 100%; background: linear-gradient(90deg, var(--copper), var(--emerald)); width: <?php echo $overall_progress; ?>%; transition: width 1s ease-in-out; }

    header { text-align: center; padding: 50px 20px 30px; }
    .icon-magic { font-size: 3.5rem; color: var(--emerald); margin-bottom: 15px; filter: drop-shadow(0 0 15px var(--emerald-glow)); }
    h1 { font-family: 'Cinzel', serif; font-size: 3.2rem; margin: 0 0 10px 0; color: var(--text-main); letter-spacing: 1px; text-transform: uppercase; }
    p { color: #AAB7B8; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

    .control-panel { width: 95%; max-width: 1000px; margin: 0 auto 30px; display: flex; justify-content: space-between; align-items: center; }
    .btn-return { background: var(--stone-light); color: var(--text-main); padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: bold; border: 1px solid #4A5056; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; }
    .btn-return:hover { background: var(--copper); border-color: var(--copper); color: white; transform: translateX(-5px); }
    
    .btn-restart { background: var(--stone-light); border: 1px solid var(--copper); color: var(--copper); width: 45px; height: 45px; border-radius: 50%; cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .btn-restart:hover { background: var(--copper); color: white; transform: rotate(-180deg); box-shadow: 0 0 15px var(--amber-glow); }

    .match-board { 
        width: 95%; max-width: 1000px; margin: 0 auto 100px; 
        display: flex; gap: 30px; justify-content: space-between;
    }
    .match-column { flex: 1; display: flex; flex-direction: column; gap: 18px; }
    
    .match-item { 
        background: var(--glass-panel); border: 2px solid #3E464E; 
        padding: 20px 25px; border-radius: 8px; cursor: pointer; transition: var(--transition);
        position: relative; font-weight: 600; font-size: 1.05rem; user-select: none;
        display: flex; align-items: center; min-height: 85px; 
        box-shadow: inset 0 0 20px rgba(0,0,0,0.5), 0 5px 15px rgba(0,0,0,0.3);
        backdrop-filter: blur(4px); border-left: 5px solid #3E464E;
    }
    
    .match-item:hover { border-color: var(--copper-light); background: rgba(52, 73, 94, 0.8); transform: translateY(-3px); }
    
    .left-col .match-item.active { border-color: var(--amber); border-left-color: var(--amber); box-shadow: 0 0 20px var(--amber-glow); background: rgba(243, 156, 18, 0.1); }
    .right-col .match-item.active { border-color: var(--emerald); border-left-color: var(--emerald); box-shadow: 0 0 20px var(--emerald-glow); background: rgba(46, 204, 113, 0.1); }
    
    .match-item.matched { border-color: var(--copper); border-left-color: var(--copper); background: rgba(211, 84, 0, 0.15); opacity: 0.85; }
    
    .match-item.error { border-color: var(--danger); border-left-color: var(--danger); background: rgba(231, 76, 60, 0.15); animation: shake 0.4s; }
    @keyframes shake { 0%, 100% {transform: translateX(0);} 25% {transform: translateX(-6px);} 75% {transform: translateX(6px);} }

    .pair-badge {
        position: absolute; width: 32px; height: 32px; border-radius: 4px;
        background: linear-gradient(135deg, var(--copper-light), var(--copper)); color: white; font-weight: 900;
        display: flex; justify-content: center; align-items: center; font-size: 1rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.5); border: 2px solid #FFF;
    }
    .left-col .pair-badge { right: -16px; top: 50%; transform: translateY(-50%); }
    .right-col .pair-badge { left: -16px; top: 50%; transform: translateY(-50%); }

    .submit-area { position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(30, 34, 38, 0.95); padding: 20px 0; text-align: center; border-top: 2px solid var(--copper); z-index: 100; backdrop-filter: blur(5px); }
    .btn-brew { 
        background: linear-gradient(to right, var(--copper), #E67E22); color: white; border: none; 
        padding: 16px 50px; font-family: 'Cinzel', serif; font-size: 1.3rem; font-weight: bold; 
        border-radius: 4px; cursor: pointer; transition: var(--transition); 
        box-shadow: 0 4px 15px rgba(211, 84, 0, 0.4); text-transform: uppercase; letter-spacing: 2px;
    }
    .btn-brew:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(211, 84, 0, 0.6); }
    .btn-brew:disabled { filter: grayscale(1); opacity: 0.7; cursor: not-allowed; }

    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(8px); }
    .modal { background: var(--stone-light); padding: 50px 40px; border-radius: 8px; text-align: center; border: 2px solid var(--copper); width: 90%; max-width: 450px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); position: relative; }
    .modal::before { content: ''; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); width: 60px; height: 30px; background: var(--copper); clip-path: polygon(0 0, 100% 0, 80% 100%, 20% 100%); }
    
    .score-text { font-family: 'Cinzel', serif; font-size: 4.5rem; color: var(--emerald); margin: 20px 0; text-shadow: 0 0 20px var(--emerald-glow); line-height: 1; }
    .btn-modal { display: block; width: 100%; padding: 16px; border-radius: 4px; font-weight: bold; text-decoration: none; margin-top: 15px; border: none; cursor: pointer; font-size: 1.1rem; transition: var(--transition); text-transform: uppercase; letter-spacing: 1px; }
    
    .select-lab { width: 100%; padding: 15px; margin: 20px 0; background: var(--stone-dark); color: white; border: 2px solid var(--copper); border-radius: 4px; font-family: 'Nunito'; font-size: 1.1rem; outline: none; }

    @media (max-width: 768px) {
        .match-board { gap: 15px; flex-direction: column; }
        .left-col .pair-badge { right: 50%; top: auto; bottom: -16px; transform: translateX(50%); z-index: 10; }
        .right-col .pair-badge { left: 50%; top: -16px; transform: translateX(-50%); z-index: 10; }
        .match-item { font-size: 0.95rem; padding: 15px; min-height: auto; text-align: center; justify-content: center; border-left: 2px solid #3E464E; border-top: 5px solid #3E464E; }
        .left-col .match-item.active { border-top-color: var(--amber); }
        .right-col .match-item.active { border-top-color: var(--emerald); }
        .match-item.matched { border-top-color: var(--copper); }
        h1 { font-size: 2.2rem; }
    }
</style>
</head>
<body>

<div class="nav-bar">
    <a href="index.php"><i class="fa-solid fa-house-chimney"></i> Leave Lab</a>
    <div class="recipe-tag">RECIPE #<?php echo $page; ?></div>
</div>
<div class="progress-bar"><div class="progress-fill"></div></div>

<header>
    <i class="fa-solid fa-vial-circle-check icon-magic"></i>
    <h1>Alchemist's Match</h1>
    <p>Select a rare component on the left, then bind it to its true property on the right to synthesize the potion.</p>
</header>

<div class="control-panel">
    <a href="latihan_en.php" class="btn-return"><i class="fa-solid fa-arrow-left"></i> Back to Barn</a>
    <?php if ($unlocked_page > $pages && $totalQuestions > 0): ?>
        <button onclick="document.getElementById('restartModal').style.display='flex'" class="btn-restart" title="Reset Workstation"><i class="fa-solid fa-rotate-right"></i></button>
    <?php endif; ?>
</div>

<div class="match-board">
    <div class="match-column left-col" id="leftColumn">
        <?php if(!empty($left_items)): foreach($left_items as $l): ?>
            <div class="match-item left-item" data-id="<?php echo $l['id']; ?>" onclick="handleLeftClick(this)">
                <?php echo htmlspecialchars($l['text']); ?>
            </div>
        <?php endforeach; else: ?>
            <p style="text-align:center; color: #888;">No ingredients found.</p>
        <?php endif; ?>
    </div>

    <div class="match-column right-col" id="rightColumn">
        <?php if(!empty($right_items)): foreach($right_items as $r): ?>
            <div class="match-item right-item" data-id="<?php echo $r['id']; ?>" onclick="handleRightClick(this)">
                <?php echo htmlspecialchars($r['text']); ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="submit-area">
    <button class="btn-brew" id="submitBtn" onclick="evaluateMatches()"><i class="fa-solid fa-fire-burner"></i> Brew Potion</button>
</div>

<div id="resultModal" class="overlay">
    <div class="modal">
        <h2 style="font-family:'Cinzel'; color:#FFF; margin-top:0;" id="modalTitle">Analysis Complete</h2>
        <div class="score-text" id="modalScore">0/10</div>
        <p id="modalMsg" style="color:#AAB7B8; margin-bottom: 30px; font-size: 1.1rem;"></p>
        
        <button onclick="location.reload()" class="btn-modal" id="btnRetry" style="background:var(--danger); color:white; display:none;"><i class="fa-solid fa-trash-can"></i> Clean Cauldron & Retry</button>
        
        <?php $nextP = $page + 1; ?>
        <?php if($nextP <= $pages): ?>
            <a href="?page=<?php echo $nextP; ?>" class="btn-modal" id="btnNext" style="background:var(--emerald); color:var(--stone-dark); display:none;"><i class="fa-solid fa-flask"></i> Next Recipe</a>
        <?php else: ?>
            <a href="latihan_en.php" class="btn-modal" id="btnFinish" style="background:var(--copper); color:white; display:none;"><i class="fa-solid fa-award"></i> Master Alchemist (Finish)</a>
        <?php endif; ?>
    </div>
</div>

<div id="restartModal" class="overlay">
    <div class="modal">
        <h2 style="font-family:'Cinzel'; color:var(--copper); margin-top:0;"><i class="fa-solid fa-clock-rotate-left"></i> Time Travel</h2>
        <p style="color: #AAB7B8;">Select a previous workstation to revisit.</p>
        <select id="jumpPage" class="select-lab">
            <?php for($i=1; $i<=$pages; $i++) echo "<option value='$i'>Recipe Book #$i</option>"; ?>
        </select>
        <button onclick="doJump()" class="btn-modal" style="background:var(--copper); color:white;">Teleport</button>
        <button onclick="document.getElementById('restartModal').style.display='none'" class="btn-modal" style="background:transparent; color:#888; border:1px solid #555;">Cancel</button>
    </div>
</div>

<script>
let activeLeft = null;
let activeRight = null;
let pairCounter = 0;

function handleLeftClick(el) {
    if(el.classList.contains('matched')) { unmatch(el.dataset.pairId); return; }
    if(activeLeft) activeLeft.classList.remove('active');
    activeLeft = el;
    activeLeft.classList.add('active');
    checkPair();
}

function handleRightClick(el) {
    if(el.classList.contains('matched')) { unmatch(el.dataset.pairId); return; }
    if(activeRight) activeRight.classList.remove('active');
    activeRight = el;
    activeRight.classList.add('active');
    checkPair();
}

function checkPair() {
    if(activeLeft && activeRight) {
        pairCounter++;
        let pId = pairCounter;
        
        activeLeft.classList.remove('active'); activeRight.classList.remove('active');
        activeLeft.classList.add('matched', 'pair-' + pId); activeRight.classList.add('matched', 'pair-' + pId);
        
        activeLeft.dataset.pairId = pId; activeRight.dataset.pairId = pId;
        activeLeft.dataset.matchedTo = activeRight.dataset.id;
        
        activeLeft.innerHTML += `<div class="pair-badge">${pId}</div>`;
        activeRight.innerHTML += `<div class="pair-badge">${pId}</div>`;
        
        activeLeft = null; activeRight = null;
    }
}

function unmatch(pairId) {
    document.querySelectorAll(`.pair-${pairId}`).forEach(el => {
        el.classList.remove('matched', `pair-${pairId}`);
        delete el.dataset.pairId;
        if(el.classList.contains('left-item')) delete el.dataset.matchedTo;
        const badge = el.querySelector('.pair-badge');
        if(badge) badge.remove();
    });
}

function evaluateMatches() {
    const leftItems = document.querySelectorAll('.left-item');
    let score = 0;
    let correctQuestionIds = [];
    let allMatched = true;

    leftItems.forEach(item => {
        if(!item.dataset.matchedTo) allMatched = false;
    });

    if(!allMatched) {
        alert("You must bind all components before brewing!");
        return;
    }

    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> SYNTHESIZING...';

    // Periksa Kunci Jawaban & Catat ID Soal yang Benar
    leftItems.forEach(item => {
        const correctId = String(item.dataset.id).trim();
        const userMatchedId = String(item.dataset.matchedTo).trim();
        
        if(correctId === userMatchedId) {
            score++;
            correctQuestionIds.push(correctId);
            item.style.borderColor = "var(--emerald)";
            item.style.borderLeftColor = "var(--emerald)";
        } else {
            item.classList.add('error');
            document.querySelector(`.right-item[data-id="${userMatchedId}"]`).classList.add('error');
        }
    });

    setTimeout(() => {
        const modal = document.getElementById('resultModal');
        const scoreDisplay = document.getElementById('modalScore');
        scoreDisplay.innerText = `${score}/${leftItems.length}`;
        
        if(score >= Math.ceil(leftItems.length * 0.8)) {
            document.getElementById('modalTitle').innerText = "Perfect Synthesis!";
            document.getElementById('modalTitle').style.color = "var(--emerald)";
            scoreDisplay.style.color = "var(--emerald)";
            scoreDisplay.style.textShadow = "0 0 20px var(--emerald-glow)";
            document.getElementById('modalMsg').innerText = "The ingredients matched perfectly. Recipe saved!";
            
            if(document.getElementById('btnNext')) document.getElementById('btnNext').style.display = 'block';
            if(document.getElementById('btnFinish')) document.getElementById('btnFinish').style.display = 'block';
            
            // Simpan Progres + Poin Soal ke Database via AJAX
            const fd = new FormData(); 
            fd.append('action', 'save_progress'); 
            fd.append('next_page', <?php echo $page+1; ?>);
            fd.append('correct_ids', JSON.stringify(correctQuestionIds));
            fd.append('total_correct', score);

            fetch('latihan_match.php', {method:'POST', body:fd});
        } else {
            document.getElementById('modalTitle').innerText = "Exploded Cauldron!";
            document.getElementById('modalTitle').style.color = "var(--danger)";
            scoreDisplay.style.color = "var(--danger)";
            scoreDisplay.style.textShadow = "0 0 20px rgba(231, 76, 60, 0.4)";
            document.getElementById('modalMsg').innerText = `You need at least ${Math.ceil(leftItems.length * 0.8)} stable bonds to proceed.`;
            document.getElementById('btnRetry').style.display = 'block';
        }
        modal.style.display = 'flex';
    }, 1000);
}

function doJump() {
    const p = document.getElementById('jumpPage').value;
    const fd = new FormData(); fd.append('action', 'reset_progress'); fd.append('target_page', p);
    fetch('latihan_match.php', {method:'POST', body:fd}).then(() => window.location.href='?page='+p);
}
</script>
</body>
</html>