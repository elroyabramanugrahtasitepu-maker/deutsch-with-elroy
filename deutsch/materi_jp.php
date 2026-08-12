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

// --- 2. HITUNG JUMLAH MATERI PER LEVEL ---
$sql_count = "SELECT level, COUNT(id) as total FROM materi_jp GROUP BY level";
$result_count = $conn->query($sql_count);

$jumlah_materi = [
    'N5' => 0,
    'N4' => 0,
    'N3' => 0,
    'N2' => 0,
    'N1' => 0
];

if ($result_count && $result_count->num_rows > 0) {
    while($row = $result_count->fetch_assoc()) {
        $lvl = strtoupper($row['level']);
        if (array_key_exists($lvl, $jumlah_materi)) {
            $jumlah_materi[$lvl] = $row['total'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JLPT Learning Path | Nihongo</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --jp-red: #dc2626;
            --jp-red-hover: #b91c1c;
            --jp-dark: #0f172a;
            --jp-rose: #fff1f2;
            --bg-light: #fafaf9;
            --surface: #ffffff;
            --border-color: #f1f5f9;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --radius-lg: 20px;
            --radius-xl: 28px;
            --shadow-sm: 0 4px 20px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 20px 35px -10px rgba(220, 38, 38, 0.12);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans JP', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(254, 226, 226, 0.4) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(254, 243, 199, 0.4) 0%, transparent 40%);
            background-attachment: fixed;
        }

        /* Glassmorphism Container */
        .container {
            width: 100%;
            max-width: 960px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 48px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
        }

        /* Header Navigation & Title */
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: var(--surface);
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }

        .btn-back:hover {
            background: var(--jp-rose);
            color: var(--jp-red);
            border-color: #fecdd3;
            transform: translateX(-4px);
        }

        .jp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--jp-rose);
            color: var(--jp-red);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .header-content {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 44px;
        }

        .header-content h1 {
            font-family: 'Noto Sans JP', 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 900;
            color: var(--jp-dark);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .header-content h1 span {
            color: var(--jp-red);
        }

        .header-content p {
            color: var(--text-muted);
            font-size: 0.975rem;
            line-height: 1.6;
        }

        /* Grid Level Layout */
        .level-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        /* Level Card Component */
        .level-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 28px 24px;
            text-decoration: none;
            color: var(--text-main);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 1;
        }

        .level-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(220, 38, 38, 0.2);
        }

        /* Top Accent Indicator */
        .level-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-color, var(--jp-red));
            transition: var(--transition);
        }

        .level-card:hover::before {
            height: 6px;
        }

        /* Background Kanji watermark */
        .kanji-bg {
            position: absolute;
            bottom: -15px;
            right: -10px;
            font-family: 'Noto Sans JP', sans-serif;
            font-size: 5.5rem;
            font-weight: 900;
            color: var(--text-main);
            opacity: 0.03;
            transition: var(--transition);
            pointer-events: none;
            z-index: -1;
        }

        .level-card:hover .kanji-bg {
            opacity: 0.08;
            transform: scale(1.1) rotate(-8deg);
            color: var(--accent-color, var(--jp-red));
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .level-title {
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--jp-dark);
            line-height: 1;
            letter-spacing: -1px;
        }

        .card-arrow {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--bg-light);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .level-card:hover .card-arrow {
            background: var(--accent-color, var(--jp-red));
            color: #ffffff;
            transform: translateX(4px);
        }

        .level-subtitle {
            font-size: 0.925rem;
            font-weight: 700;
            color: var(--jp-dark);
            margin-bottom: 4px;
        }

        .level-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .level-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
        }

        .level-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-light);
            color: var(--text-muted);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.775rem;
            font-weight: 700;
            border: 1px solid var(--border-color);
        }

        .level-card:hover .level-count {
            background: #ffffff;
            color: var(--jp-dark);
        }

        /* Specific Level Accent Color Themes */
        .card-n5 { --accent-color: #10b981; }
        .card-n4 { --accent-color: #3b82f6; }
        .card-n3 { --accent-color: #f59e0b; }
        .card-n2 { --accent-color: #8b5cf6; }
        .card-n1 { --accent-color: #ef4444; }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .container {
                padding: 28px 20px;
                border-radius: var(--radius-lg);
            }

            .header-nav {
                margin-bottom: 20px;
            }

            .header-content h1 {
                font-size: 1.85rem;
            }

            .level-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header Navigation -->
    <div class="header-nav">
        <a href="japan.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Menu Utama
        </a>
        <div class="jp-badge">
            <i class="fa-solid fa-torii-gate"></i> JLPT Path
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-content">
        <h1>学習<span>材料</span></h1>
        <p>Pilih tingkatan kemampuan JLPT Anda untuk membuka modul tata bahasa, kosakata, dan latihan soal yang terstruktur.</p>
    </div>

    <!-- Level Cards Grid -->
    <div class="level-grid">
        <?php
        // Metadata level dengan karakter Kanji watermark & deskripsi
        $urutan_level = [
            'N5' => [
                'kanji' => '五', 
                'label' => 'Pemula (Beginner)', 
                'desc' => 'Dasar kosakata & tata bahasa sederhana',
                'class' => 'card-n5'
            ],
            'N4' => [
                'kanji' => '四', 
                'label' => 'Dasar (Elementary)', 
                'desc' => 'Percakapan dasar & ekspresi harian',
                'class' => 'card-n4'
            ],
            'N3' => [
                'kanji' => '三', 
                'label' => 'Menengah (Intermediate)', 
                'desc' => 'Pemahaman konteks situasi sehari-hari',
                'class' => 'card-n3'
            ],
            'N2' => [
                'kanji' => '二', 
                'label' => 'Menengah Atas (Upper)', 
                'desc' => 'Artikel, berita, dan wacana umum',
                'class' => 'card-n2'
            ],
            'N1' => [
                'kanji' => '一', 
                'label' => 'Mahir (Advanced)', 
                'desc' => 'Tingkat mahir bisnis & akademis',
                'class' => 'card-n1'
            ]
        ];

        foreach ($urutan_level as $lvl => $data) {
            $total_materi = $jumlah_materi[$lvl];
            ?>
            <a href="lihat_materi_jp.php?level=<?= $lvl ?>" class="level-card <?= $data['class'] ?>">
                <div class="kanji-bg"><?= $data['kanji'] ?></div>
                
                <div class="card-top">
                    <span class="level-title"><?= $lvl ?></span>
                    <div class="card-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>

                <div class="level-subtitle"><?= $data['label'] ?></div>
                <div class="level-desc"><?= $data['desc'] ?></div>

                <div class="level-footer">
                    <span class="level-count">
                        <i class="fa-regular fa-folder-open"></i> <?= $total_materi ?> Modul
                    </span>
                </div>
            </a>
            <?php
        }
        ?>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>