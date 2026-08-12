<?php
session_start();

// --- 1. KONEKSI DATABASE ---


if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];

// --- 2. AMBIL PROGRESS USER ---
$user_query = $conn->query("SELECT current_artikel_map FROM users WHERE id = $uid");
$user_data = $user_query->fetch_assoc();
$unlocked_map = $user_data['current_artikel_map'] ?? 1;

// --- 3. DATA KOTA & TRADISI JERMAN ---
// Format: Nama Kota, Tradisi/Tema, Emoji Besar (sebagai gambar)
$cities = [
    ["name" => "München",   "theme" => "Oktoberfest",  "icon" => "🥨"],
    ["name" => "Frankfurt", "theme" => "Finanzzentrum", "icon" => "💶"],
    ["name" => "Köln",      "theme" => "Kölner Karneval", "icon" => "🎭"],
    ["name" => "Hamburg",   "theme" => "Hafenstadt",   "icon" => "⚓"],
    ["name" => "Stuttgart", "theme" => "Autostadt",    "icon" => "🏎️"],
    ["name" => "Nürnberg",  "theme" => "Bratwurst",    "icon" => "🌭"],
    ["name" => "Dresden",   "theme" => "Weihnachtsmarkt", "icon" => "🎄"],
    ["name" => "Leipzig",   "theme" => "Musikstadt",   "icon" => "🎵"],
    ["name" => "Bremen",    "theme" => "Stadtmusikanten", "icon" => "🐈"],
    ["name" => "Berlin",    "theme" => "Hauptstadt",   "icon" => "🐻"]
];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deutschland Tour | Eduventure</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Tema Warna Alam Jerman */
            --sky-blue: #87CEEB;
            --grass-green: #a7f3d0;
            --path-color: #ffffff;
            --wood-dark: #5c2c16;
            --wood-light: #8b4513;
            
            /* Status Warna */
            --cleared: #4ade80; /* Hijau segar */
            --current: #fde047; /* Emas menyala */
            --locked: #d1d5db;  /* Abu-abu beku */
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0; 
            /* Gradasi Langit ke Rumput */
            background: linear-gradient(to bottom, #4facfe, #86efac, #dcfce7);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }

        /* --- ANIMASI LINGKUNGAN (BIAR RAME!) --- */
        .environment { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
        
        .cloud { position: absolute; font-size: 5rem; opacity: 0.8; animation: moveCloud linear infinite; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.1)); }
        .c1 { top: 10%; animation-duration: 40s; font-size: 6rem; }
        .c2 { top: 30%; animation-duration: 55s; font-size: 4rem; animation-delay: -20s; }
        .c3 { top: 60%; animation-duration: 45s; font-size: 5rem; animation-delay: -10s; }
        .c4 { top: 80%; animation-duration: 60s; font-size: 7rem; animation-delay: -30s; }

        .balloon { position: absolute; font-size: 4rem; top: 15%; animation: floatBalloon 30s ease-in-out infinite alternate; filter: drop-shadow(0 10px 10px rgba(0,0,0,0.2)); }
        .bird { position: absolute; font-size: 2rem; color: #333; animation: flyBird 25s linear infinite; }

        @keyframes moveCloud { from { transform: translateX(-20vw); } to { transform: translateX(110vw); } }
        @keyframes floatBalloon { 0% { transform: translate(-10vw, 0) rotate(-5deg); } 100% { transform: translate(90vw, -50px) rotate(5deg); } }
        @keyframes flyBird { 0% { transform: translate(110vw, 20vh) scaleX(-1); } 100% { transform: translate(-20vw, 10vh) scaleX(-1); } }

        /* --- HEADER PREMIUM --- */
        .map-header {
            width: 100%; height: 85px; 
            background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
            position: fixed; top: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            border-bottom: 5px solid #0284c7;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .header-content { width: 90%; max-width: 500px; display: flex; justify-content: space-between; align-items: center; }
        
        .back-btn { 
            width: 45px; height: 45px; border-radius: 12px; 
            background: #0284c7; display: flex; align-items: center; justify-content: center; 
            color: #fff; text-decoration: none; font-size: 1.2rem;
            box-shadow: 0 5px 0 #0369a1; transition: 0.1s; 
        }
        .back-btn:active { transform: translateY(5px); box-shadow: 0 0 0 #0369a1; }

        .header-title { text-align: center; }
        .header-title .subtitle { font-size: 0.75rem; color: #ef4444; letter-spacing: 3px; font-weight: 900; text-transform: uppercase; }
        .header-title .main-title { font-family: 'Fredoka One', cursive; font-size: 1.8rem; color: #1e293b; text-shadow: 2px 2px 0px rgba(255,255,255,1); line-height: 1.2; }

        /* --- CONTAINER & PATH --- */
        .map-container {
            margin-top: 140px; padding-bottom: 150px;
            position: relative; width: 100%; max-width: 500px; margin-inline: auto;
            display: flex; flex-direction: column-reverse; /* Level 1 di bawah, 10 di atas */
            z-index: 10;
        }

        /* Garis Rute Perjalanan */
        .map-path-svg {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1; pointer-events: none;
        }
        #pathLine { stroke: var(--path-color); stroke-dasharray: 15, 15; opacity: 0.8; filter: drop-shadow(0 5px 5px rgba(0,0,0,0.2)); }

        /* --- KOTA / LEVEL NODE --- */
        .level-row {
            width: 100%; height: 180px; position: relative;
            display: flex; justify-content: center; align-items: center; z-index: 2;
        }

        .island-wrapper { 
            position: relative; 
            display: flex; flex-direction: column; align-items: center;
        }
        
        /* Papan Nama Kota (Kayu) */
        .city-sign {
            background: var(--wood-light);
            border: 3px solid var(--wood-dark);
            border-radius: 8px;
            padding: 5px 15px;
            color: #fff;
            font-family: 'Fredoka One', cursive;
            font-size: 1.1rem;
            text-shadow: 1px 1px 0px #000;
            box-shadow: 0 5px 10px rgba(0,0,0,0.3);
            margin-bottom: 10px;
            z-index: 5;
            position: relative;
        }
        /* Tali gantungan papan */
        .city-sign::before, .city-sign::after {
            content: ''; position: absolute; bottom: -10px; width: 4px; height: 10px; background: var(--wood-dark);
        }
        .city-sign::before { left: 20%; }
        .city-sign::after { right: 20%; }

        .island {
            width: 90px; height: 90px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; position: relative;
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 5px solid #fff;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2), inset 0 -5px 10px rgba(0,0,0,0.1);
        }

        /* Emoji Tradisi Besar */
        .tradition-emoji { font-size: 2.8rem; filter: drop-shadow(0 4px 5px rgba(0,0,0,0.3)); }

        /* Label Tema Tradisi (Kecil di bawah) */
        .theme-label {
            margin-top: 8px;
            background: rgba(255,255,255,0.9);
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 900; color: #333;
            box-shadow: 0 3px 5px rgba(0,0,0,0.1);
        }

        /* --- STATUS WARNA & EFEK --- */
        .island.cleared { background: var(--cleared); }
        .island.locked { background: var(--locked); filter: grayscale(100%); opacity: 0.8; pointer-events: none; }
        
        .island.current { 
            background: var(--current); 
            border-color: #fff;
            animation: pulse-glow 1.5s infinite alternate; 
            transform: scale(1.1);
        }
        
        .island:active:not(.locked) { transform: scale(0.95); }

        /* Pin Lokasi Pemain (Player Marker) */
        .player-pin {
            position: absolute; top: -50px; left: 50%; transform: translateX(-50%);
            font-size: 3rem; color: #ef4444;
            filter: drop-shadow(0 10px 5px rgba(0,0,0,0.3));
            animation: bouncePin 1s infinite alternate cubic-bezier(0.5, 0.05, 1, 0.5);
            z-index: 20;
        }

        /* Nomor Level Badge */
        .level-num {
            position: absolute; bottom: 0px; right: -15px;
            background: #ef4444; width: 35px; height: 35px;
            border-radius: 50%; border: 3px solid white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; font-weight: 900; color: white;
            box-shadow: 0 4px 5px rgba(0,0,0,0.2);
            font-family: 'Fredoka One', cursive;
        }

        /* Dekorasi Tambahan Acak (Pohon/Kastil) */
        .bg-deco { position: absolute; font-size: 2.5rem; opacity: 0.5; z-index: 1; pointer-events: none; }

        /* --- KEYFRAMES --- */
        @keyframes pulse-glow {
            from { box-shadow: 0 0 10px #fde047, 0 10px 15px rgba(0,0,0,0.2); }
            to { box-shadow: 0 0 30px #f59e0b, 0 10px 15px rgba(0,0,0,0.2); }
        }
        @keyframes bouncePin {
            from { transform: translateX(-50%) translateY(0); }
            to { transform: translateX(-50%) translateY(-15px); }
        }
    </style>
</head>
<body>

<div class="environment">
    <div class="cloud c1">☁️</div>
    <div class="cloud c2">☁️</div>
    <div class="cloud c3">☁️</div>
    <div class="cloud c4">☁️</div>
    <div class="balloon">🎈</div>
    <div class="bird">🦅</div>
</div>

<div class="map-header">
    <div class="header-content">
        <a href="latihan.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="header-title">
            <div class="subtitle">DeutschAktiv Tour</div>
            <div class="main-title">Deutschland Karte</div>
        </div>
        <div style="width: 45px; text-align:center; color:#ef4444; font-size:1.8rem; filter:drop-shadow(0 2px 2px rgba(0,0,0,0.2));"><i class="fa-solid fa-map-location-dot"></i></div> 
    </div>
</div>

<div class="map-container" id="mapContainer">
    <svg class="map-path-svg" id="svgPath">
        <path id="pathLine" d="" fill="none" stroke-width="12" stroke-linecap="round"/>
    </svg>

    <?php 
    for ($i = 1; $i <= 10; $i++): 
        // Data kota berdasarkan index (0-9)
        $cityData = $cities[$i-1];
        
        // Logika zigzag lekukan peta (lebih dinamis)
        $side_offset = sin($i * 1.5) * 110; 
        
        $status = "locked";
        if ($i < $unlocked_map) { $status = "cleared"; } 
        elseif ($i == $unlocked_map) { $status = "current"; }
    ?>
        <div class="level-row">
            
            <?php if($i % 3 == 0): ?>
                <div class="bg-deco" style="left: <?= 30 + ($side_offset * 1.2) ?>px; transform: rotate(<?= rand(-10,10) ?>deg);">🌲</div>
            <?php elseif($i % 2 == 0): ?>
                <div class="bg-deco" style="right: <?= 30 - ($side_offset * 1.2) ?>px; transform: rotate(<?= rand(-15,15) ?>deg);">🏔️</div>
            <?php else: ?>
                <div class="bg-deco" style="left: <?= 40 + ($side_offset * 1.5) ?>px; font-size: 2rem;">🍻</div>
            <?php endif; ?>

            <div class="island-wrapper" style="transform: translateX(<?= $side_offset ?>px);">
                
                <div class="city-sign"><?= $cityData['name'] ?></div>

                <?php if($status == 'current'): ?>
                    <i class="fa-solid fa-location-dot player-pin"></i>
                <?php endif; ?>

                <a href="artikel.php?map=<?= $i ?>" class="island <?= $status ?>" data-point>
                    <div class="tradition-emoji"><?= $cityData['icon'] ?></div>
                    <div class="level-num"><?= $i ?></div>
                </a>

                <div class="theme-label"><?= $cityData['theme'] ?></div>
            </div>

        </div>
    <?php endfor; ?>
</div>

<script>
    function drawPath() {
        const path = document.getElementById('pathLine');
        const points = document.querySelectorAll('[data-point]');
        let d = "";

        points.forEach((el, index) => {
            const rect = el.getBoundingClientRect();
            const parentRect = document.getElementById('mapContainer').getBoundingClientRect();
            
            // Cari titik tengah tiap tombol pulau
            const x = (rect.left + rect.width / 2) - parentRect.left;
            const y = (rect.top + rect.height / 2) - parentRect.top;

            if (index === 0) {
                d += `M ${x} ${y}`;
            } else {
                const prevRect = points[index-1].getBoundingClientRect();
                const prevX = (prevRect.left + prevRect.width / 2) - parentRect.left;
                const prevY = (prevRect.top + prevRect.height / 2) - parentRect.top;
                
                // Bikin kurva Bezier Q biar garis putus-putusnya mulus melengkung
                const cpY = (y + prevY) / 2;
                d += ` Q ${prevX} ${cpY}, ${x} ${y}`;
            }
        });
        path.setAttribute('d', d);
    }

    // Eksekusi gambar garis saat halaman beres loading
    window.addEventListener('load', () => {
        setTimeout(() => {
            drawPath();
            // Scroll otomatis biar langsung nampilin posisi level pemain terakhir
            const current = document.querySelector('.island.current');
            if (current) {
                // Kasih offset dikit ke atas biar pin lokasinya gak ketutup header
                const y = current.getBoundingClientRect().top + window.scrollY - 200;
                window.scrollTo({top: y, behavior: 'smooth'});
            }
        }, 150);
    });
    
    // Gambar ulang kalau layar di-resize (biar responsif di HP/Laptop)
    window.addEventListener('resize', drawPath);
</script>

</body>
</html>
