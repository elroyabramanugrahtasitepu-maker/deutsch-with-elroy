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
// LOGIKA AJAX: SIMPAN PROGRES & STATISTIK (ESSAY)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_progress') {
        $next_page   = (int)$_POST['next_page'];
        $correct_ids = isset($_POST['correct_ids']) ? json_decode($_POST['correct_ids'], true) : [];

        // 1. Simpan Unlocked Page (id_essay = 0 khusus untuk penanda halaman)
        $conn->query("INSERT INTO latihan_en_essay_progress (user_id, id_essay, unlocked_page) VALUES ($user_id, 0, $next_page) ON DUPLICATE KEY UPDATE unlocked_page = GREATEST(unlocked_page, $next_page)");

        // 2. Simpan Jawaban Benar per Soal (id_essay > 0)
        if (!empty($correct_ids) && is_array($correct_ids)) {
            foreach ($correct_ids as $q_id) {
                $q_id = (int)$q_id;
                $conn->query("INSERT INTO latihan_en_essay_progress (user_id, id_essay, is_correct) VALUES ($user_id, $q_id, 1) ON DUPLICATE KEY UPDATE is_correct = 1");
            }
        }

        // 3. Update Statistik Harian & Log Aktivitas
        $today = date('Y-m-d');
        $conn->query("INSERT INTO user_daily_stats (user_id, log_date, exercises_completed) VALUES ($user_id, '$today', 10) ON DUPLICATE KEY UPDATE exercises_completed = exercises_completed + 10");

        $scroll_num = max(1, $next_page - 1);
        $desc = "Selesai 10 soal The Scribe's Trial / Fill in the Blanks (Scroll $scroll_num)";
        $stmt_log = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, language) VALUES (?, 'exercise', ?, 'en')");
        if ($stmt_log) {
            $stmt_log->bind_param("is", $user_id, $desc);
            $stmt_log->execute();
        }

        exit("Progress Saved"); 
    } 
    elseif ($_POST['action'] == 'reset_progress') {
        $target_page = (int)$_POST['target_page'];
        $conn->query("UPDATE latihan_en_essay_progress SET unlocked_page = $target_page WHERE user_id = $user_id AND id_essay = 0");
        exit("Progress Reset");
    }
}

// ==========================================
// PENGATURAN HALAMAN & ANTI-CHEAT
// ==========================================
$totalResultCount = $conn->query("SELECT COUNT(*) AS total FROM latihan_en_essay");
$rowCount = $totalResultCount->fetch_assoc();
$totalQuestions = $rowCount['total'];
$limit = 10; 
$pages = ceil($totalQuestions / $limit);
if($pages == 0) $pages = 1;

$prog_sql = $conn->query("SELECT unlocked_page FROM latihan_en_essay_progress WHERE user_id = $user_id AND id_essay = 0 AND unlocked_page IS NOT NULL ORDER BY unlocked_page DESC LIMIT 1");
$unlocked_page = 1; 
if ($prog_sql && $prog_sql->num_rows > 0) {
    $prog_row = $prog_sql->fetch_assoc();
    $unlocked_page = (int)$prog_row['unlocked_page'];
}

$safe_redirect_page = ($unlocked_page > $pages) ? $pages : $unlocked_page;

if (!isset($_GET['page'])) {
    header("Location: ?page=" . $safe_redirect_page);
    exit();
}

$page = (int)$_GET['page'];
if ($page > $unlocked_page || $page > $pages) {
    if ($totalQuestions > 0) {
        header("Location: ?page=" . $safe_redirect_page);
        exit();
    }
}

$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$sql = "SELECT * FROM latihan_en_essay ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);
$nomor_urut = $start + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Scribe's Trial | English Village</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { 
        --wood-dark: #3A2E26; --wood-medium: #7A5B45; --wood-light: #C4A484; 
        --bg-cream: #FDFBF7; --bg-paper: #F2EBE1; 
        --leaf-green: #4E7B54; --earth-orange: #C86B3C; --sky-blue: #5B8FB9; 
        --danger-red: #E57373; --success-green: #81C784;
        --radius-lg: 20px; --radius-md: 12px;
        --shadow-soft: 0 8px 24px rgba(58, 46, 38, 0.08);
        --transition: all 0.3s ease;
    }

    * { box-sizing: border-box; }
    body { 
        font-family: 'Nunito', sans-serif; background-color: var(--bg-paper); 
        color: var(--wood-dark); margin: 0; overflow-x: hidden;
        background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23noise)' opacity='0.05'/%3E%3C/svg%3E");
    }

    .user-nav { display: flex; justify-content: space-between; padding: 15px 40px; background: var(--bg-cream); align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-bottom: 3px solid var(--wood-light); }
    .lobby-action a { color: var(--wood-dark); text-decoration: none; font-weight: 800; font-size: 0.95rem; transition: var(--transition); display: flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: var(--radius-md); background: var(--bg-paper); }
    .lobby-action a:hover { background: var(--bg-cream); color: var(--earth-orange); }
    .nav-flags { display: flex; gap: 12px; align-items: center; }
    .flag-icon { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; border: 3px solid var(--bg-paper); cursor: pointer; transition: 0.3s; }
    .flag-active { border-color: var(--earth-orange); transform: scale(1.05); }
    .user-actions { display: flex; gap: 15px; align-items: center; }
    .user-link { color: var(--wood-dark); text-decoration: none; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
    .logout-btn { background: var(--wood-medium); color: var(--bg-cream); padding: 10px 20px; border-radius: var(--radius-md); }
    .logout-btn:hover { background: var(--earth-orange); color: white; }

    header { padding: 40px 15px 20px; text-align: center; }
    .village-badge { display: inline-block; background: var(--earth-orange); color: white; font-size: 0.85rem; font-weight: 800; padding: 6px 18px; border-radius: 30px; letter-spacing: 2px; margin-bottom: 15px; }
    .logo-text { font-family: 'Lora', serif; font-size: 3rem; color: var(--wood-dark); margin: 0; font-weight: 700; }
    
    .back-container { width: 95%; max-width: 900px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; }
    .btn-back { display: inline-flex; align-items: center; gap: 10px; background: var(--bg-cream); color: var(--wood-dark); text-decoration: none; font-weight: 800; padding: 10px 20px; border-radius: 30px; transition: all 0.3s ease; border: 2px solid var(--wood-light); font-size: 0.95rem; }
    .btn-back:hover { background: var(--earth-orange); color: white; border-color: var(--earth-orange); transform: translateX(-5px); }
    
    .progress-wrapper { display: flex; align-items: center; gap: 10px; }
    .progress-info { font-weight: 800; color: var(--wood-medium); background: var(--bg-cream); padding: 8px 18px; border-radius: 20px; border: 2px dashed var(--wood-light); font-size: 0.9rem;}
    .btn-restart { background: var(--earth-orange); color: white; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-size: 1rem; }
    .btn-restart:hover { background: #a6562f; transform: rotate(-45deg) scale(1.1); box-shadow: 0 6px 10px rgba(0,0,0,0.2); }

    .quiz-container { width: 95%; max-width: 900px; margin: 0 auto 60px; background: var(--bg-cream); border: 3px solid var(--wood-light); border-radius: var(--radius-lg); padding: 40px; box-shadow: var(--shadow-soft); position: relative; }
    .quiz-container::before, .quiz-container::after { content: ''; position: absolute; width: 12px; height: 12px; background: #8b7355; border-radius: 50%; top: 15px; box-shadow: inset 2px 2px 4px rgba(0,0,0,0.3); }
    .quiz-container::before { left: 15px; } .quiz-container::after { right: 15px; }

    .question-box { background: var(--bg-paper); border: 2px dashed var(--wood-light); border-radius: var(--radius-md); padding: 25px; margin-bottom: 30px; transition: var(--transition); }
    .question-box:focus-within { border-color: var(--earth-orange); background: #FFF8F2; }
    .question-text { font-family: 'Lora', serif; font-size: 1.25rem; font-weight: 700; color: var(--wood-dark); margin-top: 0; margin-bottom: 20px; line-height: 1.5; }
    
    .essay-textarea { width: 100%; height: 60px; padding: 15px; border: 2px solid #e0d6cb; border-radius: 8px; font-family: 'Nunito', sans-serif; font-size: 1.05rem; resize: vertical; background: var(--bg-cream); color: var(--wood-dark); transition: var(--transition); font-weight: bold;}
    .essay-textarea:focus { outline: none; border-color: var(--earth-orange); box-shadow: 0 0 0 3px rgba(200, 107, 60, 0.1); }
    
    .feedback-box { margin-top: 15px; padding: 15px; border-radius: 8px; font-weight: 700; display: none; align-items: center; gap: 10px; }

    .btn-submit-quiz { background: var(--earth-orange); color: white; border: none; padding: 18px 40px; font-weight: 800; cursor: pointer; transition: var(--transition); font-size: 1.1rem; border-radius: 30px; display: block; width: 100%; margin-top: 20px; box-shadow: 0 4px 10px rgba(200, 107, 60, 0.3); }
    .btn-submit-quiz:hover { background: #a6562f; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(200, 107, 60, 0.4); }

    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(58, 46, 38, 0.85); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; }
    .modal-content { background: var(--bg-cream); padding: 40px; text-align: center; border-radius: var(--radius-lg); color: var(--wood-dark); max-width: 450px; width: 90%; border: 4px solid var(--wood-light); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
    .modal-content h2 { margin-top: 0; font-family: 'Lora', serif; font-size: 2rem; }
    .score-display { font-size: 3.5rem; font-weight: 800; margin: 15px 0; }
    
    .btn-modal { display: block; padding: 14px 30px; font-weight: 800; border-radius: 30px; text-decoration: none; cursor: pointer; transition: var(--transition); border: none; font-size: 1rem; width: 100%; margin-bottom: 12px; }
    .btn-retry { background: var(--earth-orange); color: white; }
    .btn-retry:hover { background: #a6562f; transform: translateY(-2px); }
    .btn-next { background: var(--leaf-green); color: white; } 
    .btn-next:hover { background: #3d6342; transform: translateY(-2px); }
    .btn-cancel { background: var(--wood-medium); color: white; }
    .btn-cancel:hover { background: var(--wood-dark); }

    .select-rustic { width: 100%; padding: 15px; margin-bottom: 25px; border-radius: 12px; border: 2px solid var(--wood-light); background: var(--bg-paper); font-family: 'Nunito', sans-serif; font-size: 1rem; color: var(--wood-dark); font-weight: 600; outline: none; cursor: pointer; }
    
    .empty-state { text-align: center; padding: 40px; font-weight: 700; color: var(--wood-medium); font-family: 'Lora', serif; font-size: 1.3rem; border: 2px dashed var(--wood-light); border-radius: var(--radius-md); }

    @media (max-width: 768px) {
        .user-nav { flex-direction: column; gap: 15px; padding: 15px; }
        .logo-text { font-size: 2.5rem; }
        .quiz-container { padding: 25px 15px; }
        .back-container { flex-direction: column-reverse; gap: 15px; justify-content: center; }
    }
</style>
</head>
<body>

<div class="user-nav">
    <div class="lobby-action"><a href="index.php"><i class="fa-solid fa-tree-city"></i> Village Square</a></div>
    <div class="nav-flags">
        <img src="https://flagcdn.com/w80/id.png" alt="Indonesia" class="flag-icon">
        <img src="https://flagcdn.com/w80/us.png" alt="Inggris/Amerika" class="flag-icon flag-active"> 
    </div>
    <div class="user-actions">
        <a href="user_profile.php" class="user-link"><i class="fa-solid fa-address-card"></i> Villager ID</a>
        <a href="logout.php" class="user-link logout-btn"><i class="fa-solid fa-person-walking-arrow-right"></i> Leave</a>
    </div>
</div>

<header>
    <div class="village-badge"><i class="fa-solid fa-feather-pointed"></i> THE SCRIBE'S TRIAL</div>
    <h1 class="logo-text">Fill in the Blanks</h1>
</header>

<div class="back-container">
    <a href="latihan_en.php" class="btn-back"><i class="fa-solid fa-arrow-left-long"></i> Back to The Barn</a>
    
    <div class="progress-wrapper">
        <div class="progress-info">
            <i class="fa-solid fa-scroll"></i> Scroll: <?php echo $page; ?> / <?php echo $pages; ?>
        </div>
        
        <?php if ($unlocked_page > $pages && $totalQuestions > 0): ?>
            <button onclick="openRestartModal()" class="btn-restart" title="Rewrite your history"><i class="fa-solid fa-rotate-left"></i></button>
        <?php endif; ?>
    </div>
</div>

<div class="quiz-container">
    <form id="quizForm" onsubmit="checkAnswers(event)">
        
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="question-box" id="qb_<?php echo $row['id']; ?>">
                    <h3 class="question-text"><?php echo $nomor_urut . ". " . htmlspecialchars($row['question']); ?></h3>
                    
                    <textarea class="essay-textarea" id="ans_<?php echo $row['id']; ?>" placeholder="Type the missing word(s) here..." required></textarea>
                    
                    <input type="hidden" id="key_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['expected_keyword']); ?>">
                    
                    <div class="feedback-box" id="fb_<?php echo $row['id']; ?>"></div>
                </div>
                <?php $nomor_urut++; ?>
            <?php endwhile; ?>
            
            <button type="submit" class="btn-submit-quiz" id="submitBtn"><i class="fa-solid fa-feather"></i> Submit Parchment</button>

        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-scroll" style="font-size: 3rem; color: var(--earth-orange); margin-bottom: 15px; display: block;"></i>
                The scribes are currently resting.<br>No questions available right now.
            </div>
        <?php endif; ?>

    </form>
</div>

<div id="resultModal" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalTitle">Trial Assessed!</h2>
        <div class="score-display" id="modalScore">0/10</div>
        <p id="modalMessage" style="font-weight: 600; font-size: 1.1rem; color: var(--wood-dark); margin-bottom: 25px;">You need to answer at least 8 questions correctly.</p>
        
        <button class="btn-modal btn-retry" id="btnRetry" onclick="location.reload()" style="display:none;"><i class="fa-solid fa-rotate-right"></i> Rewrite Again</button>
        
        <?php $nextPage = $page + 1; ?>
        <?php if($nextPage <= $pages): ?>
            <a href="?page=<?php echo $nextPage; ?>" class="btn-modal btn-next" id="btnNextPage" style="display:none;"><i class="fa-solid fa-arrow-right"></i> Next Scroll</a>
        <?php else: ?>
            <a href="latihan_en.php" class="btn-modal btn-next" id="btnNextPage" style="display:none;"><i class="fa-solid fa-trophy"></i> Finish Trial (Back to The Barn)</a>
        <?php endif; ?>
    </div>
</div>

<div id="restartModal" class="modal-overlay">
    <div class="modal-content">
        <h2 style="color: var(--earth-orange);"><i class="fa-solid fa-clock-rotate-left"></i> Rewrite History</h2>
        <p style="font-weight: 600; color: var(--wood-dark); margin-bottom: 20px;">Choose a scroll to practice your writing once again.</p>
        
        <select id="targetRestartPage" class="select-rustic">
            <?php for($i = 1; $i <= $pages; $i++): ?>
                <?php 
                    $startQ = ($i * 10) - 9; 
                    $endQ = $i * 10;
                ?>
                <option value="<?php echo $i; ?>">Scroll <?php echo $i; ?> (Questions <?php echo $startQ; ?> - <?php echo $endQ; ?>)</option>
            <?php endfor; ?>
        </select>
        
        <button onclick="executeRestart(event)" class="btn-modal btn-next"><i class="fa-solid fa-check"></i> Confirm Selection</button>
        <button type="button" onclick="closeRestartModal()" class="btn-modal btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</button>
    </div>
</div>

<script>
function openRestartModal() { document.getElementById('restartModal').style.display = 'flex'; }
function closeRestartModal() { document.getElementById('restartModal').style.display = 'none'; }

function executeRestart(e) {
    const targetPage = document.getElementById('targetRestartPage').value;
    const formData = new FormData();
    formData.append('action', 'reset_progress');
    formData.append('target_page', targetPage);
    
    if (e && e.target) {
        e.target.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparing scroll...';
    }
    
    fetch('latihan_essay.php', { method: 'POST', body: formData })
    .then(() => { window.location.href = '?page=' + targetPage; })
    .catch(err => alert("Error processing request."));
}

function checkAnswers(e) {
    e.preventDefault(); 
    
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Assessing your writing...';
    btn.style.background = 'var(--wood-medium)';
    btn.disabled = true;

    const questionBoxes = document.querySelectorAll('.question-box');
    let score = 0;
    let correctQuestionIds = [];
    
    questionBoxes.forEach(box => {
        const qId = box.id.split('_')[1];
        const textArea = document.getElementById(`ans_${qId}`);
        const userAnswerRaw = textArea.value;
        const userAnswer = userAnswerRaw.toLowerCase().trim();
        const expectedKeyword = document.getElementById(`key_${qId}`).value.toLowerCase().trim();
        const feedbackBox = document.getElementById(`fb_${qId}`);
        
        textArea.readOnly = true; 
        
        // Validasi: Jawaban mengandung kata kunci yang diharapkan
        if(userAnswer.includes(expectedKeyword) && userAnswer !== "") {
            score++;
            correctQuestionIds.push(qId);
            feedbackBox.innerHTML = '<i class="fa-solid fa-check-circle"></i> Correct!';
            feedbackBox.style.background = '#E8F5E9';
            feedbackBox.style.color = '#2E7D32';
            feedbackBox.style.border = '1px solid #81C784';
        } else {
            feedbackBox.innerHTML = `<i class="fa-solid fa-xmark-circle"></i> Incorrect. The right answer is: <b>${expectedKeyword}</b>`;
            feedbackBox.style.background = '#FFEBEE';
            feedbackBox.style.color = '#C62828';
            feedbackBox.style.border = '1px solid #E57373';
        }
        feedbackBox.style.display = 'flex';
    });

    const totalQuestions = questionBoxes.length;
    
    setTimeout(() => {
        const modal = document.getElementById('resultModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalScore = document.getElementById('modalScore');
        const modalMessage = document.getElementById('modalMessage');
        const btnNext = document.getElementById('btnNextPage');
        const btnRetry = document.getElementById('btnRetry');
        
        modalScore.innerText = `${score}/${totalQuestions}`;
        const passingScore = Math.ceil(totalQuestions * 0.8);

        if (score >= passingScore) {
            modalTitle.innerText = "Master Scribe!";
            modalTitle.style.color = "var(--leaf-green)";
            modalScore.style.color = "var(--leaf-green)";
            modalMessage.innerText = "Excellent writing. Progress Saved!";
            document.querySelector('#resultModal .modal-content').style.borderColor = "var(--leaf-green)";
            
            btnNext.style.display = "block"; 
            btnRetry.style.display = "none"; 

            // Simpan Progres Halaman + ID Soal Benar ke Server
            const nextPageNum = <?php echo $nextPage; ?>;
            const formData = new FormData();
            formData.append('action', 'save_progress');
            formData.append('next_page', nextPageNum);
            formData.append('correct_ids', JSON.stringify(correctQuestionIds));
            
            fetch('latihan_essay.php', { method: 'POST', body: formData });
        } else {
            modalTitle.innerText = "Rewrite Needed!";
            modalTitle.style.color = "var(--earth-orange)";
            modalScore.style.color = "var(--earth-orange)";
            modalMessage.innerText = `You need to construct at least ${passingScore} correct responses.`;
            document.querySelector('#resultModal .modal-content').style.borderColor = "var(--earth-orange)";
            
            btnNext.style.display = "none"; 
            btnRetry.style.display = "block"; 
        }
        
        btn.innerHTML = '<i class="fa-solid fa-lock"></i> Scroll Sealed';
        modal.style.display = "flex";
    }, 1000); 
}
</script>
</body>
</html>