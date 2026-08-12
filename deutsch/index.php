<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Deutsch with Elroy | Grand Lobby</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Nunito:wght@300;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Tema Midnight Grand Lobby */
            --bg-dark: #0A0F1D;           /* Biru malam sangat gelap */
            --text-main: #F8FAFC;        /* Putih silver */
            --text-muted: #94A3B8;       /* Abu-abu terang */
            --gold-accent: #D4AF37;      /* Emas mewah */
            --gold-glow: rgba(212, 175, 55, 0.3);
            
            --glass-bg: rgba(15, 23, 42, 0.4);
            --glass-blur: blur(20px);
            --glass-border: rgba(255, 255, 255, 0.1);
            
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.5);
            --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.8);
            
            --transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * { 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            margin: 0;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            overflow-y: auto;
            
            /* Background Lobi Arsitektur Modern Malam Hari */
            background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2069&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Overlay Midnight Blue untuk meredupkan background */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(10, 15, 29, 0.88) 0%, rgba(15, 23, 42, 0.75) 100%);
            z-index: -1;
        }

        /* --- NAVIGASI RESPONSIIF --- */
        .top-nav {
            position: absolute;
            top: 0; width: 100%;
            padding: clamp(15px, 3vw, 30px) clamp(20px, 4vw, 50px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.1rem, 2.5vw, 1.8rem);
            color: var(--gold-accent);
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 0 2px 15px var(--gold-glow);
            white-space: nowrap;
        }

        .btn-logout {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 800;
            font-size: clamp(0.75rem, 1.5vw, 0.95rem);
            padding: clamp(8px, 1.2vw, 12px) clamp(16px, 2vw, 30px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
            display: flex; align-items: center; gap: 8px;
            white-space: nowrap;
        }

        .btn-logout:hover {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold-accent);
            border-color: var(--gold-accent);
            box-shadow: 0 0 20px var(--gold-glow);
        }

        /* --- CONTAINER UTAMA RESPONSIF --- */
        .grand-hall {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            perspective: 1200px;
            overflow: hidden;
            padding-top: clamp(80px, 12vh, 120px);
            padding-bottom: 20px;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.6s ease, filter 0.6s ease;
        }

        .grand-hall.entering {
            transform: scale(0.95);
            opacity: 0.3;
            filter: blur(12px);
        }

        .lobby-info {
            text-align: center;
            margin-bottom: clamp(10px, 2vh, 25px);
            z-index: 10;
            padding: 0 15px;
        }

        .lobby-info h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5.5vw, 4.5rem);
            margin: 0;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--text-main);
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
            line-height: 1.1;
        }

        /* --- ANIMASI UCAPAN 3 BAHASA --- */
        .greetings-container {
            font-family: 'Nunito', sans-serif;
            font-size: clamp(1.1rem, 2.2vw, 1.5rem);
            font-weight: 800;
            color: var(--gold-accent);
            height: clamp(30px, 4vh, 40px);
            position: relative;
            margin-top: 8px;
            margin-bottom: clamp(10px, 2vh, 20px);
            text-shadow: 0 2px 10px var(--gold-glow);
        }

        .greeting-word {
            position: absolute;
            width: 100%;
            left: 0;
            text-align: center;
            opacity: 0;
            letter-spacing: 3px;
            animation: fadeGreeting 9s infinite cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .greeting-word:nth-child(1) { animation-delay: 0s; }
        .greeting-word:nth-child(2) { animation-delay: 3s; }
        .greeting-word:nth-child(3) { animation-delay: 6s; }

        @keyframes fadeGreeting {
            0% { opacity: 0; transform: translateY(15px); filter: blur(4px); }
            10%, 25% { opacity: 1; transform: translateY(0); filter: blur(0); }
            33.3%, 100% { opacity: 0; transform: translateY(-15px); filter: blur(4px); }
        }

        .subtitle {
            font-size: clamp(0.75rem, 1.4vw, 1.1rem);
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: clamp(1px, 0.3vw, 3px);
            margin: 0;
        }

        /* --- INFINITE SCROLL TRACK PERFECT LOOP --- */
        .slider-wrapper {
            width: 100%;
            overflow: hidden;
            padding: clamp(20px, 3vh, 40px) 0 clamp(30px, 5vh, 80px) 0;
            position: relative;
            z-index: 5;
        }

        .slider-wrapper::before,
        .slider-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            width: clamp(5vw, 12vw, 15vw);
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }
        .slider-wrapper::before {
            left: 0;
            background: linear-gradient(to right, rgba(10, 15, 29, 1), transparent);
        }
        .slider-wrapper::after {
            right: 0;
            background: linear-gradient(to left, rgba(10, 15, 29, 1), transparent);
        }

        .slider-track {
            display: flex;
            width: max-content;
            animation: infiniteScroll 35s linear infinite;
            will-change: transform;
        }

        .slider-group {
            display: flex;
            gap: clamp(20px, 3vw, 40px);
            padding-right: clamp(20px, 3vw, 40px); /* Menyamakan presisi gap antar-grup */
        }

        .slider-track:hover {
            animation-play-state: paused;
        }

        @keyframes infiniteScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); } /* Bergeser persis 1 grup penuh tanpa celah */
        }

        /* --- DESTINATION CARDS RESPONSIVE --- */
        .lang-card {
            flex: 0 0 clamp(240px, 22vw, 320px);
            height: clamp(350px, 52vh, 450px);
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: var(--transition);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }

        .lang-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to top, rgba(10, 15, 29, 0.95) 0%, rgba(10, 15, 29, 0.3) 50%, transparent 100%);
            z-index: 2;
            transition: var(--transition);
        }

        .card-img-wrapper {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            overflow: hidden;
            z-index: 1;
            border-radius: 20px;
        }

        .lang-card img {
            width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.65;
            transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .lang-card:hover {
            transform: translateY(-10px);
            border-color: var(--gold-accent);
            box-shadow: var(--shadow-hover), 0 0 30px var(--gold-glow);
            z-index: 10;
        }

        .lang-card:hover::before {
            background: linear-gradient(to top, rgba(10, 15, 29, 0.95) 0%, rgba(10, 15, 29, 0.1) 60%, transparent 100%);
        }

        .lang-card:hover img {
            transform: scale(1.12);
            opacity: 0.9;
        }

        .card-content {
            position: relative;
            z-index: 3;
            text-align: center;
            padding: clamp(18px, 2.5vw, 30px);
            margin-top: auto;
        }

        .lang-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 2.5vw, 2.5rem);
            font-weight: 800;
            margin: 0;
            color: var(--text-main);
            letter-spacing: 1px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.8);
        }

        .lang-desc {
            font-size: clamp(0.75rem, 1vw, 0.9rem);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold-accent);
            margin-top: 4px;
            margin-bottom: 15px;
        }
        
        .enter-btn {
            display: inline-block;
            padding: clamp(8px, 1vw, 10px) clamp(18px, 1.8vw, 25px);
            background: rgba(255, 255, 255, 0.12);
            color: var(--gold-accent);
            border: 1px solid var(--gold-accent);
            border-radius: 30px;
            font-size: clamp(0.75rem, 0.9vw, 0.85rem);
            font-weight: 800;
            letter-spacing: 1px;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        @media (min-width: 769px) {
            .enter-btn {
                opacity: 0;
                transform: translateY(15px);
            }
            .lang-card:hover .enter-btn {
                opacity: 1;
                transform: translateY(0);
                background: var(--gold-accent);
                color: var(--bg-dark);
            }
        }

        @media (max-width: 768px) {
            .enter-btn {
                opacity: 1;
                transform: translateY(0);
                background: rgba(212, 175, 55, 0.15);
            }
        }

        /* --- LOADING OVERLAY RESPONSIIF --- */
        #loadingOverlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(10, 15, 29, 0.92);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            padding: 20px;
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.5s;
        }

        #loadingOverlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-card {
            text-align: center;
            padding: clamp(30px, 5vw, 50px) clamp(25px, 6vw, 60px);
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--gold-accent);
            border-radius: 25px;
            box-shadow: 0 0 50px var(--gold-glow);
            max-width: 420px;
            width: 100%;
            position: relative;
            overflow: hidden;
            transform: scale(0.85) translateY(30px);
            opacity: 0;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease;
        }

        #loadingOverlay.active .loading-card {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .loading-compass {
            width: clamp(55px, 12vw, 80px);
            height: clamp(55px, 12vw, 80px);
            margin: 0 auto clamp(15px, 3vh, 25px);
            border-radius: 50%;
            border: 2px solid rgba(212, 175, 55, 0.2);
            border-top: 3px solid var(--gold-accent);
            display: flex; align-items: center; justify-content: center;
            animation: spinRing 1.2s linear infinite;
            box-shadow: 0 0 25px var(--gold-glow);
        }

        .loading-compass i {
            font-size: clamp(1.5rem, 3.5vw, 2.2rem);
            color: var(--gold-accent);
            animation: spinReverse 2.4s linear infinite;
        }

        @keyframes spinRing { 100% { transform: rotate(360deg); } }
        @keyframes spinReverse { 100% { transform: rotate(-360deg); } }

        .loading-greeting {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.5rem, 4vw, 2.2rem);
            font-weight: 800;
            color: var(--gold-accent);
            margin: 0;
            letter-spacing: 1px;
            text-shadow: 0 2px 10px var(--gold-glow);
        }

        .loading-sub {
            font-size: clamp(0.8rem, 1.8vw, 0.95rem);
            font-weight: 700;
            color: var(--text-main);
            margin-top: 8px;
            letter-spacing: 0.5px;
        }

        .progress-bar-wrap {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-top: clamp(20px, 3vh, 30px);
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--gold-accent), #FFFFFF);
            box-shadow: 0 0 12px var(--gold-accent);
            transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @media (max-width: 480px) {
            .top-nav { padding: 15px 18px; }
            .brand { font-size: 1.1rem; }
            .btn-logout { padding: 8px 14px; font-size: 0.75rem; }
            .btn-logout span { display: none; }
            .btn-logout i { margin: 0; }
        }

        @media (max-height: 550px) {
            .grand-hall { padding-top: 60px; }
            .lobby-info { margin-bottom: 5px; }
            .slider-wrapper { padding: 10px 0 20px 0; }
            .lang-card { height: 260px; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .grand-hall { animation: fadeIn 1.2s cubic-bezier(0.16, 1, 0.3, 1); }

    </style>
</head>
<body>

    <nav class="top-nav">
        <div class="brand"><i class="fa-solid fa-earth-americas"></i> DEUTSCH WITH ELROY</div>
        <a href="logout.php" class="btn-logout">
            <i class="fa-solid fa-door-open"></i> <span>EXIT LOBBY</span>
        </a>
    </nav>

    <div class="grand-hall">
        <div class="lobby-info">
            <h1>CHOOSE DESTINATION</h1>
            <div class="greetings-container">
                <div class="greeting-word">WELCOME</div>
                <div class="greeting-word">WILLKOMMEN</div>
                <div class="greeting-word">ようこそ</div>
            </div>
            <p class="subtitle">Select your language faculty</p>
        </div>

        <div class="slider-wrapper">
            <div class="slider-track">
                <!-- GRUP 1 -->
                <div class="slider-group">
                    <a href="english.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?q=80&w=1000&auto=format&fit=crop" alt="London"></div>
                        <div class="card-content">
                            <h2 class="lang-title">English</h2>
                            <p class="lang-desc">Faculty I</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="deutsch.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1599946347371-68eb71b16afc?q=80&w=1000&auto=format&fit=crop" alt="Berlin"></div>
                        <div class="card-content">
                            <h2 class="lang-title">Deutsch</h2>
                            <p class="lang-desc">Faculty II</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="japan.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1528360983277-13d401cdc186?q=80&w=1000&auto=format&fit=crop" alt="Tokyo"></div>
                        <div class="card-content">
                            <h2 class="lang-title">日本語</h2>
                            <p class="lang-desc">Faculty III</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="english.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?q=80&w=1000&auto=format&fit=crop" alt="London"></div>
                        <div class="card-content">
                            <h2 class="lang-title">English</h2>
                            <p class="lang-desc">Faculty I</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="deutsch.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1599946347371-68eb71b16afc?q=80&w=1000&auto=format&fit=crop" alt="Berlin"></div>
                        <div class="card-content">
                            <h2 class="lang-title">Deutsch</h2>
                            <p class="lang-desc">Faculty II</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="japan.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1528360983277-13d401cdc186?q=80&w=1000&auto=format&fit=crop" alt="Tokyo"></div>
                        <div class="card-content">
                            <h2 class="lang-title">日本語</h2>
                            <p class="lang-desc">Faculty III</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>
                </div>

                <!-- GRUP 2 (DUPLIKASI PERSIS GRUP 1 UNTUK SEAMLESS LOOP) -->
                <div class="slider-group">
                    <a href="english.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?q=80&w=1000&auto=format&fit=crop" alt="London"></div>
                        <div class="card-content">
                            <h2 class="lang-title">English</h2>
                            <p class="lang-desc">Faculty I</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="deutsch.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1599946347371-68eb71b16afc?q=80&w=1000&auto=format&fit=crop" alt="Berlin"></div>
                        <div class="card-content">
                            <h2 class="lang-title">Deutsch</h2>
                            <p class="lang-desc">Faculty II</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="japan.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1528360983277-13d401cdc186?q=80&w=1000&auto=format&fit=crop" alt="Tokyo"></div>
                        <div class="card-content">
                            <h2 class="lang-title">日本語</h2>
                            <p class="lang-desc">Faculty III</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="english.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?q=80&w=1000&auto=format&fit=crop" alt="London"></div>
                        <div class="card-content">
                            <h2 class="lang-title">English</h2>
                            <p class="lang-desc">Faculty I</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="deutsch.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1599946347371-68eb71b16afc?q=80&w=1000&auto=format&fit=crop" alt="Berlin"></div>
                        <div class="card-content">
                            <h2 class="lang-title">Deutsch</h2>
                            <p class="lang-desc">Faculty II</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>

                    <a href="japan.php" class="lang-card">
                        <div class="card-img-wrapper"><img src="https://images.unsplash.com/photo-1528360983277-13d401cdc186?q=80&w=1000&auto=format&fit=crop" alt="Tokyo"></div>
                        <div class="card-content">
                            <h2 class="lang-title">日本語</h2>
                            <p class="lang-desc">Faculty III</p>
                            <span class="enter-btn">ENTER <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- LOADING OVERLAY MEWAH -->
    <div id="loadingOverlay">
        <div class="loading-card">
            <div class="loading-compass">
                <i class="fa-solid fa-compass-drafting"></i>
            </div>
            <h2 class="loading-greeting" id="loadingGreeting">WELCOME</h2>
            <p class="loading-sub" id="loadingSub">Preparing your learning faculty...</p>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
        </div>
    </div>

<script>
document.querySelectorAll('.lang-card').forEach(card => {
    card.addEventListener('click', function(e) {
        e.preventDefault();
        const targetUrl = this.getAttribute('href');
        const langTitle = this.querySelector('.lang-title').innerText.trim();

        const grandHall = document.querySelector('.grand-hall');
        const overlay = document.getElementById('loadingOverlay');
        const greeting = document.getElementById('loadingGreeting');
        const sub = document.getElementById('loadingSub');
        const progressFill = document.getElementById('progressFill');

        // Sesuaikan ucapan & pesan berdasarkan fakultas pilihan
        if (langTitle.includes("English")) {
            greeting.innerText = "WELCOME";
            sub.innerText = "Entering London Discovery Faculty...";
        } else if (langTitle.includes("Deutsch")) {
            greeting.innerText = "WILLKOMMEN";
            sub.innerText = "Eintreten in die Deutsche Fakultät...";
        } else {
            greeting.innerText = "ようこそ (YŌKOSO)";
            sub.innerText = "Entering Sakura Pavilion Faculty...";
        }

        // Efek blur & scale pada latar lobi saat mengklik
        grandHall.classList.add('entering');

        // Tampilkan overlay dengan animasi halus
        overlay.classList.add('active');

        // Animasi progress bar berjalan mulus
        requestAnimationFrame(() => {
            setTimeout(() => {
                progressFill.style.width = '100%';
            }, 60);
        });

        // Pindah halaman tepat setelah 1.05 detik
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 1050);
    });
});
</script>

</body>
</html>