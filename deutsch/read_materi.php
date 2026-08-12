<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. KONEKSI DATABASE
$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// 2. AMBIL ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM materi_en WHERE id = $id";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("<div style='text-align:center; padding:100px; font-family:sans-serif;'><h2>Opps! Materi tidak ditemukan.</h2><a href='materi_en.php'>Kembali ke Village</a></div>");
}

$lvl = !empty($data['level']) ? $data['level'] : 'A1';
$colors = ['A1' => '#3498db', 'A2' => '#f1c40f', 'B1' => '#e67e22', 'C1' => '#e74c3c'];
$accent = $colors[$lvl] ?? '#5a825f';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['judul']); ?> - Elroy English</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5a825f;
            --primary-light: #f0f4f0;
            --accent: <?php echo $accent; ?>;
            --bg: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --radius: 20px;
        }

        * { box-sizing: border-box; }
        body { 
            background: var(--bg); 
            margin: 0; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Navbar Modern */
        .nav-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            padding: 18px 0;
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        .nav-container { max-width: 1000px; margin: 0 auto; padding: 0 25px; display: flex; justify-content: space-between; align-items: center; }
        .back-btn { 
            text-decoration: none; color: var(--text-dark); font-weight: 700; 
            font-size: 0.9rem; display: flex; align-items: center; gap: 10px; 
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .back-btn:hover { color: var(--primary); transform: translateX(-5px); }

        /* Main Layout */
        .main-content { max-width: 900px; margin: 50px auto; padding: 0 20px; }
        
        /* Hero Section */
        .hero { text-align: center; margin-bottom: 50px; }
        .lvl-badge { 
            background: var(--accent); color: white; padding: 6px 20px; 
            border-radius: 100px; font-weight: 800; font-size: 11px; 
            letter-spacing: 1.5px; text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            font-family: 'Playfair Display', serif; font-size: clamp(32px, 6vw, 48px); 
            margin: 20px 0 10px; color: #0f172a; font-weight: 900;
        }
        .subtitle { color: var(--text-muted); font-size: 1.1rem; }

        /* Content Card */
        .materi-card { 
            background: var(--white); border-radius: var(--radius); padding: 50px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04); 
            border: 1px solid rgba(0,0,0,0.03); 
        }

        /* Typography & Custom Elements */
        h2 { 
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
            color: var(--text-dark); margin-top: 50px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 12px; font-size: 1.6rem;
        }
        h2 i { color: var(--primary); font-size: 1.3rem; }
        
        p { margin-bottom: 20px; font-size: 1.1rem; color: #475569; }

        /* --- NEW PARSER STYLES --- */
        .section-title {
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.3rem;
            color: var(--primary); margin: 40px 0 20px; display: flex; align-items: center; gap: 10px;
            border-bottom: 2px dashed #e2e8f0; padding-bottom: 10px;
        }
        
        .vocab-grid { display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px; }
        .vocab-row {
            display: flex; justify-content: space-between; align-items: center;
            background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 25px;
            border-radius: 12px; transition: 0.3s;
        }
        .vocab-row:hover { transform: translateX(5px); border-color: var(--primary); background: var(--white); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .vocab-en { font-weight: 800; color: var(--text-dark); font-size: 1.1rem; }
        .vocab-id { color: var(--text-muted); font-style: italic; font-weight: 600; }

        .grammar-highlight {
            background: var(--primary-light); padding: 20px 25px; border-radius: 12px;
            border-left: 5px solid var(--primary); margin: 20px 0; color: #1e293b; font-weight: 600;
        }
        
        .bullet-item {
            background: white; padding: 12px 20px; border-radius: 10px; margin-bottom: 8px;
            border: 1px solid #f1f5f9; display: flex; gap: 15px; align-items: center;
        }
        .bullet-icon { color: var(--accent); font-weight: bold; width: 20px; text-align: center; }

        /* Alphabet & Numbers */
        .alpha-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 16px; margin: 30px 0; }
        .alpha-box { background: #ffffff; border: 2px solid #f1f5f9; border-radius: 18px; padding: 22px 10px; text-align: center; transition: all 0.4s ease; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .alpha-box:hover { transform: translateY(-8px); border-color: var(--primary); background: var(--primary-light); box-shadow: 0 15px 25px rgba(90,130,95,0.1); }
        .alpha-letter { display: block; font-size: 34px; font-weight: 800; color: #0f172a; margin-bottom: 5px; }
        .alpha-sound { font-size: 13px; color: var(--primary); font-weight: 800; background: white; padding: 4px 10px; border-radius: 8px; border: 1px solid #e2e8f0; }
        
        .num-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin: 25px 0; }
        .num-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 20px; border-radius: 16px; display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .num-card:hover { border-color: var(--accent); background: white; transform: scale(1.02); }
        .num-circle { background: var(--accent); color: white; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-weight: 800; font-size: 15px; }
        .num-text { font-weight: 700; color: var(--text-dark); font-size: 1rem; }

        .example-box { background: #fcfcfc; padding: 30px; border-radius: 20px; border-left: 6px solid var(--primary); margin: 30px 0; box-shadow: inset 0 0 20px rgba(0,0,0,0.01); }

        /* Footer Finish */
        .finish-area { margin-top: 60px; text-align: center; padding-bottom: 100px; }
        .celebration-card { 
            background: var(--primary); color: white; padding: 50px 30px; 
            border-radius: 35px; background-image: linear-gradient(135deg, #5a825f 0%, #3d5a41 100%);
            box-shadow: 0 20px 40px rgba(90,130,95,0.3);
        }
        .finish-btn { 
            background: var(--white); color: var(--primary); padding: 18px 45px; 
            border-radius: 100px; text-decoration: none; font-weight: 800; 
            display: inline-block; margin-top: 25px; transition: 0.4s;
            text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;
        }
        .finish-btn:hover { transform: scale(1.05); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }

        @media (max-width: 600px) { 
            .materi-card { padding: 30px 20px; } 
            .alpha-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            h1 { font-size: 28px; }
            .num-grid { grid-template-columns: 1fr; }
            .vocab-row { flex-direction: column; align-items: flex-start; gap: 5px; }
        }
    </style>
</head>
<body>

<nav class="nav-header">
    <div class="nav-container">
        <a href="materi_en.php" class="back-btn"><i class="fas fa-arrow-left"></i> BACK TO VILLAGE</a>
        <span style="font-weight: 800; color: var(--primary); letter-spacing: -0.5px; font-size: 1.2rem;">ELROY<span style="color:var(--text-dark)">ENGLISH</span></span>
    </div>
</nav>

<main class="main-content">
    <div class="hero">
        <span class="lvl-badge"><?php echo $lvl; ?> Level</span>
        <h1><?php echo htmlspecialchars($data['judul']); ?></h1>
        <p class="subtitle">Complete this lesson to unlock the next challenge!</p>
    </div>

    <div class="materi-card">
        <div class="content-body">
            <?php 
            $text = $data['isi_materi'];

            // LOGIKA 1: Untuk Materi Alphabet & Numbers gabungan
            if (strpos($text, '(ei)') !== false || stripos($data['judul'], 'Alphabet') !== false) {
                
                // 1. ALPHABET SECTION
                echo "<h2><i class='fas fa-spell-check'></i> The Alphabet</h2>";
                echo "<p>Tap on the letters to hear how they are pronounced.</p>";
                echo '<div class="alpha-grid">';
                preg_match_all('/([A-Z])\s*\((.*?)\)/', $text, $matches);
                for($i=0; $i < count($matches[0]); $i++) {
                    echo "<div class='alpha-box'><span class='alpha-letter'>{$matches[1][$i]}</span><span class='alpha-sound'>{$matches[2][$i]}</span></div>";
                }
                echo '</div>';

                // 2. NUMBERS SECTION
                echo "<h2><i class='fas fa-sort-numeric-down'></i> Essential Numbers</h2>";
                echo '<div class="num-grid">';
                $lines = explode("\n", $text);
                foreach($lines as $line) {
                    // Cari baris yang punya format angka titik dua contoh "1 : One"
                    if (strpos($line, ':') !== false && stripos($line, 'Example') === false) {
                        $p = explode(':', $line, 2);
                        $angka = trim($p[0]);
                        // Pastikan teks sebelum titik dua adalah angka
                        if(is_numeric($angka)) {
                            echo "<div class='num-card'>
                                    <div class='num-circle'>".$angka."</div>
                                    <div class='num-text'>".trim($p[1])."</div>
                                  </div>";
                        }
                    }
                }
                echo '</div>';

                // 3. EXAMPLE SECTION
                if(stripos($text, 'Example:') !== false) {
                    $example_part = preg_split('/Example:/i', $text);
                    if(isset($example_part[1])) {
                        echo "<h2><i class='fas fa-lightbulb'></i> Practice Example</h2>";
                        echo "<div class='example-box'>";
                        echo nl2br(trim($example_part[1]));
                        echo "</div>";
                    }
                }

            } 
            // LOGIKA 2: Auto-Parser Keren untuk Materi Baru (A1 dll)
            else {
                $lines = explode("\n", $text);
                $inVocabGrid = false;

                foreach($lines as $line) {
                    $line = trim($line);
                    if(empty($line)) continue;

                    // Deteksi Header (==== JUDUL ====)
                    if (preg_match('/====\s*(.*?)\s*====/', $line, $matches)) {
                        if ($inVocabGrid) { echo '</div>'; $inVocabGrid = false; }
                        echo "<div class='section-title'><i class='fas fa-cube'></i> " . htmlspecialchars($matches[1]) . "</div>";
                    }
                    // Deteksi Kata = Arti (Flashcard Row)
                    elseif (strpos($line, '=') !== false && !stripos($line, 'contoh') && !stripos($line, 'rumus')) {
                        if (!$inVocabGrid) { echo '<div class="vocab-grid">'; $inVocabGrid = true; }
                        $parts = explode('=', $line, 2);
                        $en = htmlspecialchars(trim($parts[0]));
                        $id = htmlspecialchars(trim($parts[1]));
                        echo "<div class='vocab-row'>
                                <span class='vocab-en'>$en</span>
                                <span class='vocab-id'>$id</span>
                              </div>";
                    }
                    // Deteksi Rumus / Aturan
                    elseif (stripos($line, 'Rumus:') !== false || stripos($line, 'Aturan') !== false) {
                        if ($inVocabGrid) { echo '</div>'; $inVocabGrid = false; }
                        echo "<div class='grammar-highlight'>" . htmlspecialchars($line) . "</div>";
                    }
                    // Deteksi Contoh Kalimat / List (+, -, ?)
                    elseif (preg_match('/^[\+\-\?]\s/', $line) || stripos($line, 'Contoh:') !== false || stripos($line, 'Contoh Kalimat') !== false) {
                        if ($inVocabGrid) { echo '</div>'; $inVocabGrid = false; }
                        
                        if (preg_match('/^([\+\-\?])\s(.*)/', $line, $matches)) {
                            $icon = $matches[1] == '+' ? 'fa-check text-green' : ($matches[1] == '-' ? 'fa-times text-red' : 'fa-question text-blue');
                            echo "<div class='bullet-item'><span class='bullet-icon'><i class='fas $icon'></i> {$matches[1]}</span> " . htmlspecialchars($matches[2]) . "</div>";
                        } else {
                            echo "<p style='font-weight: 800; margin-top: 20px; color: var(--primary);'>".htmlspecialchars($line)."</p>";
                        }
                    }
                    // Teks biasa
                    else {
                        if ($inVocabGrid) { echo '</div>'; $inVocabGrid = false; }
                        echo "<p>".htmlspecialchars($line)."</p>";
                    }
                }
                if ($inVocabGrid) { echo '</div>'; } 
            }
            ?>
        </div>
    </div>

    <div class="finish-area">
        <div class="celebration-card">
            <i class="fas fa-trophy fa-3x" style="margin-bottom: 20px; color: #f1c40f;"></i>
            <h2 style="color: white; border: none; margin: 0; display: block; text-align: center; font-size: 2rem;">Lesson Complete!</h2>
            <p style="color: rgba(255,255,255,0.9); margin-top: 10px;">You've just leveled up your English skills. Ready for the next one?</p>
            <a href="materi_en.php" class="finish-btn">Complete & Continue <i class="fas fa-arrow-right" style="margin-left: 10px;"></i></a>
        </div>
    </div>
</main>

</body>
</html>