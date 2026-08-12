<?php
session_start();

// --- KONEKSI DATABASE (tetap dipanggil untuk cek login) ---
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

// --- DATA HURUF (Pakai Array agar cepat dan tidak perlu query DB) ---
$hiragana = [
    'a'=>'あ', 'i'=>'い', 'u'=>'う', 'e'=>'え', 'o'=>'お',
    'ka'=>'か', 'ki'=>'き', 'ku'=>'く', 'ke'=>'け', 'ko'=>'こ',
    'sa'=>'さ', 'shi'=>'し', 'su'=>'す', 'se'=>'せ', 'so'=>'そ',
    'ta'=>'た', 'chi'=>'ち', 'tsu'=>'つ', 'te'=>'て', 'to'=>'と',
    'na'=>'な', 'ni'=>'に', 'nu'=>'ぬ', 'ne'=>'ね', 'no'=>'の',
    'ha'=>'は', 'hi'=>'ひ', 'fu'=>'ふ', 'he'=>'へ', 'ho'=>'ほ',
    'ma'=>'ま', 'mi'=>'み', 'mu'=>'む', 'me'=>'め', 'mo'=>'も',
    'ya'=>'や', 'yu'=>'ゆ', 'yo'=>'よ',
    'ra'=>'ら', 'ri'=>'り', 'ru'=>'る', 're'=>'れ', 'ro'=>'ろ',
    'wa'=>'わ', 'wo'=>'を', 'n'=>'ん'
];

$katakana = [
    'a'=>'ア', 'i'=>'イ', 'u'=>'ウ', 'e'=>'エ', 'o'=>'オ',
    'ka'=>'カ', 'ki'=>'キ', 'ku'=>'ク', 'ke'=>'ケ', 'ko'=>'コ',
    'sa'=>'サ', 'shi'=>'シ', 'su'=>'ス', 'se'=>'セ', 'so'=>'ソ',
    'ta'=>'タ', 'chi'=>'チ', 'tsu'=>'ツ', 'te'=>'テ', 'to'=>'ト',
    'na'=>'ナ', 'ni'=>'ニ', 'nu'=>'ヌ', 'ne'=>'ネ', 'no'=>'ノ',
    'ha'=>'ハ', 'hi'=>'ヒ', 'fu'=>'フ', 'he'=>'ヘ', 'ho'=>'ホ',
    'ma'=>'マ', 'mi'=>'ミ', 'mu'=>'ム', 'me'=>'メ', 'mo'=>'モ',
    'ya'=>'ヤ', 'yu'=>'ユ', 'yo'=>'ヨ',
    'ra'=>'ラ', 'ri'=>'リ', 'ru'=>'ル', 're'=>'レ', 'ro'=>'ロ',
    'wa'=>'ワ', 'wo'=>'ヲ', 'n'=>'ン'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belajar Huruf Jepang - Eduventure</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* TEMA JEPANG & SAKURA */
        body { 
            font-family: 'Poppins', sans-serif; 
            background-image: url('https://images.unsplash.com/photo-1522383225653-ed111181a951?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0; 
            padding: 40px 20px; 
            color: #2d3436;
        }

        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            text-align: center;
        }

        h1 { color: #d63031; margin-bottom: 5px; font-weight: 700; }
        .subtitle { color: #636e72; margin-bottom: 30px; font-size: 0.95rem; }

        /* TABS STYLING */
        .tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .tab-btn {
            background: rgba(255, 255, 255, 0.5);
            border: 2px solid #ff9a9e;
            padding: 10px 30px;
            border-radius: 50px;
            font-family: inherit;
            font-weight: 600;
            font-size: 1rem;
            color: #d63031;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab-btn.active, .tab-btn:hover {
            background: #ff9a9e;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 154, 158, 0.4);
        }

        /* GRID HURUF - NO TABLES! */
        .char-grid {
            display: grid;
            /* Layout otomatis 5 kolom menyesuaikan lebar */
            grid-template-columns: repeat(5, 1fr); 
            gap: 15px;
            display: none; /* Disembunyikan dulu via CSS */
            animation: fadeIn 0.5s ease;
        }
        .char-grid.active {
            display: grid;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* STYLING CARD HURUF */
        .char-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 20px 10px;
            box-shadow: 0 4px 10px rgba(255, 183, 197, 0.2);
            transition: all 0.3s ease;
            border-bottom: 3px solid #ff9a9e;
            cursor: pointer;
        }
        .char-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 20px rgba(214, 48, 49, 0.15);
            background: #fff;
            border-bottom-color: #d63031;
        }
        .jp-char {
            font-size: 2.5rem;
            font-weight: 400;
            color: #2d3436;
            margin-bottom: 5px;
        }
        .romaji {
            font-size: 0.9rem;
            font-weight: 600;
            color: #e17055;
            text-transform: uppercase;
        }
        
        /* Tombol Kembali */
        .btn-back {
            display: inline-block;
            margin-top: 30px;
            text-decoration: none;
            color: #636e72;
            font-weight: 600;
            transition: color 0.3s;
        }
        .btn-back:hover { color: #d63031; }

        /* Responsive untuk HP */
        @media (max-width: 600px) {
            .char-grid { grid-template-columns: repeat(3, 1fr); }
            .tabs { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Hiragana & Katakana 🌸</h1>
    <p class="subtitle">Kenali bentuk dan cara baca huruf dasar bahasa Jepang.</p>

    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('hiragana', this)">Hiragana (ひらがな)</button>
        <button class="tab-btn" onclick="showTab('katakana', this)">Katakana (カタカナ)</button>
    </div>

    <div id="hiragana" class="char-grid active">
        <?php foreach($hiragana as $romaji => $char): ?>
            <div class="char-card">
                <div class="jp-char"><?= $char ?></div>
                <div class="romaji"><?= $romaji ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="katakana" class="char-grid">
        <?php foreach($katakana as $romaji => $char): ?>
            <div class="char-card">
                <div class="jp-char"><?= $char ?></div>
                <div class="romaji"><?= $romaji ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="materi_jp.php" class="btn-back">← Kembali ke Menu Materi</a>
</div>

<script>
    // Script sederhana untuk fungsi Tab (Ganti antara Hiragana & Katakana)
    function showTab(tabId, btn) {
        // Hilangkan class active dari semua grid & button
        document.querySelectorAll('.char-grid').forEach(grid => grid.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(button => button.classList.remove('active'));
        
        // Tambahkan class active ke tab & button yang diklik
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }
</script>

</body>
</html>