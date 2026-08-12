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

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$level_filter = isset($_GET['level']) ? $conn->real_escape_string($_GET['level']) : '';
$where_sql = ($level_filter !== '') ? "WHERE level = '$level_filter'" : "";
$materi_query = $conn->query("SELECT * FROM materi $where_sql ORDER BY level ASC");
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lernmaterial | DeutschAktiv</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --de-black: #0f172a;
            --de-red: #dc2626;
            --de-red-hover: #b91c1c;
            --de-gold: #f59e0b;
            --de-gold-light: #fef3c7;
            --bg-light: #f8fafc;
            --surface: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 20px 30px -10px rgba(220, 38, 38, 0.15);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Modern Sticky Header */
        .nav-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 12px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .logo {
            font-family: 'UnifrakturMaguntia', serif;
            font-size: 2rem;
            color: var(--de-black);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-dot {
            width: 8px;
            height: 8px;
            background: var(--de-red);
            border-radius: 50%;
            display: inline-block;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-item {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 50px;
            transition: var(--transition);
            letter-spacing: 0.5px;
        }

        .nav-item:hover {
            color: var(--de-black);
            background: #f1f5f9;
        }

        .nav-item.active {
            background: var(--de-red);
            color: #ffffff;
        }

        .nav-item.active:hover {
            background: var(--de-red-hover);
        }

        .nav-item.logout {
            color: var(--de-red);
            background: #fef2f2;
        }

        .nav-item.logout:hover {
            background: #fee2e2;
        }

        /* Container Layout */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 0 60px;
            flex: 1;
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 36px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--de-gold-light);
            color: #b45309;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-section h1 {
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            font-weight: 800;
            color: var(--de-black);
            line-height: 1.15;
            letter-spacing: -1px;
        }

        .hero-section h1 span {
            color: var(--de-red);
            position: relative;
        }

        .hero-section p {
            color: var(--text-muted);
            font-size: 1rem;
            margin-top: 12px;
            line-height: 1.6;
        }

        /* Filter Segment Controller */
        .filter-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .filter-tabs {
            display: inline-flex;
            background: #e2e8f0;
            padding: 4px;
            border-radius: 50px;
            gap: 4px;
            overflow-x: auto;
            max-width: 100%;
            scrollbar-width: none;
        }

        .filter-tabs::-webkit-scrollbar {
            display: none;
        }

        .tab-btn {
            padding: 8px 20px;
            border-radius: 50px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            white-space: nowrap;
            transition: var(--transition);
        }

        .tab-btn:hover {
            color: var(--de-black);
        }

        .tab-btn.active {
            background: var(--surface);
            color: var(--de-black);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* Grid & Card UI */
        .materi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .materi-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 28px;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .materi-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(220, 38, 38, 0.2);
        }

        /* Top Accent Bar */
        .materi-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--de-black) 33.3%, var(--de-red) 33.3%, var(--de-red) 66.6%, var(--de-gold) 66.6%);
            opacity: 0.8;
            transition: var(--transition);
        }

        .materi-card:hover::before {
            height: 6px;
            opacity: 1;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .icon-box {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-lg);
            background: #f1f5f9;
            color: var(--de-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: var(--transition);
        }

        .materi-card:hover .icon-box {
            background: #fef2f2;
            color: var(--de-red);
        }

        .level-badge {
            background: #f8fafc;
            color: var(--de-black);
            border: 1px solid var(--border-color);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .materi-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--de-black);
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .materi-card p {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 24px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-learn {
            background: var(--de-black);
            color: #ffffff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-learn i {
            transition: transform 0.2s ease;
        }

        .btn-learn:hover {
            background: var(--de-red);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .btn-learn:hover i {
            transform: translateX(4px);
        }

        /* Empty State Styling */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px dashed var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .empty-state h4 {
            font-size: 1.2rem;
            color: var(--de-black);
            margin-bottom: 6px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .nav-header {
                padding: 12px 16px;
                flex-direction: column;
                gap: 12px;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .nav-item {
                padding: 6px 12px;
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .container {
                width: 92%;
                padding: 24px 0 40px;
            }

            .materi-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sticky Navigation Bar -->
    <header class="nav-header">
        <a href="home.php" class="logo">
            DeutschAktiv <span class="logo-dot"></span>
        </a>
        <nav class="nav-links">
            <a href="deutsch.php" class="nav-item">BIBLIOTHEK</a>
            <a href="materi.php" class="nav-item active">MATERI</a>
            <a href="user_profile.php" class="nav-item">PROFIL</a>
            <a href="logout.php" class="nav-item logout">LOGOUT</a>
        </nav>
    </header>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-badge">
                <i class="fa-solid fa-graduation-cap"></i> Deutsch Kurse
            </div>
            <h1>Lern<span>material.</span></h1>
            <p>Pilih tingkat kemahiran Anda dan tingkatkan kosakata serta tata bahasa Jerman Anda secara sistematis.</p>
        </section>

        <!-- Segmented Filter Tabs -->
        <div class="filter-wrapper">
            <div class="filter-tabs">
                <a href="materi.php" class="tab-btn <?= $level_filter == '' ? 'active' : '' ?>">ALLE LEVELS</a>
                <a href="materi.php?level=A1" class="tab-btn <?= $level_filter == 'A1' ? 'active' : '' ?>">LEVEL A1</a>
                <a href="materi.php?level=A2" class="tab-btn <?= $level_filter == 'A2' ? 'active' : '' ?>">LEVEL A2</a>
                <a href="materi.php?level=B1" class="tab-btn <?= $level_filter == 'B1' ? 'active' : '' ?>">LEVEL B1</a>
            </div>
        </div>

        <!-- Materi Cards Grid -->
        <div class="materi-grid">
            <?php if ($materi_query && $materi_query->num_rows > 0): ?>
                <?php while($m = $materi_query->fetch_assoc()): ?>
                    <article class="materi-card">
                        <div class="card-header">
                            <div class="icon-box">
                                <i class="<?= htmlspecialchars($m['icon'] ?? 'fa-solid fa-book-open') ?>"></i>
                            </div>
                            <span class="level-badge"><?= htmlspecialchars($m['level']) ?></span>
                        </div>
                        
                        <h3><?= htmlspecialchars($m['judul']) ?></h3>
                        <p><?= htmlspecialchars($m['deskripsi']) ?></p>
                        
                        <a href="isi_materi.php?id=<?= $m['id'] ?>" class="btn-learn">
                            JETZT LERNEN <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <h4>Belum Ada Materi</h4>
                    <p>Materi untuk tingkatan ini belum tersedia saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
<?php $conn->close(); ?>