<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$conn = new mysqli($host, $user, $pass, $db);

$materi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = $conn->query("SELECT * FROM materi WHERE id = $materi_id");
$m = $query->fetch_assoc();

if (!$m) {
    echo "<script>alert('Materi tidak ditemukan!'); window.location.href='materi.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($m['judul']) ?> | DeutschAktiv</title>
     <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&family=Inter:wght@400;600;800&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --de-black: #1a1a1a;
            --de-red: #ae0001;
            --de-gold: #ffcf00;
            --bg-bright: #fdfdfd;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0; font-family: 'Inter', sans-serif;
            background-color: var(--bg-bright); color: #1a1a1a;
            background-image: url('https://www.transparenttextures.com/patterns/white-diamond.png');
            line-height: 1.6;
        }

        /* Progress Bar (Germany Colors) */
        .progress-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 6px; background: #eee; z-index: 2000;
        }
        .progress-bar { 
            height: 100%; background: linear-gradient(to right, var(--de-black), var(--de-red), var(--de-gold)); 
            width: 0%; transition: 0.1s; 
        }

        /* Navbar - Responsive Flexbox */
        .nav-sticky {
            position: sticky; top: 6px; background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px); padding: 12px 5%;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--de-red); z-index: 1000;
            flex-wrap: wrap; gap: 10px;
        }
        .logo-small { font-family: 'UnifrakturMaguntia', serif; font-size: 1.4rem; color: var(--de-black); text-decoration: none; }
        .back-link { text-decoration: none; color: var(--de-red); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; }

        /* Content Layout - Fluid Width */
        .content-wrapper { width: 92%; max-width: 850px; margin: 30px auto; }

        .materi-card {
            background: white; border-radius: 20px; padding: clamp(20px, 5vw, 50px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #eee;
            position: relative; overflow: hidden;
        }

        .materi-card::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 8px;
            background: linear-gradient(to right, var(--de-black) 33.3%, var(--de-red) 33.3%, var(--de-red) 66.6%, var(--de-gold) 66.6%);
        }

        .materi-header { text-align: center; margin-bottom: 40px; }
        .level-badge {
            background: var(--de-black); color: var(--de-gold); padding: 4px 12px;
            border-radius: 4px; font-weight: 800; font-size: 0.65rem; display: inline-block; margin-bottom: 15px;
        }
        .materi-header h1 { font-size: clamp(1.6rem, 6vw, 2.8rem); margin: 0; font-weight: 800; color: var(--de-black); line-height: 1.2; }

        /* Isi Materi - Typography Optimized */
        .materi-body { font-family: 'Lora', serif; font-size: clamp(1rem, 4vw, 1.2rem); line-height: 1.8; color: #333; }
        .materi-body h2, .materi-body h3 { 
            font-family: 'Inter', sans-serif; font-weight: 800; color: var(--de-red); 
            margin-top: 35px; border-bottom: 2px solid var(--de-gold); display: inline-block;
        }

        /* TABLE RESPONSIVE - Sangat Penting untuk HP */
        .table-container {
            width: 100%; overflow-x: auto; margin: 25px 0;
            border-radius: 10px; border: 1px solid #eee;
            -webkit-overflow-scrolling: touch;
        }
        .materi-body table {
            width: 100%; border-collapse: collapse; min-width: 500px;
        }
        .materi-body table th { background: var(--de-black); color: var(--de-gold); padding: 12px; text-align: left; font-size: 0.8rem; }
        .materi-body table td { background: #fff; padding: 12px; border-bottom: 1px solid #f0f0f0; }

        /* Button Finish */
        .btn-finish {
            display: block; width: 100%; padding: 20px; background: var(--de-red); color: white;
            border: none; border-radius: 12px; font-weight: 800; font-size: 1rem;
            cursor: pointer; margin-top: 40px; transition: 0.3s; text-transform: uppercase;
        }
        .btn-finish:hover { background: var(--de-black); transform: translateY(-3px); }

        /* Media Queries iPad/Tablet */
        @media (max-width: 1024px) {
            .content-wrapper { width: 90%; }
        }

        /* Media Queries HP */
        @media (max-width: 600px) {
            .nav-sticky { padding: 10px; justify-content: center; }
            .back-link { font-size: 0.65rem; }
            .materi-header { margin-bottom: 25px; }
            .materi-body { font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="progress-container"><div class="progress-bar" id="myBar"></div></div>

<div class="nav-sticky">
    <a href="index.php" class="logo-small">DeutschAktiv</a>
    <a href="materi.php" class="back-link"><i class="fa-solid fa-chevron-left"></i> ZURÜCK (KEMBALI)</a>
</div>

<div class="content-wrapper">
    <div class="materi-card">
        <header class="materi-header">
            <span class="level-badge">NIVEAU <?= htmlspecialchars($m['level']) ?></span>
            <h1><?= htmlspecialchars($m['judul']) ?></h1>
        </header>

        <article class="materi-body" id="materiContent">
            <?= $m['konten'] ?>
        </article>

        <button onclick="markAsDone()" class="btn-finish">
            Lektion abschließen (Selesai) <i class="fa-solid fa-flag-checkered"></i>
        </button>
    </div>
</div>

<script>
    // Update Progress Bar on Scroll
    window.onscroll = function() {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var scrolled = (winScroll / height) * 100;
        document.getElementById("myBar").style.width = scrolled + "%";
    };

    // Script Otomatis: Membungkus semua tabel dengan container scrollable
    document.addEventListener("DOMContentLoaded", function() {
        const content = document.getElementById('materiContent');
        const tables = content.getElementsByTagName('table');
        
        for (let i = tables.length - 1; i >= 0; i--) {
            const table = tables[i];
            const wrapper = document.createElement('div');
            wrapper.className = 'table-container';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    function markAsDone() {
        alert("Ausgezeichnet! Kamu telah menyelesaikan materi ini.");
        window.location.href = "materi.php";
    }
</script>

</body>
</html>