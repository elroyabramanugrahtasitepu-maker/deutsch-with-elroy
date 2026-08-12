<?php
session_start();

// Cek apakah user sudah login, jika belum arahkan ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Koneksi Database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Grand Line | Nihongo Village</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
<style>
    :root { 
        /* One Piece Pirate Map Palette */
        --map-paper: #E6C280;    
        --map-edge: #B88645;
        --ink-dark: #3E2723;     
        --ink-light: #5D4037;
        --ink-red: #B71C1C;      
        --ocean-blue: #0277BD;   
        
        --radius-lg: 8px; 
        --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Nunito', sans-serif; 
        background-color: var(--map-paper); 
        color: var(--ink-dark); 
        margin: 0; 
        overflow-x: hidden;
        /* Efek perkamen tua dan vignette di pinggiran */
        background-image: 
            radial-gradient(circle at center, rgba(255,255,255,0.15) 0%, rgba(139,69,19,0.4) 100%),
            url("data:image/svg+xml,%3Csvg width='150' height='150' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.6' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23noise)' opacity='0.08'/%3E%3C/svg%3E");
        position: relative;
    }

    /* --- ANIMASI DEKORASI BACKGROUND --- */
    /* Watermark Kompas Raksasa */
    body::before {
        content: '\f14e'; 
        font-family: 'Font Awesome 6 Free';
        font-weight: 400;
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 50vw;
        color: rgba(93, 64, 55, 0.05);
        z-index: -1;
        pointer-events: none;
    }

    /* Efek Ombak Abstrak di Latar */
    .sea-waves {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: 150px;
        background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%233E2723" opacity="0.05"/></svg>') repeat-x;
        background-size: 1000px 150px;
        z-index: -1;
        animation: wave-animation 20s linear infinite;
        pointer-events: none;
    }
    @keyframes wave-animation {
        0% { background-position-x: 0; }
        100% { background-position-x: 1000px; }
    }

    /* Elemen Harta Karun Melayang Bebas */
    .floating-decor {
        position: absolute;
        color: var(--ink-red);
        opacity: 0.15;
        z-index: 0;
        animation: floatRandom 6s ease-in-out infinite alternate;
        pointer-events: none;
    }
    .decor-1 { top: 15%; left: 5%; font-size: 5rem; animation-delay: 0s; } /* Jangkar */
    .decor-2 { top: 30%; right: 8%; font-size: 4rem; animation-delay: 1s; transform: rotate(15deg); } /* Koin */
    .decor-3 { bottom: 20%; left: 10%; font-size: 6rem; animation-delay: 2s; transform: rotate(-20deg); } /* Meriam */
    .decor-4 { bottom: 10%; right: 15%; font-size: 4.5rem; animation-delay: 1.5s; transform: rotate(45deg); } /* Botol Rum */

    @keyframes floatRandom {
        0% { transform: translateY(0px) rotate(0deg); }
        100% { transform: translateY(-20px) rotate(10deg); }
    }

    /* --- TOP NAVIGATION BOURGEOISIE --- */
    .user-nav {
        display: flex; justify-content: space-between; padding: 15px 40px;
        background: rgba(230, 194, 128, 0.9);
        backdrop-filter: blur(5px);
        align-items: center; position: sticky; top: 0; z-index: 1000;
        border-bottom: 3px dashed var(--ink-dark);
    }
    .lobby-action a {
        color: var(--ink-dark); text-decoration: none; font-weight: 900;
        font-size: 1rem; transition: var(--transition); display: flex; align-items: center; gap: 8px;
    }
    .lobby-action a:hover { color: var(--ink-red); text-shadow: 2px 2px 0px rgba(0,0,0,0.1); }
    
    .nav-flags { display: flex; gap: 12px; align-items: center; }
    .flag-icon { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; border: 2px solid var(--ink-dark); cursor: pointer; transition: 0.3s; }
    .flag-icon:hover { transform: scale(1.1) rotate(-10deg); }
    .flag-active { border-color: var(--ink-red); transform: scale(1.1); box-shadow: 0 0 10px rgba(183, 28, 28, 0.5); }
    
    .user-actions { display: flex; gap: 20px; align-items: center; }
    .user-link { color: var(--ink-dark); text-decoration: none; font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
    .user-link:hover { color: var(--ocean-blue); }
    .logout-btn { background: var(--ink-dark); color: var(--map-paper); padding: 8px 15px; border-radius: 4px; border: 2px solid var(--ink-dark); }
    .logout-btn:hover { background: var(--ink-red); color: white; border-color: var(--ink-red); }

    header { padding: 50px 15px 20px; text-align: center; position: relative; z-index: 2; }
    
    .village-badge {
        display: inline-block; background: transparent; color: var(--ink-red);
        font-size: 1.2rem; font-weight: 900; text-transform: uppercase;
        padding: 5px 20px; letter-spacing: 4px; margin-bottom: 10px;
        border-top: 2px solid var(--ink-dark);
        border-bottom: 2px solid var(--ink-dark);
    }
    .logo-text { font-family: 'Lora', serif; font-size: 4rem; color: var(--ink-dark); margin: 0; font-weight: 700; text-shadow: 3px 3px 0px rgba(255,255,255,0.4); }
    .subtitle { color: var(--ink-light); font-weight: 700; font-size: 1.2rem; margin-top: 10px; font-family: 'Nunito', sans-serif; letter-spacing: 1px; }
    
    .back-container {
        width: 95%; max-width: 1200px; margin: 0 auto 30px; 
        display: flex; justify-content: flex-start;
        z-index: 2; position: relative;
    }
    .btn-back {
        display: inline-flex; align-items: center; gap: 10px;
        background: transparent; color: var(--ink-dark);
        text-decoration: none; font-weight: 900; padding: 10px 20px;
        transition: all 0.3s ease; border: 2px dashed var(--ink-dark); font-size: 1rem;
        border-radius: 4px;
    }
    .btn-back:hover { background: var(--ink-dark); color: var(--map-paper); transform: translateX(-5px); }

    .map-container {
        width: 95%; max-width: 1200px; margin: 0 auto 100px;
        display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px;
        position: relative;
        padding: 40px 0;
    }
    
    .map-container::before {
        content: ''; position: absolute; top: 50%; left: 10%; right: 10%; height: 4px;
        background-image: linear-gradient(to right, var(--ink-red) 50%, transparent 50%);
        background-size: 20px 4px; background-repeat: repeat-x; z-index: 0; opacity: 0.5;
    }

    .island-card:nth-child(odd) { transform: translateY(-30px); }
    .island-card:nth-child(even) { transform: translateY(30px); }

    .island-card {
        background: #FDF8E2; 
        border: 3px solid var(--ink-dark);
        border-radius: 4px; 
        padding: 30px 25px;
        text-align: center;
        box-shadow: 8px 8px 0px rgba(62, 39, 35, 0.8);
        transition: var(--transition);
        position: relative;
        display: flex; flex-direction: column; justify-content: space-between;
        z-index: 2; /* Di atas dekorasi background */
        margin-top: 50px; 
    }
    
    .island-card::before {
        content: ''; position: absolute; width: 14px; height: 14px;
        background: #424242; border-radius: 50%; top: -7px; left: 50%;
        transform: translateX(-50%); box-shadow: inset -2px -2px 4px rgba(0,0,0,0.5), 2px 2px 2px rgba(0,0,0,0.3);
    }

    .island-card:hover { transform: translateY(-10px) scale(1.03); box-shadow: 12px 12px 0px var(--ink-red); border-color: var(--ink-red); z-index: 15;}
    .island-card:nth-child(even):hover { transform: translateY(20px) scale(1.03); }

    .icon-wrapper {
        width: 70px; height: 70px; margin: 0 auto 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.8rem; color: var(--ink-dark);
        transition: transform 0.3s ease;
    }
    .island-card:hover .icon-wrapper { transform: scale(1.2) rotate(10deg); color: var(--ink-red); }

    .exercise-title { font-family: 'Lora', serif; font-size: 1.5rem; font-weight: 800; color: var(--ink-dark); margin: 0 0 10px 0; text-transform: uppercase; }
    .exercise-desc { color: var(--ink-light); font-size: 0.95rem; line-height: 1.5; margin-bottom: 25px; flex-grow: 1; font-weight: 700; }

    .btn-enter {
        display: block; background: transparent; color: var(--ink-dark);
        text-decoration: none; font-weight: 900; padding: 12px 15px;
        border: 2px solid var(--ink-dark); font-size: 1rem; text-transform: uppercase;
        letter-spacing: 1px; transition: var(--transition);
        position: relative; overflow: hidden;
    }
    .btn-enter::before {
        content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
        background: var(--ink-red); transition: all 0.3s ease; z-index: -1;
    }
    .island-card:hover .btn-enter { color: white; border-color: var(--ink-red); }
    .island-card:hover .btn-enter::before { left: 0; }

    /* --- ANIMASI ICON KARTU BAJAK LAUT MELAYANG --- */
    .crew-member-icon {
        position: absolute;
        z-index: 10;
        color: var(--ink-red);
        font-size: 3.5rem; /* Sedikit diperkecil agar proporsional */
        filter: drop-shadow(4px 6px 5px rgba(0,0,0,0.3));
        animation: floatAnimeFast 2.5s ease-in-out infinite;
        pointer-events: none; 
    }
    
    @keyframes floatAnimeFast {
        0% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(5deg); }
        100% { transform: translateY(0px) rotate(0deg); }
    }

    /* Posisi masing-masing ikon melayang di kartu */
    .icon-island-1 { top: -55px; right: -15px; font-size: 4rem;} /* Tengkorak */
    .icon-island-2 { bottom: -30px; left: -25px; transform: rotate(-15deg); animation-direction: alternate-reverse;} /* Pedang */
    .icon-island-3 { top: -45px; left: -15px; animation-delay: 1s;} /* Kemudi/Kompas */
    .icon-island-4 { top: -55px; right: -10px; animation-delay: 0.5s; font-size: 4rem;} /* Peti Harta */

    @media (max-width: 900px) {
        .map-container::before { display: none; } 
        .island-card:nth-child(odd), .island-card:nth-child(even) { transform: none; margin-bottom: 50px; }
        .island-card:hover, .island-card:nth-child(even):hover { transform: translateY(-5px); }
        .logo-text { font-size: 2.8rem; }
        .floating-decor { display: none; } /* Matikan dekorasi bg di HP biar gak sempit */
    }
    @media (max-width: 768px) {
        .user-nav { flex-direction: column; gap: 15px; padding: 15px; }
        .back-container { justify-content: center; }
        .crew-member-icon { font-size: 3rem; } 
    }
</style>
</head>
<body>

<div class="sea-waves"></div>
<i class="fa-solid fa-anchor floating-decor decor-1"></i>
<i class="fa-solid fa-coins floating-decor decor-2"></i>
<i class="fa-solid fa-bomb floating-decor decor-3"></i>
<i class="fa-solid fa-wine-bottle floating-decor decor-4"></i>

<div class="user-nav">
    <div class="lobby-action">
        <a href="index.php"><i class="fa-solid fa-anchor"></i> Port Town</a>
    </div>
    <div class="nav-flags">
        <img src="https://flagcdn.com/w80/id.png" alt="Indonesia" class="flag-icon">
        <img src="https://flagcdn.com/w80/jp.png" alt="Jepang" class="flag-icon flag-active"> 
        <img src="https://flagcdn.com/w80/gb.png" alt="Inggris" class="flag-icon"> 
    </div>
    <div class="user-actions">
        <a href="user_profile.php" class="user-link"><i class="fa-solid fa-id-badge"></i> Bounty Poster</a>
        <a href="logout.php" class="user-link logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Set Sail</a>
    </div>
</div>

<header>
    <div class="village-badge"><i class="fa-solid fa-skull-crossbones"></i> SHIN SEKAI</div>
    <h1 class="logo-text">Grand Line Trials</h1>
    <p class="subtitle">"Navigate the rough seas of Japanese grammar. Choose your island!"</p>
</header>

<div class="back-container">
    <a href="japan.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retreat to Log Pose</a>
</div>

<div class="map-container">
    
    <div class="island-card">
        <i class="fa-solid fa-skull-crossbones crew-member-icon icon-island-1"></i>
        
        <div class="icon-wrapper"><i class="fa-solid fa-map"></i></div>
        <h3 class="exercise-title">Bunpou Island</h3>
        <p class="exercise-desc">Pecahkan teka-teki tata bahasa dan rute partikel yang menjebak layaknya navigasi laut.</p>
        <a href="latihan_jp_pg.php" class="btn-enter">Explore Island</a>
    </div>

    <div class="island-card">
        <i class="fa-solid fa-khanda crew-member-icon icon-island-2"></i>

        <div class="icon-wrapper"><i class="fa-solid fa-scroll"></i></div>
        <h3 class="exercise-title">Poneglyph Log</h3>
        <p class="exercise-desc">Pahat jawabanmu sendiri menggunakan Kanji dan Kana layaknya menulis sejarah Poneglyph.</p>
        <a href="latihan_jp_essay.php" class="btn-enter">Write Logbook</a>
    </div>

    <div class="island-card">
        <i class="fa-solid fa-dharmachakra crew-member-icon icon-island-3"></i>

        <div class="icon-wrapper"><i class="fa-solid fa-compass"></i></div>
        <h3 class="exercise-title">Wheel of Truth</h3>
        <p class="exercise-desc">Kendalikan kemudi kapal! Tentukan arah mana yang Maru (Benar) dan Batsu (Salah).</p>
        <a href="latihan_jp_tf.php" class="btn-enter">Steer the Wheel</a>
    </div>

    <div class="island-card">
        <i class="fa-solid fa-sack-dollar crew-member-icon icon-island-4"></i>

        <div class="icon-wrapper"><i class="fa-solid fa-gem"></i></div>
        <h3 class="exercise-title">Kotoba Treasure</h3>
        <p class="exercise-desc">Bongkar peti harta karun! Cocokkan kosakata Jepang dengan kepingan emas maknanya.</p>
        <a href="latihan_jp_match.php" class="btn-enter">Hunt Treasure</a>
    </div>

</div>

</body>
</html>