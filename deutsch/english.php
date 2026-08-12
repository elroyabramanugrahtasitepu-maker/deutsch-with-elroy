<?php
session_start();

// Cek apakah user sudah login, jika belum arahkan ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Koneksi Database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

/* =========================
   SIMPAN FEEDBACK
========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback'])) {
    $pesan = $conn->real_escape_string($_POST['feedback']);
    $uid = $_SESSION['user_id'];

    if (!empty($pesan)) {
        $conn->query("INSERT INTO feedback (user_id, pesan) VALUES ($uid, '$pesan')");
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            exit(); 
        }
        
        echo "<script>alert('Message pinned to the notice board!');</script>";
    }
}

// 2. Logika Menu (Filter Kategori Absolut)
$category = isset($_GET['cat']) ? $_GET['cat'] : '';

// 3. Logika Pagination & Search
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : "";

$whereClause = "WHERE 1=1";
if ($category == 'pendek') {
    $whereClause .= " AND category = 'pendek'"; 
} elseif ($category == 'panjang') {
    $whereClause .= " AND category = 'panjang'"; 
}

if ($searchTerm !== "") {
    $whereClause .= " AND (title LIKE '%$searchTerm%' OR content LIKE '%$searchTerm%')";
}

// MENGAMBIL DATA DARI TABEL BAHASA INGGRIS (stories_en)
$sql = "SELECT * FROM stories_en $whereClause ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);

// Hitung total data
$totalResultCount = $conn->query("SELECT COUNT(*) AS total FROM stories_en $whereClause");
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
        $icons = ['fa-bookmark', 'fa-feather-pointed', 'fa-map'];
        $std_count = 0;
        while($row = $result->fetch_assoc()) {
            $randomIcon = $icons[$std_count % count($icons)];
            $std_count++;
            ?>
            <div class="book" 
                 data-title="<?php echo htmlspecialchars($row['title']); ?>"
                 data-content="<?php echo htmlspecialchars($row['content']); ?>"
                 data-q1="<?php echo htmlspecialchars($row['question_1']); ?>"
                 data-q2="<?php echo htmlspecialchars($row['question_2']); ?>"
                 data-a1="<?php echo htmlspecialchars($row['answer_1']); ?>"
                 data-a2="<?php echo htmlspecialchars($row['answer_2']); ?>"
                 onclick="openReader(this)">
                 
                <div class="book-header">
                    <div class="leaf-icon"><i class="fa-solid <?php echo $randomIcon; ?>"></i></div>
                    <div class="genre-info">
                        <div class="book-category">Path: <?php echo htmlspecialchars($row['category'] ?: 'General'); ?></div>
                    </div>
                </div>
                
                <div class="book-body">
                    <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p style="text-align: center; grid-column: 1/-1; font-weight: 800; color: var(--text-muted); font-size: 1.2rem; background: rgba(255, 255, 255, 0.6); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(10px);">The trails are empty. Nothing found.</p>';
    }
    $shelfHtml = ob_get_clean();

    // Render HTML Pagination
    ob_start();
    if ($pages > 1) {
        for($i = 1; $i <= $pages; $i++) {
            $activeClass = ($page == $i) ? 'active' : '';
            $pageUrl = "?page=$i&cat=$category&search=" . urlencode($searchTerm);
            echo "<a href=\"$pageUrl\" class=\"page-link $activeClass\">$i</a>";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English | Learn & Grow</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    html {
        background-color: #EBF5FB; /* Menghilangkan flash putih saat pertama kali dibuka */
    }

    :root { 
        /* Tema Royal Light Glassmorphism */
        --glass-bg: rgba(255, 255, 255, 0.65);       /* Kaca putih cerah */
        --glass-blur: blur(16px);               
        --glass-border: rgba(255, 255, 255, 0.8);    /* Border kaca tegas */
        
        --shadow-glass: 0 8px 32px rgba(0, 48, 73, 0.1); 
        
        --text-main: #0B192C;                     /* Navy sangat gelap untuk teks */
        --text-muted: #475569;                    /* Abu-abu kebiruan */
        --accent-red: #D90429;                    /* London Bus Red */
        --accent-blue: #003049;                   /* Royal Thames Blue */
        --accent-gold: #D4AF37;                   /* Aksen emas */
        
        --radius-lg: 16px;                        
        --radius-md: 8px;
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Nunito', sans-serif; 
        color: var(--text-main); 
        margin: 0; 
        overflow-x: hidden;
        min-height: 100vh;
        
        /* BACKGROUND LONDON SIANG HARI */
        background-color: #EBF5FB; 
        background-image: url('https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?q=80&w=2070&auto=format&fit=crop');
        background-attachment: fixed;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }

    /* Lapisan overlay terang agar pemandangan sedikit lembut dan UI terbaca jelas */
    body::before {
        content: '';
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.2);
        z-index: -1;
    }

    /* --- LIGHT GLASS PANEL UTILITY --- */
    .glass-panel {
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-glass);
    }

    /* --- TOP NAVIGATION --- */
    .user-nav {
        display: grid;
        grid-template-columns: 1fr auto 1fr; 
        padding: 10px 40px;
        align-items: center; position: sticky; top: 0; z-index: 1000;
        border-bottom: 1px solid rgba(255, 255, 255, 0.6);
    }

    .lobby-action a {
        color: var(--text-main); text-decoration: none; font-weight: 800;
        font-size: 0.95rem; transition: var(--transition); display: flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: var(--radius-md); 
        background: rgba(255, 255, 255, 0.5);
        width: fit-content;
        border: 1px solid var(--glass-border);
    }
    .lobby-action a:hover { 
        background: var(--accent-blue); color: white; border-color: var(--accent-blue);
    }

    /* --- CONTAINER BENDERA --- */
    .nav-flags {
        grid-column: 2; 
        display: flex; gap: 20px; align-items: flex-start;
        position: relative;
        padding: 15px 15px 0 15px; 
    }
    
    .nav-flags::before {
        content: '';
        position: absolute;
        top: 2px; 
        left: 0; right: 0;
        height: 8px; 
        background: rgba(0, 48, 73, 0.8); 
        border: 1px solid #001f30;
        box-shadow: 2px 2px 0px rgba(0,0,0,0.1);
        border-radius: 4px;
        z-index: 1;
    }
    
    .flag-wrapper {
        position: relative;
        transform-origin: top center;
        animation: swing-flag 3s ease-in-out infinite alternate;
        filter: drop-shadow(3px 3px 5px rgba(0,0,0,0.2)); 
        z-index: 2; 
    }
    
    .flag-wrapper:nth-child(odd) { animation-duration: 3.3s; animation-direction: alternate-reverse; }
    .flag-wrapper:nth-child(even) { animation-duration: 2.9s; }

    .flag-wrapper::before {
        content: '';
        position: absolute;
        top: -12px; 
        left: 50%; transform: translateX(-50%);
        width: 10px; height: 14px;
        background: transparent;
        border: 2px solid rgba(0, 48, 73, 0.6); 
        border-bottom: none;
        border-radius: 6px 6px 0 0;
        z-index: -1; 
    }

    .flag-icon {
        width: 38px; height: 55px; object-fit: cover;
        clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%);
        border: 1px solid rgba(255,255,255,0.8);
        border-top: 2px solid rgba(0, 48, 73, 0.8); 
        display: block;
        transition: 0.3s;
        cursor: pointer;
    }

    .flag-wrapper:hover {
        z-index: 10;
        animation-play-state: paused;
        filter: drop-shadow(0px 5px 10px rgba(217, 4, 41, 0.4));
    }

    @keyframes swing-flag {
        0% { transform: rotate(4deg); }
        100% { transform: rotate(-4deg); }
    }

    /* TOMBOL USER (DI KANAN) */
    .user-actions { 
        grid-column: 3; justify-self: end;
        display: flex; gap: 15px; align-items: center; 
    }
    .user-link {
        color: var(--text-main); text-decoration: none; font-weight: 800;
        font-size: 0.9rem; transition: var(--transition); display: flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,0.5); padding: 8px 15px; border-radius: var(--radius-md);
        border: 1px solid var(--glass-border);
    }
    .user-link:hover { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
    .logout-btn { color: #C0392B; border-color: rgba(217, 4, 41, 0.4); background: rgba(217, 4, 41, 0.1); }
    .logout-btn:hover { background: var(--accent-red); color: white; border-color: var(--accent-red); }

    /* --- HEADER & LOGO --- */
    header { padding: 60px 15px 30px; text-align: center; }
    .village-badge {
        display: inline-block; background: rgba(255,255,255,0.8); color: var(--accent-blue);
        font-size: 0.85rem; font-weight: 800; text-transform: uppercase;
        padding: 8px 20px; letter-spacing: 2px; margin-bottom: 20px; border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.9); backdrop-filter: var(--glass-blur);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .logo-text {
        font-family: 'Lora', serif; font-size: 4.5rem; color: var(--accent-blue);
        margin: 0; font-weight: 800; letter-spacing: -1px;
        text-shadow: 2px 2px 10px rgba(255,255,255,0.9);
    }
    .logo-subtitle {
        font-family: 'Nunito', sans-serif; color: var(--text-main);
        font-size: 1.3rem; font-weight: 700; margin-top: 10px;
        text-shadow: 1px 1px 5px rgba(255,255,255,0.8);
    }

    /* --- SEARCH BAR --- */
    .search-wrapper { width: 90%; max-width: 700px; margin: 30px auto 50px; }
    .search-wrapper form { 
        display: flex; border-radius: 30px;
        transition: var(--transition); overflow: hidden;
        border: 1px solid var(--glass-border);
        background: rgba(255, 255, 255, 0.7);
        box-shadow: var(--shadow-glass);
        backdrop-filter: blur(10px);
    }
    .search-wrapper form:focus-within { 
        border-color: var(--accent-blue); 
        box-shadow: 0 0 20px rgba(0, 48, 73, 0.2); 
    }
    .search-input { 
        flex: 1; padding: 18px 30px; border: none; outline: none; font-size: 1.05rem; 
        font-family: 'Nunito', sans-serif; font-weight: 700; color: var(--text-main);
        background: transparent;
    }
    .search-input::placeholder { color: var(--text-muted); font-weight: 600; }
    .search-btn { 
        padding: 0 35px; border: none; background: rgba(0, 48, 73, 0.05); 
        cursor: pointer; color: var(--accent-blue); transition: var(--transition); font-size: 1.2rem;
        border-left: 1px solid rgba(255,255,255,0.5);
    }
    .search-btn:hover { background: var(--accent-blue); color: #FFF; }

    /* --- MAIN NAVIGATION MENU --- */
    .main-menu { display: flex; justify-content: center; gap: 15px; margin-bottom: 50px; flex-wrap: wrap; }
    .menu-btn {
        padding: 10px 25px; color: var(--text-main); 
        text-decoration: none; font-weight: 800; font-size: 0.95rem; 
        display: flex; align-items: center; gap: 8px;
        border-radius: 30px; transition: var(--transition); 
        border: 1px solid var(--glass-border);
        background: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(8px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .menu-btn.active, .menu-btn:hover { 
        background: var(--accent-blue); color: white; border-color: var(--accent-blue);
        transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0, 48, 73, 0.3);
    }
    .menu-btn.btn-kompetisi { color: var(--accent-red); border-color: rgba(217, 4, 41, 0.3); background: rgba(217, 4, 41, 0.1); }
    .menu-btn.btn-kompetisi:hover { background: var(--accent-red); color: white; border-color: var(--accent-red); box-shadow: 0 6px 15px rgba(217, 4, 41, 0.4); }

    /* --- KARTU BUKU (Style Light Glass) --- */
    .shelf-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 20px; }
    
    .shelf { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 40px; 
        transition: opacity 0.25s ease-in-out;
    }
    .shelf.loading {
        opacity: 0.3;
        pointer-events: none;
    }
    
    .book {
        cursor: pointer; transition: var(--transition);
        display: flex; flex-direction: column; position: relative; 
        border-radius: var(--radius-lg); 
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        box-shadow: var(--shadow-glass);
        min-height: 250px;
        border: 1px solid var(--glass-border);
        border-top: 1px solid #FFF;
        border-left: 1px solid #FFF;
        overflow: hidden; 
    }
    
    /* Garis warna di punggung buku bergaya Inggris */
    .book::before {
        content: '';
        position: absolute;
        top: 0; left: 0; bottom: 0; width: 8px;
        background: var(--accent-blue); 
        z-index: 1;
    }

    .book:nth-child(3n+1)::before { background: var(--accent-blue); }
    .book:nth-child(3n+2)::before { background: var(--accent-red); } 
    .book:nth-child(3n+3)::before { background: var(--accent-gold); } 

    .book-header {
        padding: 20px 20px 0 25px; 
        display: flex; justify-content: space-between; align-items: flex-start;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding-bottom: 15px;
    }
    
    .leaf-icon { 
        width: 35px; height: 35px; background: rgba(255,255,255,0.8); border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: var(--accent-blue); 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Sinkronkan warna icon dengan punggung buku */
    .book:nth-child(3n+1) .leaf-icon { color: var(--accent-blue); }
    .book:nth-child(3n+2) .leaf-icon { color: var(--accent-red); }
    .book:nth-child(3n+3) .leaf-icon { color: #B7950B; }
    
    .genre-info { display: flex; flex-direction: column; align-items: flex-end; }
    
    .book-category { 
        font-family: 'Nunito', sans-serif; font-weight: 800; text-transform: uppercase;
        color: var(--text-muted); font-size: 0.75rem; letter-spacing: 1px;
    }

    .book-body { 
        padding: 30px 20px 30px 25px; flex: 1; display: flex; align-items: center; justify-content: center; 
    }
    
    .book-title { 
        font-family: 'Lora', serif; font-size: 1.6rem; font-weight: 800;
        text-align: center; color: var(--text-main); line-height: 1.4;
    }

    .book:hover { 
        transform: translateY(-8px) scale(1.02); 
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 15px 35px rgba(0, 48, 73, 0.15);
    }

    /* --- PAGINATION --- */
    .pagination-container { display: flex; justify-content: center; gap: 10px; margin: 60px 10px; flex-wrap: wrap; }
    .page-link { 
        width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
        background: rgba(255, 255, 255, 0.6); color: var(--text-main); font-weight: 800;
        text-decoration: none; border-radius: var(--radius-md); font-size: 1.1rem; 
        transition: var(--transition); border: 1px solid var(--glass-border);
        backdrop-filter: blur(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .page-link.active, .page-link:hover { 
        background: var(--accent-blue); color: white; border-color: var(--accent-blue);
        transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 48, 73, 0.3);
    }

    /* --- KOTAK KONTEN (Papan Terang) --- */
    .content-box {
        width: 95%; max-width: 800px; margin: 40px auto; padding: 45px;
        position: relative; border-radius: var(--radius-lg);
        background: var(--glass-bg);
        box-shadow: var(--shadow-glass);
        border: 1px solid var(--glass-border);
        border-top: 1px solid #FFF; border-left: 1px solid #FFF;
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
    }
    
    .box-title {
        text-align:center; font-family:'Lora', serif; color: var(--accent-blue); 
        margin-bottom:25px; font-size: 2rem; font-weight: 800; 
        text-shadow: 1px 1px 0px rgba(255,255,255,0.8);
    }

    textarea {
        width:100%; height:160px; padding:20px; border: 2px dashed rgba(0,0,0,0.1); border-radius: var(--radius-md);
        font-family:'Nunito', sans-serif; resize:none; color: var(--text-main); 
        font-size:1.05rem; background: rgba(255, 255, 255, 0.5); transition: var(--transition);
    }
    textarea:focus { outline: none; border-color: var(--accent-blue); background: rgba(255, 255, 255, 0.8); }
    textarea::placeholder { color: var(--text-muted); }

    .btn-rustic {
        background: var(--accent-blue); color: white; border: none; 
        padding: 12px 30px; font-weight: 800; cursor: pointer; transition: var(--transition);
        font-size: 1rem; border-radius: var(--radius-md); display: inline-block;
        font-family: 'Nunito', sans-serif; letter-spacing: 1px; text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(0, 48, 73, 0.2);
    }
    .btn-rustic:hover { background: #001f30; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 48, 73, 0.3); }

    .feedback-item { 
        padding: 20px; border-radius: var(--radius-md); 
        margin-bottom: 20px; transition: var(--transition);
        border: 1px solid rgba(255,255,255,0.8); border-left: 4px solid var(--accent-gold);
        background: rgba(255, 255, 255, 0.5);
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    /* --- READER MODE (Kaca Terang Nyaman Dibaca) --- */
    #reader { 
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(235, 245, 251, 0.85); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); z-index: 9999; overflow-y: auto; 
    }
    
    .reader-nav { 
        position: sticky; top: 0; border-bottom: 1px solid rgba(255, 255, 255, 0.6);
        padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; z-index: 100;
        background: rgba(255, 255, 255, 0.7); box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        backdrop-filter: blur(10px);
    }
    .reader-nav h2 { 
        font-family: 'Lora', serif; font-weight: 800; margin: 0; font-size: 1.3rem; color: var(--accent-blue); 
    }
    
    .reader-paper {
        width: 90%; max-width: 800px; margin: 40px auto 60px; 
        background: rgba(255, 255, 255, 0.85); padding: 60px 80px; color: var(--text-main); 
        box-shadow: 0 20px 50px rgba(0, 48, 73, 0.1);
        border-radius: var(--radius-lg);
        position: relative;
        border: 1px solid rgba(255,255,255,0.9);
    }
    
    .novel-text { 
        font-family: 'Lora', serif; font-size: 1.25rem; line-height: 2.2;
        text-align: justify; margin-bottom: 50px; color: #1E293B; white-space: pre-line;
    }

    .essay-box { 
        background: rgba(241, 245, 249, 0.6); padding: 40px; border-radius: var(--radius-md); 
        border: 1px solid rgba(203, 213, 225, 0.6); margin-top: 50px; position: relative;
    }
    .essay-box::before {
        content: 'VILLAGE TASK'; position: absolute; top: -14px; left: 30px; 
        background: var(--accent-red); color: white;
        padding: 6px 18px; font-weight: 800; border-radius: 20px; font-size: 0.8rem; letter-spacing: 1px;
    }
    
    .correction { padding: 15px 20px; border-radius: var(--radius-md); margin: 15px 0; font-weight: 700; display: none; background: rgba(255,255,255,0.8); }

    /* --- MODAL DUEL --- */
    #duelModal { 
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); z-index: 999999; justify-content: center; align-items: center; 
    }
    .modal-content { 
        padding: 50px; text-align: center; border-radius: var(--radius-lg);
        color: var(--text-main); max-width: 450px; border: 2px solid var(--accent-red);
        background: rgba(255, 255, 255, 0.9); box-shadow: 0 15px 40px rgba(217, 4, 41, 0.2);
    }
    
    @media (max-width: 768px) {
        .user-nav { grid-template-columns: 1fr; gap: 15px; padding: 15px; }
        .nav-flags { grid-column: 1; justify-content: center; padding-top: 10px; }
        .user-actions { grid-column: 1; justify-self: center; gap: 15px; }
        .logo-text { font-size: 3rem; }
        .search-wrapper form { flex-direction: column; border-radius: 15px; }
        .search-btn { padding: 15px; border-left: none; border-top: 1px solid rgba(0,0,0,0.05); }
        .reader-paper { padding: 40px 20px; }
        .shelf { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>

<div class="user-nav glass-panel">
    <div class="lobby-action">
        <a href="index.php"><i class="fa-solid fa-tree-city"></i> Village Square</a>
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
        <a href="user_profile.php" class="user-link"><i class="fa-solid fa-address-card"></i> Villager ID</a>
        <a href="logout.php" class="user-link logout-btn"><i class="fa-solid fa-person-walking-arrow-right"></i> Leave</a>
    </div>
</div>

<header>
    <div class="village-badge"><i class="fa-solid fa-bookmark"></i> LONDON DISCOVERY</div>
    <h1 class="logo-text">English</h1>
    <p class="logo-subtitle">Learn deeply, grow naturally.</p>
</header>

<div class="search-wrapper">
    <form action="" method="GET">
        <input type="hidden" name="cat" value="<?php echo $category; ?>">
        <input type="text" name="search" class="search-input" placeholder="Search the library..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
</div>

<div class="main-menu">
    <a href="english.php" class="menu-btn <?php echo ($category == '') ? 'active' : ''; ?>">All Paths</a>
    <a href="english.php?cat=pendek" class="menu-btn <?php echo ($category == 'pendek') ? 'active' : ''; ?>">Short Walks</a>
    <a href="english.php?cat=panjang" class="menu-btn <?php echo ($category == 'panjang') ? 'active' : ''; ?>">Long Journeys</a>
    <a href="materi_en.php" class="menu-btn"><i class="fa-solid fa-book-open"></i> The Library</a>
    <a href="latihan_en.php" class="menu-btn"><i class="fa-solid fa-seedling"></i> The Barn (Gym)</a>
    <a href="kompetisi_en.php" class="menu-btn btn-kompetisi"><i class="fa-solid fa-fire-flame-curved"></i> Festival Duel</a>
    <a href="obrolan_en.php" class="menu-btn"><i class="fa-solid fa-mug-hot"></i> Campfire Chat</a>
</div>

<div class="shelf-container">
    <div class="shelf">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $icons = ['fa-bookmark', 'fa-feather-pointed', 'fa-map'];
            static $std_count = 0;
            while($row = $result->fetch_assoc()): 
                $randomIcon = $icons[$std_count % count($icons)];
                $std_count++;
            ?>
                <div class="book" 
                     data-title="<?php echo htmlspecialchars($row['title']); ?>"
                     data-content="<?php echo htmlspecialchars($row['content']); ?>"
                     data-q1="<?php echo htmlspecialchars($row['question_1']); ?>"
                     data-q2="<?php echo htmlspecialchars($row['question_2']); ?>"
                     data-a1="<?php echo htmlspecialchars($row['answer_1']); ?>"
                     data-a2="<?php echo htmlspecialchars($row['answer_2']); ?>"
                     onclick="openReader(this)">
                     
                    <div class="book-header">
                        <div class="leaf-icon"><i class="fa-solid <?php echo $randomIcon; ?>"></i></div>
                        <div class="genre-info">
                            <div class="book-category">Path: <?php echo htmlspecialchars($row['category'] ?: 'General'); ?></div>
                        </div>
                    </div>
                    
                    <div class="book-body">
                        <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; font-weight: 800; color: var(--text-muted); font-size: 1.2rem; background: rgba(255, 255, 255, 0.6); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(10px);">The trails are empty. Nothing found.</p>
        <?php endif; ?>
    </div>
</div>

<div class="pagination-container">
    <?php if ($pages > 1): ?>
        <?php for($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&cat=<?php echo $category; ?>&search=<?php echo urlencode($searchTerm); ?>" 
               class="page-link <?php echo ($page == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<div class="content-box">
    <h2 class="box-title">Village Notice Board</h2>
    <form id="feedbackForm">
        <textarea id="feedbackText" name="feedback" required placeholder="Pin your thoughts or messages here..."></textarea>
        <div style="text-align:center;">
            <button type="submit" class="btn-rustic" style="margin-top:20px;"><i class="fa-solid fa-thumbtack"></i> Pin It</button>
        </div>
    </form>
</div>

<div class="content-box" style="margin-top: -20px; box-shadow: none; border: none; background: transparent; padding: 20px; backdrop-filter: none;">
    <h2 class="box-title" style="font-size: 1.5rem; text-align: left; margin-bottom: 10px; background: transparent;">Village Whispers</h2>
    <div id="feedbackListContainer">
        <p style="color: var(--text-muted); font-style: italic; font-weight: 600;">Listening to the wind...</p>
    </div>
</div>

<div id="reader">
    <div class="reader-nav">
        <button onclick="closeReader()" class="btn-rustic" style="padding: 10px 20px; font-size: 0.85rem; background: rgba(255,255,255,0.5); color: var(--accent-blue); border: 1px solid rgba(0,0,0,0.1); box-shadow: none;"><i class="fa-solid fa-arrow-left"></i> Back</button>
        <h2 id="readingTitle"></h2>
        <button id="audioBtn" onclick="toggleAudio()" class="btn-rustic" style="background: var(--accent-red); color: #FFF; padding: 10px 20px; font-size: 0.85rem;"><i class="fa-solid fa-volume-high"></i> Read Aloud</button>
    </div>
    <div class="reader-paper">
        <div class="novel-text" id="novelBody"></div>
        
        <div class="essay-box">
            <p id="displayQ1" style="font-weight:800; margin-bottom:10px; color: var(--accent-blue);"></p>
            <textarea id="ans1" placeholder="Write your answer..." style="height: 100px;"></textarea>
            <div id="corr1" class="correction"></div>
            
            <p id="displayQ2" style="font-weight:800; margin: 30px 0 10px 0; color: var(--accent-blue);"></p>
            <textarea id="ans2" placeholder="Write your answer..." style="height: 100px;"></textarea>
            <div id="corr2" class="correction"></div>
            
            <button onclick="verifyAnswers()" class="btn-rustic" style="width:100%; margin-top:25px; background: var(--accent-blue);"><i class="fa-solid fa-check-double"></i> Submit Answers</button>
        </div>
    </div>
</div>

<div id="duelModal">
    <div class="modal-content">
        <h2 style="margin:0; font-family:'Lora', serif; font-size: 2.2rem; color: var(--accent-red); font-weight: 800;">Friendly Match!</h2>
        <p id="duelText" style="font-weight: 700; margin:20px 0; font-size:1.1rem; color: var(--text-main);"></p>
        <div style="display:flex; gap:15px; justify-content: center; flex-wrap: wrap;">
            <button onclick="acceptTheDuel()" class="btn-rustic" style="flex: 1; background: var(--accent-blue);">Accept Match</button>
            <button onclick="rejectTheDuel()" class="btn-rustic" style="flex: 1; background: rgba(0,0,0,0.05); color: var(--text-main); border: 1px solid rgba(0,0,0,0.1); box-shadow: none;">Decline</button>
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
            if (btnHref && (btnHref.includes('cat=') || btnHref === 'english.php')) {
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
        // Hanya cegah reload jika link mengarah ke english.php (bukan halaman lain)
        if (href && (href.startsWith('english.php') || href.startsWith('?'))) {
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
        const cat = searchForm.querySelector('input[name="cat"]').value;
        const search = searchForm.querySelector('input[name="search"]').value;
        const targetUrl = `english.php?cat=${encodeURIComponent(cat)}&search=${encodeURIComponent(search)}`;
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

        fetch('english.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(() => {
            textInput.value = ''; 
            loadFeedback(); 
        }).catch(err => alert("Failed to submit feedback."));
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
            document.getElementById('duelText').innerHTML = `<b>${data.challenger_name}</b> is inviting you to a word challenge!`;
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
    document.getElementById('displayQ1').innerText = "Question 1: " + el.getAttribute('data-q1');
    document.getElementById('displayQ2').innerText = "Question 2: " + el.getAttribute('data-q2');
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
        corrDiv.style.background = "rgba(74, 222, 128, 0.2)"; corrDiv.style.color = "#166534"; corrDiv.style.border = "1px solid #22C55E";
        corrDiv.innerHTML = "<i class='fa-solid fa-leaf'></i> <b>CORRECT.</b><br>Key: " + correctText;
    } else {
        corrDiv.style.background = "rgba(248, 113, 113, 0.2)"; corrDiv.style.color = "#991B1B"; corrDiv.style.border = "1px solid #EF4444";
        corrDiv.innerHTML = "<i class='fa-solid fa-circle-xmark'></i> <b>INCORRECT.</b><br>Key: " + correctText;
    }
}

function toggleAudio() {
    if (isSpeaking) { synth.cancel(); isSpeaking = false; } 
    else {
        const ut = new SpeechSynthesisUtterance(document.getElementById('novelBody').innerText);
        ut.lang = 'en-US'; 
        ut.onstart = () => isSpeaking = true; ut.onend = () => isSpeaking = false;
        synth.speak(ut);
    }
}

window.onload = loadFeedback;
</script>
</body>
</html>