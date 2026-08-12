<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Koneksi Database
$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 2. OTOMATIS BUAT TABEL JIKA BELUM ADA
$conn->query("CREATE TABLE IF NOT EXISTS stories_jp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    category VARCHAR(50),
    question_1 VARCHAR(255),
    question_2 VARCHAR(255),
    answer_1 VARCHAR(255),
    answer_2 VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS kompetisi_jp_scores (
    user_id INT(11) PRIMARY KEY,
    score INT(11) DEFAULT 0
)");

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
        echo "<script>alert('Pesan berhasil ditempel di Ema-Kake (Papan Kayu)!');</script>";
    }
}

// 3. Logika Menu (Filter Kategori Absolut)
$category = isset($_GET['cat']) ? $_GET['cat'] : '';

// 4. Logika Pagination & Search
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

// MENGAMBIL DATA DARI TABEL BAHASA JEPANG (stories_jp)
$sql = "SELECT * FROM stories_jp $whereClause ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);

// Hitung total data
$totalResultCount = $conn->query("SELECT COUNT(*) AS total FROM stories_jp $whereClause");
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
        $icons = ['fa-scroll', 'fa-pen-nib', 'fa-om'];
        $count = 0;
        while($row = $result->fetch_assoc()) {
            $randomIcon = $icons[$count % count($icons)];
            $count++;
            ?>
            <div class="book" 
                 data-title="<?php echo htmlspecialchars($row['title'] ?? ''); ?>"
                 data-content="<?php echo htmlspecialchars($row['content'] ?? ''); ?>"
                 data-q1="<?php echo htmlspecialchars($row['question_1'] ?? ''); ?>"
                 data-q2="<?php echo htmlspecialchars($row['question_2'] ?? ''); ?>"
                 data-a1="<?php echo htmlspecialchars($row['answer_1'] ?? ''); ?>"
                 data-a2="<?php echo htmlspecialchars($row['answer_2'] ?? ''); ?>"
                 onclick="openReader(this)">
                 
                <div class="book-header">
                    <div class="leaf-icon"><i class="fa-solid <?php echo $randomIcon; ?>"></i></div>
                    <div class="genre-info">
                        <div class="book-category">Tipe: <?php echo htmlspecialchars($row['category'] ?? 'Umum'); ?></div>
                    </div>
                </div>
                
                <div class="book-body">
                    <div class="book-title"><?php echo htmlspecialchars($row['title'] ?? ''); ?></div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p style="text-align: center; grid-column: 1/-1; font-weight: 800; color: var(--jp-ink); font-size: 1.2rem; background: rgba(255,255,255,0.6); padding: 20px; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(10px);">The archives are currently empty.</p>';
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nihongo | Sakura Pavilion</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;700;900&family=Shippori+Mincho:wght@400;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    html {
        background-color: #FDFBF7; /* Menghilangkan flash putih saat pertama kali dibuka */
    }

    :root { 
        /* Tema Modern Sakura Glass */
        --jp-red: #D32F2F;       /* Kurenai (Merah Tradisional Terang) */
        --jp-ink: #1C1C1C;       /* Sumi (Hitam Tinta) */
        --jp-paper: #FDFBF7;     /* Washi modern */
        --jp-gold: #D4AF37;      /* Kintsugi Gold */
        
        /* Palet UI Glassmorphism */
        --glass-bg: rgba(253, 251, 247, 0.55);      /* Kaca putih bertekstur washi */
        --glass-blur: blur(16px);
        --glass-border: rgba(255, 255, 255, 0.7);   /* Pinggiran kaca */
        
        --glass-dark-bg: rgba(28, 28, 28, 0.75);    /* Kaca gelap untuk elemen kontras */
        
        --shadow-soft: 0 8px 32px rgba(28, 28, 28, 0.15);
        --shadow-strong: 0 12px 40px rgba(211, 47, 47, 0.2);
        
        --radius-lg: 16px;       /* Sudut melengkung modern */
        --radius-md: 8px;
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Nunito', sans-serif; 
        color: var(--jp-ink); 
        margin: 0; 
        overflow-x: hidden;
        min-height: 100vh;

        background-color: var(--jp-paper); 

        /* BACKGROUND JEPANG DIPERTAHANKAN */
        background: url('https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
        background-size: cover;
    }

    /* Overlay pencerah agar teks kontras dan background sedikit nge-blend */
    body::before {
        content: '';
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(135deg, rgba(253, 251, 247, 0.3) 0%, rgba(253, 251, 247, 0.1) 100%);
        z-index: -1;
    }

    /* --- GLASS PANEL UTILITY --- */
    .glass-panel {
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-soft);
    }

    /* --- TOP NAVIGATION (Gaya Kaca Gelap Mewah) --- */
    .user-nav { 
        display: grid;
        grid-template-columns: 1fr auto 1fr; 
        padding: 10px 40px; 
        background: var(--glass-dark-bg); 
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        align-items: center; position: sticky; 
        top: 0; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.4); 
        border-bottom: 2px solid rgba(255,255,255,0.1); 
    }

    .lobby-action a { 
        color: var(--jp-paper); text-decoration: none; font-weight: 800; font-size: 0.95rem; 
        transition: var(--transition); display: flex; align-items: center; gap: 8px; 
        padding: 8px 18px; border-radius: var(--radius-md); background: rgba(255,255,255,0.05); 
        border: 1px solid rgba(255,255,255,0.15); 
        width: fit-content;
    }
    .lobby-action a:hover { background: var(--jp-red); color: white; border-color: var(--jp-red); }

    /* --- CONTAINER BENDERA GANTUNG --- */
    .nav-flags {
        grid-column: 2; 
        display: flex; gap: 20px; align-items: flex-start;
        position: relative;
        padding: 15px 15px 0 15px; 
    }
    
    .nav-flags::before {
        content: ''; position: absolute; top: 2px; left: 0; right: 0;
        height: 6px; background: rgba(255,255,255,0.1); 
        border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; z-index: 1;
    }
    
    .flag-wrapper { position: relative; transform-origin: top center; animation: swing-flag 3s ease-in-out infinite alternate; filter: drop-shadow(3px 3px 5px rgba(0,0,0,0.5)); z-index: 2; }
    .flag-wrapper:nth-child(odd) { animation-duration: 3.3s; animation-direction: alternate-reverse; }
    .flag-wrapper:nth-child(even) { animation-duration: 2.9s; }

    .flag-wrapper::before {
        content: ''; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
        width: 8px; height: 12px; background: transparent; border: 2px solid rgba(255,255,255,0.3); border-bottom: none; border-radius: 4px 4px 0 0; z-index: -1; 
    }

    .flag-icon {
        width: 38px; height: 55px; object-fit: cover;
        clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%);
        border: 1px solid rgba(255,255,255,0.2); border-top: 2px solid rgba(255,255,255,0.3); 
        display: block; transition: 0.3s; cursor: pointer;
    }

    .flag-wrapper:hover { z-index: 10; animation-play-state: paused; filter: drop-shadow(0px 5px 10px rgba(211, 47, 47, 0.8)); }

    @keyframes swing-flag { 0% { transform: rotate(4deg); } 100% { transform: rotate(-4deg); } }

    /* TOMBOL USER */
    .user-actions { grid-column: 3; justify-self: end; display: flex; gap: 15px; align-items: center; }
    .user-link { color: var(--jp-paper); text-decoration: none; font-weight: 800; font-size: 0.9rem; transition: var(--transition); display: flex; align-items: center; gap: 8px; padding: 8px 15px; border-radius: var(--radius-md); background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15);}
    .user-link:hover { color: var(--jp-ink); background: var(--jp-gold); border-color: var(--jp-gold); }
    .logout-btn { background: rgba(211, 47, 47, 0.15); color: #FFCDD2; border-color: rgba(211, 47, 47, 0.5); }
    .logout-btn:hover { background: var(--jp-red); color: white; border-color: var(--jp-red); }

    /* --- HEADER (Modern Glass Torii) --- */
    header { padding: 60px 15px 40px; text-align: center; position: relative; }
    
    .village-badge { 
        display: inline-block; background: var(--glass-dark-bg); color: var(--jp-gold); 
        font-family: 'Shippori Mincho', serif; font-size: 1.1rem; font-weight: 800; 
        padding: 8px 20px; letter-spacing: 4px; margin-bottom: 15px; 
        border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 30px; backdrop-filter: var(--glass-blur);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .logo-text { 
        font-family: 'Shippori Mincho', serif; font-size: 4.5rem; color: var(--jp-ink); 
        margin: 0; font-weight: 800; letter-spacing: 2px; text-shadow: 2px 2px 15px rgba(255, 255, 255, 0.9); 
    }
    .logo-subtitle { 
        font-family: 'Nunito', sans-serif; color: var(--jp-ink); font-weight: 800; font-size: 1.3rem; margin-top: 10px; text-shadow: 1px 1px 5px rgba(255,255,255,0.9);
    }

    /* MENU UTAMA (Kaca Terang) */
    .main-menu { display: flex; justify-content: center; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
    .menu-btn { 
        padding: 12px 25px; color: var(--jp-ink); 
        text-decoration: none; font-weight: 800; font-size: 0.95rem; 
        display: flex; align-items: center; gap: 8px; border-radius: 30px; 
        transition: var(--transition); border: 1px solid var(--glass-border); 
        background: var(--glass-bg); backdrop-filter: var(--glass-blur);
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .menu-btn.active, .menu-btn:hover { 
        background: var(--jp-ink); color: var(--jp-paper); border-color: var(--jp-ink);
        transform: translateY(-3px); box-shadow: 0 8px 20px rgba(28, 28, 28, 0.3); 
    }
    .menu-btn.btn-kompetisi { border-color: rgba(211, 47, 47, 0.5); color: var(--jp-red); background: rgba(211, 47, 47, 0.1); }
    .menu-btn.btn-kompetisi:hover { background: var(--jp-red); color: white; border-color: var(--jp-red); box-shadow: 0 8px 20px rgba(211, 47, 47, 0.4); }

    /* SEARCH BAR (Glass Kapsul) */
    .search-wrapper { width: 90%; max-width: 700px; margin: 40px auto 40px; }
    .search-wrapper form { 
        display: flex; border-radius: 30px; 
        transition: var(--transition); border: 1px solid var(--glass-border); 
        background: var(--glass-bg); backdrop-filter: var(--glass-blur);
        box-shadow: var(--shadow-soft); overflow: hidden;
    }
    .search-wrapper form:focus-within { border-color: var(--jp-red); box-shadow: 0 8px 25px rgba(211, 47, 47, 0.2); }
    .search-input { 
        flex: 1; padding: 18px 30px; border: none; outline: none; font-size: 1.05rem; 
        font-family: 'Nunito', sans-serif; font-weight: 800; color: var(--jp-ink); background: transparent; 
    }
    .search-input::placeholder { color: #555; font-weight: 600; }
    .search-btn { 
        padding: 0 35px; border: none; background: rgba(255,255,255,0.4); cursor: pointer; 
        color: var(--jp-ink); transition: var(--transition); font-size: 1.2rem; border-left: 1px solid var(--glass-border);
    }
    .search-btn:hover { background: var(--jp-red); color: white; border-color: var(--jp-red); }

    /* KARTU BUKU (Modern Glass Scroll) */
    .shelf-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 20px; }
    
    .shelf { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 35px; 
        transition: opacity 0.25s ease-in-out;
    }
    .shelf.loading {
        opacity: 0.3;
        pointer-events: none;
    }
    
    .book { 
        cursor: pointer; transition: var(--transition); 
        display: flex; flex-direction: column; position: relative; border-radius: var(--radius-lg); 
        background: var(--glass-bg); backdrop-filter: var(--glass-blur);
        box-shadow: var(--shadow-soft); border: 1px solid var(--glass-border); 
        overflow: hidden; min-height: 250px;
    }
    
    /* Aksen strip merah di kiri */
    .book::before {
        content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 6px; background: var(--jp-red); z-index: 1;
    }
    
    .book:nth-child(3n+1)::before { background: var(--jp-red); }
    .book:nth-child(3n+2)::before { background: var(--jp-ink); }
    .book:nth-child(3n+3)::before { background: var(--jp-gold); }

    .book-header { 
        padding: 20px 20px 10px 25px; display: flex; 
        justify-content: space-between; align-items: flex-start; 
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .leaf-icon { 
        width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; 
        font-size: 1.1rem; color: var(--jp-red); background: rgba(255,255,255,0.8); border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .book:nth-child(3n+1) .leaf-icon { color: var(--jp-red); }
    .book:nth-child(3n+2) .leaf-icon { color: var(--jp-ink); }
    .book:nth-child(3n+3) .leaf-icon { color: #B7950B; }

    .genre-info { display: flex; flex-direction: column; align-items: flex-end; }
    .book-category { font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #555;}
    
    .book-body { 
        padding: 30px 20px 30px 25px; flex: 1; display: flex; align-items: center; justify-content: center; 
    }
    .book-title { 
        font-family: 'Noto Serif JP', serif; font-size: 1.5rem; font-weight: 900; 
        text-align: center; color: var(--jp-ink); line-height: 1.5;
    }
    .book:hover { transform: translateY(-8px) scale(1.02); box-shadow: var(--shadow-strong); border-color: rgba(255,255,255,1); background: rgba(255,255,255,0.85); }

    /* PAGINATION */
    .pagination-container { display: flex; justify-content: center; gap: 10px; margin: 60px 10px; flex-wrap: wrap; }
    .page-link { 
        width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; 
        color: var(--jp-ink); font-weight: 800; text-decoration: none; 
        border-radius: var(--radius-md); font-size: 1.1rem; transition: var(--transition); border: 1px solid var(--glass-border); 
        background: var(--glass-bg); backdrop-filter: var(--glass-blur); box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .page-link.active, .page-link:hover { background: var(--jp-red); color: white; border-color: var(--jp-red); transform: translateY(-3px); box-shadow: 0 6px 15px rgba(211, 47, 47, 0.4); }

    /* KOTAK KONTEN UMUM (Papan Ema Modern) */
    .content-box { 
        width: 95%; max-width: 800px; margin: 40px auto; padding: 45px; 
        border-radius: var(--radius-lg); 
        box-shadow: var(--shadow-soft); border: 1px solid var(--glass-border); 
        background: var(--glass-bg); backdrop-filter: var(--glass-blur);
        position: relative;
    }
    .box-title { 
        text-align:center; font-family:'Shippori Mincho', serif; color:var(--jp-ink); 
        margin-bottom:25px; font-size: 2rem; font-weight: 800; 
        text-shadow: 1px 1px 5px rgba(255,255,255,0.8);
    }

    /* TEXTAREA & BUTTONS */
    textarea { 
        width:100%; height:140px; padding:20px; border:2px dashed rgba(0,0,0,0.15); border-radius: var(--radius-md); 
        font-family:'Nunito', sans-serif; resize:none; color:var(--jp-ink); font-size:1.05rem; 
        background: rgba(255, 255, 255, 0.4); transition: var(--transition); 
        font-weight: 700;
    }
    textarea:focus { outline: none; border-color: var(--jp-red); background: rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(211, 47, 47, 0.15); }
    textarea::placeholder { color: #777; font-weight: 600;}
    
    .btn-rustic { 
        background: var(--jp-red); color: white; border: none; 
        padding: 14px 35px; font-weight: 800; cursor: pointer; transition: var(--transition); 
        font-size: 1rem; border-radius: 30px; display: inline-block; text-transform: uppercase; letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(211, 47, 47, 0.4);
    }
    .btn-rustic:hover { background: #b71c1c; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(211, 47, 47, 0.5); }

    .feedback-item { 
        padding: 20px; border-radius: var(--radius-md); border-left: 4px solid var(--jp-gold);
        margin-bottom: 20px; transition: var(--transition);
        border: 1px solid rgba(255,255,255,0.8); border-left-width: 4px;
        font-weight: 700; background: rgba(255,255,255,0.6); box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    /* --- READER MODE (Kaca Bersih ala Shoji Modern) --- */
    #reader { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(243, 241, 235, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); z-index: 9999; overflow-y: auto; }
    .reader-nav { position: sticky; top: 0; background: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(255,255,255,0.9); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; z-index: 100; box-shadow: 0 4px 15px rgba(0,0,0,0.05); backdrop-filter: blur(10px); }
    .reader-nav h2 { font-family: 'Shippori Mincho', serif; font-weight: 800; margin: 0; font-size: 1.4rem; color: var(--jp-ink); }
    .reader-paper { 
        width: 90%; max-width: 800px; margin: 50px auto; padding: 60px 60px; 
        color: var(--jp-ink); box-shadow: 0 20px 50px rgba(28, 28, 28, 0.15); border: 1px solid rgba(255,255,255,0.9); 
        position: relative; background: rgba(255,255,255,0.85); border-radius: var(--radius-lg);
    }
    
    .novel-text { font-family: 'Noto Serif JP', serif; font-size: 1.25rem; line-height: 2.2; text-align: justify; margin-bottom: 50px; color: #111; white-space: pre-line; font-weight: 500;}
    
    .essay-box { background: rgba(243, 241, 235, 0.6); padding: 40px; border: 1px solid rgba(0,0,0,0.1); margin-top: 50px; position: relative; border-radius: var(--radius-md); }
    .essay-box::before { content: 'TEST / 試験'; position: absolute; top: -14px; left: 30px; background: var(--jp-red); color: white; padding: 4px 15px; font-weight: 800; font-size: 0.85rem; letter-spacing: 2px; border-radius: 20px; }
    .correction { padding: 15px 20px; border-radius: var(--radius-md); margin: 15px 0; font-weight: 800; display: none; background: rgba(255,255,255,0.8); }

    /* --- MODAL DUEL --- */
    #duelModal { 
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(15px); z-index: 999999; justify-content: center; align-items: center; 
    }
    .modal-content { 
        padding: 50px; text-align: center; border-radius: var(--radius-lg);
        color: var(--jp-ink); max-width: 450px; border: 2px solid var(--jp-red);
        background: rgba(255, 255, 255, 0.95); box-shadow: 0 20px 50px rgba(211, 47, 47, 0.2);
    }

    @media (max-width: 768px) {
        .user-nav { grid-template-columns: 1fr; gap: 15px; padding: 15px; }
        .nav-flags { grid-column: 1; justify-content: center; padding-top: 10px; }
        .user-actions { grid-column: 1; justify-self: center; gap: 15px; }
        .logo-text { font-size: 3rem; }
        .search-wrapper form { flex-direction: column; border-radius: 15px; }
        .search-btn { padding: 15px; border-left: none; border-top: 1px solid rgba(0,0,0,0.1); }
        .reader-paper { padding: 40px 20px; }
        .shelf { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>

<nav class="user-nav">
    <div class="lobby-action">
        <a href="index.php"><i class="fa-solid fa-torii-gate"></i> Main Lobby</a>
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
        <a href="user_profile.php" class="user-link"><i class="fa-solid fa-address-card"></i> Profile</a>
        <a href="logout.php" class="user-link logout-btn"><i class="fa-solid fa-person-walking-arrow-right"></i> Keluar</a>
    </div>
</nav>

<header>
    <div class="village-badge">SAKURA PAVILION</div>
    <h1 class="logo-text">Japanese Faculty</h1>
    <p class="logo-subtitle">Master the art of language, one stroke at a time.</p>
</header>

<div class="search-wrapper">
    <form action="" method="GET" class="glass-panel">
        <input type="hidden" name="cat" value="<?php echo $category; ?>">
        <input type="text" name="search" class="search-input" placeholder="Search the scrolls..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
</div>

<div class="main-menu">
    <a href="japan.php" class="menu-btn <?php echo ($category == '') ? 'active' : ''; ?>">All Scrolls</a>
    <a href="japan.php?cat=pendek" class="menu-btn <?php echo ($category == 'pendek') ? 'active' : ''; ?>">Short Texts</a>
    <a href="japan.php?cat=panjang" class="menu-btn <?php echo ($category == 'panjang') ? 'active' : ''; ?>">Long Texts</a>
    <a href="materi_jp.php" class="menu-btn"><i class="fa-solid fa-book-journal-whills"></i> The Archive</a>
    <a href="latihan_jp.php" class="menu-btn"><i class="fa-solid fa-yin-yang"></i> The Dojo</a>
    <a href="kompetisi_jp.php" class="menu-btn btn-kompetisi"><i class="fa-solid fa-khanda"></i> Samurai Duel</a>
    <a href="obrolan_jp.php" class="menu-btn"><i class="fa-solid fa-mug-hot"></i> Tea House Chat</a>
</div>

<div class="shelf-container">
    <div class="shelf">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $icons = ['fa-scroll', 'fa-pen-nib', 'fa-om'];
            $count = 0;
            while($row = $result->fetch_assoc()): 
                $randomIcon = $icons[$count % count($icons)];
                $count++;
            ?>
                <div class="book" 
                     data-title="<?php echo htmlspecialchars($row['title'] ?? ''); ?>"
                     data-content="<?php echo htmlspecialchars($row['content'] ?? ''); ?>"
                     data-q1="<?php echo htmlspecialchars($row['question_1'] ?? ''); ?>"
                     data-q2="<?php echo htmlspecialchars($row['question_2'] ?? ''); ?>"
                     data-a1="<?php echo htmlspecialchars($row['answer_1'] ?? ''); ?>"
                     data-a2="<?php echo htmlspecialchars($row['answer_2'] ?? ''); ?>"
                     onclick="openReader(this)">
                     
                    <div class="book-header">
                        <div class="leaf-icon"><i class="fa-solid <?php echo $randomIcon; ?>"></i></div>
                        <div class="genre-info">
                            <div class="book-category">Tipe: <?php echo htmlspecialchars($row['category'] ?? 'Umum'); ?></div>
                        </div>
                    </div>
                    
                    <div class="book-body">
                        <div class="book-title"><?php echo htmlspecialchars($row['title'] ?? ''); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; font-weight: 800; color: var(--jp-ink); font-size: 1.2rem; background: rgba(255,255,255,0.6); padding: 20px; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(10px);">The archives are currently empty.</p>
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
    <h2 class="box-title">Ema Board (Prayer Wood)</h2>
    <form id="feedbackForm">
        <textarea id="feedbackText" name="feedback" required placeholder="Tinggalkan doa atau pesan belajarmu di sini..."></textarea>
        <div style="text-align:center;">
            <button type="submit" class="btn-rustic" style="margin-top:20px;"><i class="fa-solid fa-paper-plane"></i> Gantung Pesan</button>
        </div>
    </form>
</div>

<div class="content-box" style="margin-top: -20px; box-shadow: none; border: none; background: transparent; padding: 20px; backdrop-filter: none;">
    <h2 class="box-title" style="font-size: 1.5rem; text-align: left; margin-bottom: 10px; border: none; margin-left: 0; transform: none; text-shadow: none;">Dojo Whispers</h2>
    <div id="feedbackListContainer">
        <p style="color: var(--jp-ink); font-weight: 700; font-style: italic;">Memuat pesan...</p>
    </div>
</div>

<div id="reader">
    <div class="reader-nav">
        <button onclick="closeReader()" class="btn-rustic" style="padding: 10px 20px; font-size: 0.85rem; background: rgba(255,255,255,0.5); color: var(--jp-ink); border: 1px solid rgba(0,0,0,0.1); box-shadow: none;"><i class="fa-solid fa-arrow-left"></i> Kembali</button>
        <h2 id="readingTitle"></h2>
        <button id="audioBtn" onclick="toggleAudio()" class="btn-rustic" style="background: var(--jp-ink); padding: 10px 20px; font-size: 0.85rem;"><i class="fa-solid fa-volume-high"></i> Baca Teks (JP)</button>
    </div>
    <div class="reader-paper">
        <div class="novel-text" id="novelBody"></div>
        
        <div class="essay-box">
            <p id="displayQ1" style="font-family:'Shippori Mincho', serif; font-weight:800; font-size:1.1rem; margin-bottom:10px; color: var(--jp-red);"></p>
            <textarea id="ans1" placeholder="Tulis jawabanmu..." style="height: 100px; background: rgba(255,255,255,0.7);"></textarea>
            <div id="corr1" class="correction"></div>
            
            <p id="displayQ2" style="font-family:'Shippori Mincho', serif; font-weight:800; font-size:1.1rem; margin: 30px 0 10px 0; color: var(--jp-red);"></p>
            <textarea id="ans2" placeholder="Tulis jawabanmu..." style="height: 100px; background: rgba(255,255,255,0.7);"></textarea>
            <div id="corr2" class="correction"></div>
            
            <button onclick="verifyAnswers()" class="btn-rustic" style="width:100%; margin-top:25px; background: var(--jp-red);"><i class="fa-solid fa-check"></i> Serahkan Ujian</button>
        </div>
    </div>
</div>

<div id="duelModal">
    <div class="modal-content">
        <h2 style="margin:0; font-family:'Shippori Mincho', serif; font-size: 2.2rem; color:var(--jp-red); font-weight: 800;">Samurai Duel!</h2>
        <p id="duelText" style="font-weight: 700; margin:20px 0; font-size:1.1rem; color: var(--jp-ink);"></p>
        <div style="display:flex; gap:15px; justify-content: center; flex-wrap: wrap;">
            <button onclick="acceptTheDuel()" class="btn-rustic" style="flex: 1; background: var(--jp-red);">Terima Tantangan</button>
            <button onclick="rejectTheDuel()" class="btn-rustic" style="flex: 1; background: rgba(0,0,0,0.05); color: var(--jp-ink); border: 1px solid rgba(0,0,0,0.1); box-shadow: none;">Tolak Halus</button>
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
            if (btnHref && (btnHref.includes('cat=') || btnHref === 'japan.php')) {
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
        if (href && (href.startsWith('japan.php') || href.startsWith('?'))) {
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
        const targetUrl = `japan.php?cat=${encodeURIComponent(cat)}&search=${encodeURIComponent(search)}`;
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

        fetch('japan.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(() => {
            textInput.value = ''; 
            loadFeedback(); 
        }).catch(err => alert("Gagal mengirim pesan."));
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
            document.getElementById('duelText').innerHTML = `<b>${data.challenger_name}</b> menantangmu dalam Duel Samurai!`;
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
    document.getElementById('displayQ1').innerText = "Q1: " + el.getAttribute('data-q1');
    document.getElementById('displayQ2').innerText = "Q2: " + el.getAttribute('data-q2');
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
        corrDiv.innerHTML = "<i class='fa-solid fa-circle-check'></i> <b>Tepat Sekali! (正解)</b><br>Kunci: " + correctText;
    } else {
        corrDiv.style.background = "rgba(248, 113, 113, 0.2)"; corrDiv.style.color = "#991B1B"; corrDiv.style.border = "1px solid #EF4444";
        corrDiv.innerHTML = "<i class='fa-solid fa-circle-xmark'></i> <b>Kurang Tepat. (不正解)</b><br>Kunci: " + correctText;
    }
}

function toggleAudio() {
    if (isSpeaking) { synth.cancel(); isSpeaking = false; } 
    else {
        const ut = new SpeechSynthesisUtterance(document.getElementById('novelBody').innerText);
        ut.lang = 'ja-JP';
        ut.onstart = () => isSpeaking = true; ut.onend = () => isSpeaking = false;
        synth.speak(ut);
    }
}

window.onload = loadFeedback;
</script>
</body>
</html>