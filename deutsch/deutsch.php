<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback'])) {
    $pesan = $conn->real_escape_string($_POST['feedback']);
    $uid = $_SESSION['user_id'];

    if (!empty($pesan)) {
        $conn->query("INSERT INTO feedback (user_id, pesan) VALUES ($uid, '$pesan')");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            exit(); 
        }
        echo "<script>alert('Terima kasih atas masukan Anda!');</script>";
    }
}

$category = isset($_GET['cat']) ? $_GET['cat'] : '';
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : "";

$whereClause = "WHERE 1=1";
if ($category == 'pendek') { $whereClause .= " AND category = 'pendek'"; } 
elseif ($category == 'panjang') { $whereClause .= " AND category = 'panjang'"; }

if ($searchTerm !== "") {
    $whereClause .= " AND (title LIKE '%$searchTerm%' OR content LIKE '%$searchTerm%')";
}

$sql = "SELECT * FROM stories $whereClause ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);

$totalResultCount = $conn->query("SELECT COUNT(*) AS total FROM stories $whereClause");
$rowCount = $totalResultCount->fetch_assoc();
$totalStories = $rowCount['total'];
$pages = ceil($totalStories / $limit);

/* =========================================================
   RESPON KHUSUS AJAX UNTUK PERPINDAHAN MENU TANPA FLASH
========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    // Render HTML Shelf
    ob_start();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            ?>
            <div class="book" 
                 data-title="<?php echo htmlspecialchars($row['title']); ?>"
                 data-content="<?php echo htmlspecialchars($row['content']); ?>"
                 data-q1="<?php echo htmlspecialchars($row['question_1']); ?>"
                 data-q2="<?php echo htmlspecialchars($row['question_2']); ?>"
                 data-a1="<?php echo htmlspecialchars($row['answer_1']); ?>"
                 data-a2="<?php echo htmlspecialchars($row['answer_2']); ?>"
                 onclick="openReader(this)">
                <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
            </div>
            <?php
        }
    } else {
        echo '<p style="text-align: center; grid-column: 1/-1; font-weight: bold; font-size: 1.2rem;">Keine Geschichten gefunden.</p>';
    }
    $shelfHtml = ob_get_clean();

    // Render HTML Pagination
    ob_start();
    if ($pages > 1) {
        for($i = 1; $i <= $pages; $i++) {
            $activeClass = ($page == $i) ? 'active' : '';
            $pageUrl = "deutsch.php?page=$i&cat=$category&search=" . urlencode($searchTerm);
            echo "<a href=\"$pageUrl\" class=\"page-link glass-effect $activeClass\">$i</a>";
        }
    }
    $paginationHtml = ob_get_clean();

    echo json_encode([
        'shelf' => $shelfHtml,
        'pagination' => $paginationHtml,
        'category' => $category,
        'search' => $searchTerm,
        'page' => $page
    ]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#1a1a1a">

    <title>Deutsch with Elroy | Library</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&family=Lora:ital,wght@0,400;0,700;1,400&family=Poppins:wght@400;600;800;900&display=swap" rel="stylesheet">
<style>
    @view-transition {
        navigation: auto;
    }

    html {
        background-color: #1a1a1a !important; /* Mencegah flash putih saat awal load */
    }

    :root {
        color-scheme: dark;
        
        --de-black: #000000; 
        --de-red: #DD0000; 
        --de-gold: #FFCE00; 
        --glass-white: rgba(255, 255, 255, 0.75);
        --glass-light: rgba(244, 244, 244, 0.65);
        --glass-blur: blur(12px);
        --text-dark: #1A1A1A;
        --border-color: #D1D1D1;
        --shadow-bold: 6px 6px 0px var(--de-black);
        --shadow-hover: 10px 10px 0px var(--de-black);
        --transition: all 0.2s ease-in-out;
    }

    html, body {
        background-color: #1a1a1a !important;
        margin: 0;
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Poppins', sans-serif; 
        color: var(--text-dark); 
        overflow-x: hidden;
        min-height: 100vh;
        
        background-image: url('https://images.unsplash.com/photo-1560969184-10fe8719e047?q=80&w=2070&auto=format&fit=crop');
        background-repeat: no-repeat;
        background-position: center center;
        background-attachment: fixed;
        background-size: cover;
    }

    body::before {
        content: '';
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.15);
        z-index: -1;
    }

    .glass-effect {
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
    }

    .flag-bar {
        height: 8px; width: 100%; display: flex; position: fixed; top: 0; z-index: 2000;
        background: linear-gradient(to right, var(--de-black) 33.3%, var(--de-red) 33.3%, var(--de-red) 66.6%, var(--de-gold) 66.6%);
    }

    .user-nav {
        display: grid;
        grid-template-columns: 1fr auto 1fr; 
        padding: 10px 40px;
        border-bottom: 3px solid var(--de-black); align-items: center;
        position: sticky; top: 8px; z-index: 1000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .nav-flags {
        grid-column: 2; 
        display: flex; gap: 20px; align-items: flex-start;
        position: relative;
        padding: 15px 15px 0 15px; 
    }
    
    .nav-flags::before {
        content: '';
        position: absolute;
        top: 2px; left: 0; right: 0;
        height: 10px; background: #222; 
        border: 2px solid var(--de-black);
        box-shadow: 3px 3px 0px rgba(0,0,0,0.3);
        border-radius: 5px; z-index: 1;
    }
    
    .flag-wrapper {
        position: relative;
        transform-origin: top center;
        animation: swing-flag 3s ease-in-out infinite alternate;
        filter: drop-shadow(5px 5px 0px rgba(0,0,0,0.2)); 
        z-index: 2; 
    }
    
    .flag-wrapper:nth-child(odd) { animation-duration: 3.3s; animation-direction: alternate-reverse; }
    .flag-wrapper:nth-child(even) { animation-duration: 2.9s; }

    .flag-wrapper::before {
        content: '';
        position: absolute;
        top: -12px; left: 50%; transform: translateX(-50%);
        width: 14px; height: 16px;
        background: transparent;
        border: 3px solid #555; 
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        z-index: -1; 
    }

    .flag-icon {
        width: 38px; height: 55px; object-fit: cover;
        clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%);
        border: 2px solid var(--de-black);
        border-top: 2px solid var(--de-black); 
        display: block; transition: 0.3s; cursor: pointer;
    }

    .flag-wrapper:hover {
        z-index: 10; animation-play-state: paused;
        filter: drop-shadow(5px 5px 0px var(--de-red));
    }

    @keyframes swing-flag {
        0% { transform: rotate(6deg); }
        100% { transform: rotate(-6deg); }
    }

    .user-actions {
        grid-column: 3; justify-self: end; 
        display: flex; gap: 25px; align-items: center;
    }
    .user-link {
        color: var(--text-dark); text-decoration: none; font-weight: 800;
        font-size: 0.8rem; transition: var(--transition); display: flex; align-items: center; gap: 8px;
        letter-spacing: 1px; text-transform: uppercase;
    }
    .user-link:hover { color: var(--de-red); transform: translateY(-2px); }

    header { 
        padding: 50px 15px; text-align: center; 
        background: transparent; position: relative;
    }
    
    .logo-container {
        max-width: 280px; margin: 0 auto 20px; 
        display: flex; justify-content: center; align-items: center;
    }
    .logo-container img { width: 100%; height: auto; object-fit: contain; }

    .main-menu { 
        display: flex; justify-content: center; gap: 15px; margin-top: 30px; flex-wrap: wrap; 
    }
    
    .menu-btn {
        padding: 12px 25px; 
        border: 2px solid var(--de-black);
        color: var(--text-dark); 
        text-decoration: none; font-weight: 800; font-size: 0.85rem; 
        display: flex; align-items: center; gap: 8px; 
        text-transform: uppercase; letter-spacing: 1.5px;
        transition: var(--transition);
        box-shadow: 4px 4px 0px var(--de-black);
    }
    
    .menu-btn.active, .menu-btn:hover { 
        background: var(--de-black); color: #fff; 
        box-shadow: 4px 4px 0px var(--de-red);
        transform: translate(-2px, -2px);
    }

    .menu-btn.btn-kompetisi {
        border-color: var(--de-red); color: var(--de-red);
        box-shadow: 4px 4px 0px var(--de-red);
    }
    .menu-btn.btn-kompetisi:hover {
        background: var(--de-red); color: #fff;
        box-shadow: 4px 4px 0px var(--de-black);
    }

    .search-wrapper { 
        width: 90%; max-width: 700px; 
        margin: 40px auto; position: relative; z-index: 50; 
    }
    .search-wrapper form { 
        display: flex; border: 3px solid var(--de-black); 
        box-shadow: var(--shadow-bold);
    }
    .search-input { 
        flex: 1; padding: 15px 25px; border: none; outline: none; font-size: 1.1rem; 
        font-family: 'Lora', serif; font-weight: 600; background: transparent;
    }
    .search-btn { 
        padding: 15px 40px; border: none; border-left: 3px solid var(--de-black);
        background: var(--de-gold); cursor: pointer; font-weight: 900; 
        color: var(--de-black); transition: var(--transition);
        font-family: 'Poppins', sans-serif; font-size: 1rem;
    }
    .search-btn:hover { background: var(--de-red); color: #fff; }

    .shelf-container { 
        width: 95%; max-width: 1200px; margin: 20px auto; padding: 40px; 
        border: 4px solid var(--de-black); box-shadow: var(--shadow-bold);
    }
    
    .shelf { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
        gap: 30px; 
        transition: opacity 0.25s ease-in-out;
    }
    .shelf.loading {
        opacity: 0.3;
        pointer-events: none;
    }
    
    .book {
        height: 280px; 
        background: var(--glass-light);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border: 2px solid var(--de-black);
        border-left: 15px solid var(--de-red); 
        cursor: pointer; transition: var(--transition);
        display: flex; align-items: center; justify-content: center;
        padding: 20px; position: relative;
        box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
    }
    
    .book:nth-child(3n+1) { border-left-color: var(--de-red); }
    .book:nth-child(3n+2) { border-left-color: var(--de-black); }
    .book:nth-child(3n+3) { border-left-color: var(--de-gold); }

    .book:hover { 
        transform: translate(-5px, -5px); 
        box-shadow: var(--shadow-hover);
        background: rgba(255, 255, 255, 0.95);
    }
    
    .book-title { 
        font-family: 'UnifrakturMaguntia', serif; 
        font-size: 1.5rem; line-height: 1.2; 
        text-align: center; color: var(--de-black);
        word-break: break-word;
    }

    .pagination-container { display: flex; justify-content: center; gap: 8px; margin: 40px 10px; flex-wrap: wrap; }
    .page-link { 
        width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
        color: var(--de-black); text-decoration: none; border: 2px solid var(--de-black); 
        font-weight: 900; font-size: 1rem; transition: var(--transition);
    }
    .page-link.active, .page-link:hover { 
        background: var(--de-red); color: #fff; 
        transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--de-black);
    }

    .content-box {
        width: 95%; max-width: 900px; margin: 50px auto; padding: 40px;
        border: 4px solid var(--de-black); box-shadow: var(--shadow-bold);
    }
    
    .box-title {
        text-align:center; font-family:'UnifrakturMaguntia', serif; color:var(--de-red); 
        margin-bottom:30px; font-size: 2.5rem; letter-spacing: 2px;
        text-shadow: 2px 2px 0px rgba(0,0,0,0.1);
    }

    textarea {
        width:100%; height:150px; padding:20px; border:2px solid var(--de-black); 
        font-family:'Lora', serif; resize:none; color:var(--text-dark); font-size:1.1rem; 
        background: rgba(255, 255, 255, 0.5);
        box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05);
    }
    textarea:focus { outline: none; border-color: var(--de-red); background: rgba(255,255,255,0.8); }

    .btn-classic {
        background: var(--de-black); color: #fff; border: 2px solid var(--de-black);
        padding: 15px 40px; font-weight: 900; cursor: pointer; transition: var(--transition);
        text-transform: uppercase; letter-spacing: 2px; font-family: 'Poppins', sans-serif;
    }
    .btn-classic:hover { 
        background: var(--de-gold); color: var(--de-black); 
        transform: translate(-3px, -3px); box-shadow: 6px 6px 0px var(--de-red); 
    }

    .feedback-item { 
        background: var(--glass-light); padding: 20px; border-left: 5px solid var(--de-gold);
        border-bottom: 2px solid var(--border-color); margin-bottom: 15px; font-family: 'Lora', serif;
    }

    #reader { 
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(244, 244, 244, 0.85); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
        z-index: 9999; overflow-y: auto; 
    }
    
    .reader-nav { 
        position: sticky; top: 0; border-bottom: 3px solid var(--de-black); 
        padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 100;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .reader-nav h2 { 
        font-family: 'UnifrakturMaguntia', serif; margin: 0; font-size: 1.8rem; color: var(--de-black); 
    }
    
    .reader-paper {
        width: 90%; max-width: 800px; margin: 40px auto; padding: 60px; color: #111; 
        border: 4px solid var(--de-black); box-shadow: 10px 10px 0px rgba(0,0,0,0.25);
    }
    
    .novel-text { 
        font-family: 'Lora', serif; font-size: 1.25rem; line-height: 2; 
        text-align: justify; margin-bottom: 50px; color: #222; white-space: pre-line;
    }

    .essay-box { 
        background: rgba(244, 244, 244, 0.5); padding: 40px; border: 2px solid var(--de-black); 
        margin-top: 50px; border-top: 8px solid var(--de-red);
    }
    .essay-box h3 { font-family: 'Poppins'; font-weight: 900; text-transform: uppercase; font-size: 1.2rem; margin-top: 0; }
    
    .correction { padding: 20px; border: 2px solid; margin: 15px 0; font-family: 'Poppins'; display: none; }

    #duelModal { 
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 999999; justify-content: center; align-items: center; 
    }
    .modal-content { 
        padding: 40px; text-align: center; 
        color: var(--de-black); max-width: 450px; border: 4px solid var(--de-gold); 
        box-shadow: 15px 15px 0px var(--de-red); font-family: 'Poppins', sans-serif;
    }
    
    @media (max-width: 600px) {
        .user-nav { grid-template-columns: 1fr; gap: 15px; padding: 10px 20px; }
        .nav-flags { grid-column: 1; justify-content: center; padding-top: 10px;}
        .user-actions { grid-column: 1; justify-self: center; gap: 15px;}
        .main-menu { flex-direction: column; align-items: center; gap: 10px; }
        .menu-btn { width: 100%; justify-content: center; }
        .search-wrapper form { flex-direction: column; }
        .search-btn { border-left: none; border-top: 3px solid var(--de-black); }
        .reader-paper { padding: 30px 20px; }
        .novel-text { font-size: 1.1rem; }
    }
</style>
</head>
<body>

<div class="flag-bar"></div>

<div class="user-nav glass-effect">
    <div>
        <a href="index.php" class="user-link">
            <i class="fa-solid fa-house"></i> LOBBY
        </a>
    </div>

    <div class="nav-flags">
        <div class="flag-wrapper">
            <a href="english.php"><img src="https://flagcdn.com/w80/us.png" alt="Inggris" class="flag-icon" title="Inggris"></a>
        </div>
        <div class="flag-wrapper">
            <a href="deutsch.php"><img src="https://flagcdn.com/w80/de.png" alt="Jerman" class="flag-icon" title="Jerman"></a>
        </div>
        <div class="flag-wrapper">
            <a href="japan.php"><img src="https://flagcdn.com/w80/jp.png" alt="Jepang" class="flag-icon" title="Jepang"></a>
        </div>
    </div>

    <div class="user-actions">
        <a href="user_profile.php" class="user-link">
            <i class="fa-solid fa-user-shield"></i> PROFIL
        </a>
        <a href="logout.php" class="user-link" style="color: var(--de-red);">
            <i class="fa-solid fa-right-from-bracket"></i> KELUAR
        </a>
    </div>
</div>

<header>
    <div class="logo-container">
        <a href="home.php">
            <img src="logo_website/gambar.1.png" alt="Logo">
        </a>
    </div>
    <div class="main-menu">
        <a href="deutsch.php" class="menu-btn glass-effect <?php echo ($category == '') ? 'active' : ''; ?>">ALLE BUCH</a>
        <a href="deutsch.php?cat=pendek" class="menu-btn glass-effect <?php echo ($category == 'pendek') ? 'active' : ''; ?>">KURZ</a>
        <a href="deutsch.php?cat=panjang" class="menu-btn glass-effect <?php echo ($category == 'panjang') ? 'active' : ''; ?>">ROMANE</a>
        <a href="materi.php" class="menu-btn glass-effect">
            <i class="fa-solid fa-book-open"></i> LERNEN
        </a>
        <a href="latihan.php" class="menu-btn glass-effect">
            <i class="fa-solid fa-pen-nib"></i> ÜBUNG
        </a>
        <a href="kompetisi.php" class="menu-btn btn-kompetisi glass-effect">
            <i class="fa-solid fa-trophy"></i> DUELL
        </a>
        <a href="obrolan.php" class="menu-btn glass-effect">
            <i class="fa-solid fa-message"></i> FORUM
        </a>
    </div>
</header>

<div class="search-wrapper">
    <form action="deutsch.php" method="GET" class="glass-effect">
        <input type="hidden" name="cat" value="<?php echo $category; ?>">
        <input type="text" name="search" class="search-input" placeholder="Titel suchen..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="search-btn">SUCHEN</button>
    </form>
</div>

<div class="shelf-container glass-effect">
    <div class="shelf">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="book" 
                     data-title="<?php echo htmlspecialchars($row['title']); ?>"
                     data-content="<?php echo htmlspecialchars($row['content']); ?>"
                     data-q1="<?php echo htmlspecialchars($row['question_1']); ?>"
                     data-q2="<?php echo htmlspecialchars($row['question_2']); ?>"
                     data-a1="<?php echo htmlspecialchars($row['answer_1']); ?>"
                     data-a2="<?php echo htmlspecialchars($row['answer_2']); ?>"
                     onclick="openReader(this)">
                    <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; font-weight: bold; font-size: 1.2rem;">Keine Geschichten gefunden.</p>
        <?php endif; ?>
    </div>
</div>

<div class="pagination-container">
    <?php if ($pages > 1): ?>
        <?php for($i = 1; $i <= $pages; $i++): ?>
            <a href="deutsch.php?page=<?php echo $i; ?>&cat=<?php echo $category; ?>&search=<?php echo urlencode($searchTerm); ?>" 
               class="page-link glass-effect <?php echo ($page == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<div class="content-box glass-effect">
    <h2 class="box-title">Ihre Meinung</h2>
    <form id="feedbackForm">
        <textarea id="feedbackText" name="feedback" required placeholder="Schreiben Sie hier Ihr Feedback..."></textarea>
        <div style="text-align:center;">
            <button type="submit" class="btn-classic" style="margin-top:25px;">ABSENDEN</button>
        </div>
    </form>
</div>

<div class="content-box glass-effect" style="margin-top: -20px;">
    <h2 class="box-title" style="font-size: 2rem;">Feedback Liste</h2>
    <div id="feedbackListContainer">
        <p style="text-align:center; font-family:'Lora';">Laden...</p>
    </div>
</div>

<div id="reader">
    <div class="reader-nav glass-effect">
        <button onclick="closeReader()" class="btn-classic" style="padding: 10px 20px; font-size: 0.8rem;">ZURÜCK</button>
        <h2 id="readingTitle"></h2>
        <button id="audioBtn" onclick="toggleAudio()" class="btn-classic" style="background: var(--de-red); border-color: var(--de-red); padding: 10px 20px; font-size: 0.8rem;">HÖREN 🔊</button>
    </div>
    <div class="reader-paper glass-effect">
        <div class="novel-text" id="novelBody"></div>
        
        <div class="essay-box">
            <h3>Übung (Latihan)</h3>
            <p id="displayQ1" style="font-weight:800; font-family: 'Poppins'; margin-bottom:10px;"></p>
            <textarea id="ans1" placeholder="Jawaban Anda..."></textarea>
            <div id="corr1" class="correction"></div>
            
            <p id="displayQ2" style="font-weight:800; font-family: 'Poppins'; margin: 30px 0 10px 0;"></p>
            <textarea id="ans2" placeholder="Jawaban Anda..."></textarea>
            <div id="corr2" class="correction"></div>
            
            <button onclick="verifyAnswers()" class="btn-classic" style="width:100%; margin-top:35px;">ANTWORT PRÜFEN (KOREKSI)</button>
        </div>
        
        <div style="text-align:center; margin-top:60px;">
             <button onclick="closeReader()" style="background:none; border:none; color:var(--de-red); font-family:'Poppins'; cursor:pointer; font-weight:900; text-decoration:underline;">LESEN BEENDEN</button>
        </div>
    </div>
</div>

<div id="duelModal">
    <div class="modal-content glass-effect">
        <h2 style="margin:0; font-family:'UnifrakturMaguntia'; font-size: 2.5rem; color:var(--de-red);">ACHTUNG!</h2>
        <p id="duelText" style="font-weight:700; margin:25px 0; font-size:1.2rem; line-height:1.5;"></p>
        <div style="display:flex; gap:15px;">
            <button onclick="acceptTheDuel()" class="btn-classic" style="flex:1; padding:15px; font-size:1rem;">ANNEHMEN</button>
            <button onclick="rejectTheDuel()" class="btn-classic" style="flex:1; padding:15px; font-size:1rem; background: #fff; color: var(--de-black);">ABLEHNEN</button>
        </div>
    </div>
</div>

<script>
// --- LOGIKA FILTER TANPA FLASH (AJAX + HISTORY API) ---
function loadPath(targetUrl, pushState = true) {
    const shelf = document.querySelector('.shelf');
    if (shelf) shelf.classList.add('loading');

    const separator = targetUrl.includes('?') ? '&' : '?';
    const fetchUrl = targetUrl + separator + 'ajax=1';

    fetch(fetchUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        const shelfContainer = document.querySelector('.shelf');
        const paginationContainer = document.querySelector('.pagination-container');
        
        if (shelfContainer) shelfContainer.innerHTML = data.shelf;
        if (paginationContainer) paginationContainer.innerHTML = data.pagination;

        // Update status aktif pada tombol menu
        const urlParams = new URLSearchParams(targetUrl.includes('?') ? targetUrl.split('?')[1] : '');
        const currentCat = urlParams.get('cat') || '';

        document.querySelectorAll('.main-menu a.menu-btn').forEach(btn => {
            const btnHref = btn.getAttribute('href');
            if (btnHref && (btnHref.includes('cat=') || btnHref === 'deutsch.php')) {
                const btnParams = new URLSearchParams(btnHref.includes('?') ? btnHref.split('?')[1] : '');
                const btnCat = btnParams.get('cat') || '';
                
                if (btnCat === currentCat) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });

        // Update nilai search input jika ada
        const searchInput = document.querySelector('.search-input');
        if (searchInput && data.search !== undefined) {
            searchInput.value = data.search;
        }

        // Update hidden field cat di form pencarian
        const hiddenCat = document.querySelector('.search-wrapper input[name="cat"]');
        if (hiddenCat) {
            hiddenCat.value = data.category || '';
        }

        if (pushState) {
            history.pushState({ url: targetUrl }, '', targetUrl);
        }
    })
    .catch(err => console.error("Error loading path:", err))
    .finally(() => {
        if (shelf) shelf.classList.remove('loading');
    });
}

// Delegasi Event Click untuk Menu & Pagination
document.addEventListener('click', function(e) {
    // Handling tombol filter menu
    const menuBtn = e.target.closest('.main-menu a.menu-btn');
    if (menuBtn) {
        const href = menuBtn.getAttribute('href');
        if (href && (href.startsWith('deutsch.php') || href.startsWith('?'))) {
            e.preventDefault();
            loadPath(href);
            return;
        }
    }

    // Handling tombol pagination
    const pageLink = e.target.closest('.pagination-container .page-link');
    if (pageLink) {
        e.preventDefault();
        const href = pageLink.getAttribute('href');
        if (href) loadPath(href);
        return;
    }
});

// Handling pencarian via AJAX
const searchForm = document.querySelector('.search-wrapper form');
if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const catInput = searchForm.querySelector('input[name="cat"]');
        const searchInput = searchForm.querySelector('input[name="search"]');
        const cat = catInput ? catInput.value : '';
        const search = searchInput ? searchInput.value : '';
        const targetUrl = `deutsch.php?cat=${encodeURIComponent(cat)}&search=${encodeURIComponent(search)}`;
        loadPath(targetUrl);
    });
}

// Handling tombol Back / Forward browser
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
        loadPath(e.state.url, false);
    } else {
        loadPath(location.href, false);
    }
});

// --- LOGIKA FEEDBACK AJAX ---
const feedbackForm = document.getElementById('feedbackForm');
const feedbackList = document.getElementById('feedbackListContainer');

function loadFeedback() {
    fetch('ambil_feedback_user.php?t=' + new Date().getTime())
        .then(res => res.text())
        .then(data => { feedbackList.innerHTML = data; })
        .catch(err => console.error("Error loading feedback:", err));
}

if (feedbackForm) {
    feedbackForm.onsubmit = function(e) {
        e.preventDefault();
        const textInput = document.getElementById('feedbackText');
        const formData = new FormData();
        formData.append('feedback', textInput.value);

        fetch('deutsch.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(() => {
            textInput.value = ''; 
            loadFeedback(); 
        }).catch(err => alert("Gagal mengirim masukan."));
    };
}

setInterval(loadFeedback, 10000);

// --- LOGIKA GLOBAL DUEL ---
let activeMatchId = null;

function globalCheckDuel() {
    fetch('check_invitation.php?t=' + Date.now())
    .then(res => res.json())
    .then(data => {
        if(data.status === 'invited') {
            activeMatchId = data.match_id;
            document.getElementById('duelText').innerText = data.challenger_name + " fordert dich heraus!";
            document.getElementById('duelModal').style.display = 'flex'; 
            
            let audio = new Audio('https://www.soundjay.com/buttons/sounds/button-3.mp3');
            audio.play().catch(e => console.log("Audio diblokir"));
        } 
        else if(data.status === 'found') {
            window.location.href = 'battle_arena.php?match_id=' + data.match_id;
        }
    })
    .catch(err => console.log("Gagal mengecek radar"));
}

function acceptTheDuel() {
    window.location.href = 'matchmaking_handler.php?action=accept&match_id=' + activeMatchId;
}

function rejectTheDuel() {
    fetch('matchmaking_handler.php?action=reject&match_id=' + activeMatchId)
    .then(() => {
        document.getElementById('duelModal').style.display = 'none';
    });
}

setInterval(globalCheckDuel, 3000);

// --- LOGIKA READER ---
const synth = window.speechSynthesis;
let isSpeaking = false;
let currentA1 = "", currentA2 = "";

function openReader(el) {
    document.getElementById('readingTitle').innerText = el.getAttribute('data-title');
    document.getElementById('novelBody').innerText = el.getAttribute('data-content');
    document.getElementById('displayQ1').innerText = "Frage 1: " + el.getAttribute('data-q1');
    document.getElementById('displayQ2').innerText = "Frage 2: " + el.getAttribute('data-q2');
    currentA1 = el.getAttribute('data-a1');
    currentA2 = el.getAttribute('data-a2');

    document.querySelectorAll('.correction').forEach(c => c.style.display = 'none');
    document.getElementById('ans1').value = "";
    document.getElementById('ans2').value = "";
    
    document.getElementById('reader').style.display = "block";
    document.body.style.overflow = "hidden";
    document.getElementById('reader').scrollTop = 0;
}

function closeReader() { 
    synth.cancel(); isSpeaking = false;
    document.getElementById('reader').style.display = "none"; 
    document.body.style.overflow = "auto"; 
}

function verifyAnswers() {
    showCorrection('ans1', 'corr1', currentA1);
    showCorrection('ans2', 'corr2', currentA2);
}

function showCorrection(inputId, corrId, correctText) {
    const userAns = document.getElementById(inputId).value.toLowerCase().trim();
    const corrDiv = document.getElementById(corrId);
    const key = (correctText || "").toLowerCase().trim();
    
    corrDiv.style.display = "block";
    if (key && userAns.includes(key)) {
        corrDiv.style.background = "#e8f5e9"; corrDiv.style.color = "#2e7d32"; corrDiv.style.borderColor = "#2e7d32";
        corrDiv.innerHTML = "<b>Richtig! (Benar)</b><br>Kunci: " + correctText;
    } else {
        corrDiv.style.background = "#fff1f0"; corrDiv.style.color = "#cf1322"; corrDiv.style.borderColor = "#cf1322";
        corrDiv.innerHTML = "<b>Falsch. (Salah)</b><br>Kunci: " + correctText;
    }
}

function toggleAudio() {
    if (isSpeaking) { synth.cancel(); isSpeaking = false; } 
    else {
        const ut = new SpeechSynthesisUtterance(document.getElementById('novelBody').innerText);
        ut.lang = 'de-DE'; ut.onstart = () => isSpeaking = true; ut.onend = () => isSpeaking = false;
        synth.speak(ut);
    }
}

window.onload = loadFeedback;
</script>
</body>
</html>