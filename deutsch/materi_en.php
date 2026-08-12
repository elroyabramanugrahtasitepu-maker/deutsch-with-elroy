<?php
// 1. TAMPILKAN ERROR UNTUK DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. KONEKSI DATABASE

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// 3. LOGIKA FILTER, SEARCH & PAGINATION
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$level_filter = isset($_GET['level']) ? $_GET['level'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// --- PENGATURAN HALAMAN (PAGINATION) ---
$limit = 12; // Jumlah maksimal card per halaman
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Susun kondisi WHERE dasar
$where_clause = "1=1";
if ($filter !== 'all') {
    $where_clause .= " AND kategori = '" . mysqli_real_escape_string($conn, $filter) . "'";
}
if ($level_filter !== 'all') {
    $where_clause .= " AND level = '" . mysqli_real_escape_string($conn, $level_filter) . "'";
}
if (!empty($search)) {
    $where_clause .= " AND judul LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
}

// Hitung total data untuk mengetahui jumlah halaman
$total_sql = "SELECT COUNT(*) as total FROM materi_en WHERE " . $where_clause;
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_pages = ceil(($total_row['total'] ?? 0) / $limit);

// Query utama dengan LIMIT dan OFFSET
$sql = "SELECT * FROM materi_en WHERE " . $where_clause . " ORDER BY level ASC, id ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// Menyimpan parameter URL saat ini agar filter tidak hilang saat pindah halaman
$url_params = "";
if ($filter !== 'all') $url_params .= "&filter=" . urlencode($filter);
if ($level_filter !== 'all') $url_params .= "&level=" . urlencode($level_filter);
if (!empty($search)) $url_params .= "&search=" . urlencode($search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Path - Oakwood Settlement</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4a6b51;
            --primary-hover: #3b5741;
            --accent: #8c7355;
            --accent-light: #f3efe9;
            --bg: #f8f6f2;
            --surface: #ffffff;
            --text-main: #2d3748;
            --text-muted: #718096;
            --border-color: #e2dacd;
            
            /* Level Badge Palette */
            --lvl-a1: #2563eb;
            --lvl-a2: #d97706;
            --lvl-b1: #dc2626;
            --lvl-default: #4a6b51;
            
            --radius-md: 12px;
            --radius-lg: 20px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-hover: 0 12px 28px rgba(74, 107, 81, 0.12);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            padding: 30px 20px 60px;
            background-image: radial-gradient(#e2dacd 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .container {
            max-width: 1140px;
            margin: 0 auto;
        }

        /* Top Navigation Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            background: var(--surface);
            color: var(--accent);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .btn-back:hover {
            background: var(--accent);
            color: #ffffff;
            border-color: var(--accent);
            transform: translateX(-3px);
        }

        .brand-title {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-title i {
            font-size: 1.1rem;
            color: var(--accent);
        }

        /* Controls Section */
        .controls-card {
            background: var(--surface);
            padding: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin-bottom: 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .search-form {
            display: flex;
            width: 100%;
            max-width: 520px;
            background: var(--bg);
            border-radius: 50px;
            padding: 4px 6px 4px 20px;
            border: 2px solid var(--border-color);
            transition: var(--transition);
        }

        .search-form:focus-within {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(74, 107, 81, 0.1);
        }

        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            font-family: inherit;
            font-size: 0.925rem;
            color: var(--text-main);
        }

        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .search-btn:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }

        /* Filter Pills */
        .filter-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pill {
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            background: var(--bg);
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.8rem;
            transition: var(--transition);
        }

        .pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .pill.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(74, 107, 81, 0.25);
        }

        /* Grid System */
        .story-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 24px;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
            position: relative;
            box-shadow: var(--shadow-sm);
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(74, 107, 81, 0.3);
        }

        /* Level Color Line & Badge */
        .level-strip {
            height: 5px;
            width: 100%;
            background: var(--lvl-default);
        }
        .bg-A1 { background-color: var(--lvl-a1); }
        .bg-A2 { background-color: var(--lvl-a2); }
        .bg-B1 { background-color: var(--lvl-b1); }

        .badge {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 50px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 28px 24px 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .card-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: var(--accent-light);
            color: var(--primary);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            transition: var(--transition);
        }

        .card:hover .card-icon {
            background: var(--primary);
            color: #ffffff;
            transform: scale(1.1);
        }

        .card-title {
            font-family: 'Lora', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-meta {
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: auto;
            background: var(--bg);
            padding: 4px 12px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
        }

        /* Empty State */
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px dashed var(--border-color);
        }

        .empty i {
            font-size: 2.8rem;
            color: var(--accent);
            margin-bottom: 16px;
        }

        .empty h4 {
            font-family: 'Lora', serif;
            font-size: 1.25rem;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .empty p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 48px;
        }

        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border-radius: var(--radius-md);
            background: var(--surface);
            border: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-main);
            font-weight: 700;
            font-size: 0.875rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .page-btn:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            pointer-events: none;
        }

        /* Responsive Breakpoints */
        @media (max-width: 640px) {
            body {
                padding: 20px 14px 40px;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .controls-card {
                padding: 18px 14px;
            }

            .story-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header Top Bar -->
    <header class="top-bar">
        <a href="english.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to English Village
        </a>
        <div class="brand-title">
            <i class="fas fa-tree"></i> Oakwood Settlement
        </div>
    </header>

    <!-- Search & Filter Controls -->
    <section class="controls-card">
        <form action="" method="GET" class="search-form">
            <?php if ($level_filter !== 'all'): ?>
                <input type="hidden" name="level" value="<?php echo htmlspecialchars($level_filter); ?>">
            <?php endif; ?>
            <?php if ($filter !== 'all'): ?>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <?php endif; ?>
            <input type="text" name="search" class="search-input" placeholder="Search lessons by title..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn" aria-label="Search"><i class="fas fa-search"></i></button>
        </form>

        <div class="filter-group">
            <a href="?level=all<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="pill <?php echo ($level_filter == 'all') ? 'active' : ''; ?>">All Levels</a>
            <a href="?level=A1<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="pill <?php echo ($level_filter == 'A1') ? 'active' : ''; ?>">Beginner (A1)</a>
            <a href="?level=A2<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="pill <?php echo ($level_filter == 'A2') ? 'active' : ''; ?>">Elementary (A2)</a>
            <a href="?level=B1<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="pill <?php echo ($level_filter == 'B1') ? 'active' : ''; ?>">Intermediate (B1)</a>
        </div>
    </section>

    <!-- Lesson Cards Grid -->
    <main class="story-grid">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $lvl = !empty($row['level']) ? strtoupper($row['level']) : 'A1';
                $kat = $row['kategori'] ?? 'Lesson';
                
                // Dynamic Icons Selection
                $icon = "fa-book-open";
                if (stripos($kat, 'tense') !== false) $icon = "fa-clock";
                elseif (stripos($kat, 'grammar') !== false) $icon = "fa-pen-nib";
                elseif (stripos($kat, 'vocab') !== false) $icon = "fa-font";
                ?>
                <a href="read_materi.php?id=<?php echo (int)$row['id']; ?>" class="card">
                    <div class="level-strip bg-<?php echo htmlspecialchars($lvl); ?>"></div>
                    <span class="badge bg-<?php echo htmlspecialchars($lvl); ?>"><?php echo htmlspecialchars($lvl); ?></span>
                    
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <h3 class="card-title"><?php echo htmlspecialchars($row['judul']); ?></h3>
                        <div class="card-meta"><?php echo htmlspecialchars($kat); ?></div>
                    </div>
                </a>
                <?php
            }
        } else {
            ?>
            <div class="empty">
                <i class="fas fa-compass"></i>
                <h4>No Lessons Found</h4>
                <p>We couldn't find any lessons matching your criteria. Try adjusting your search or filter.</p>
            </div>
            <?php
        }
        ?>
    </main>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?><?php echo $url_params; ?>" class="page-btn">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $url_params; ?>" class="page-btn <?php echo ($page == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo ($page + 1); ?><?php echo $url_params; ?>" class="page-btn">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

</div>

</body>
</html>
<?php mysqli_close($conn); ?>