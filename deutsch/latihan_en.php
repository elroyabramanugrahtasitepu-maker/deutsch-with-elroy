<?php
session_start();

// Cek apakah user sudah login, jika belum arahkan ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Koneksi Database
$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Barn (Gym Menu) | English Village</title>

    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { 
        /* Rustic Village Palette */
        --wood-dark: #3A2E26;    
        --wood-medium: #7A5B45;  
        --wood-light: #C4A484;   
        --wood-grain: #e8d5c4;
        --bg-cream: #FDFBF7;     
        --bg-paper: #F2EBE1;     
        --leaf-green: #4E7B54;   
        --earth-orange: #C86B3C; 
        --sky-blue: #5B8FB9;     
        
        --radius-lg: 24px;
        --radius-md: 12px;
        --shadow-soft: 0 10px 30px rgba(58, 46, 38, 0.08);
        --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Nunito', sans-serif; 
        background-color: var(--bg-paper); 
        color: var(--wood-dark); 
        margin: 0; 
        overflow-x: hidden;
        background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23noise)' opacity='0.05'/%3E%3C/svg%3E");
    }

    /* --- TOP NAVIGATION RUSTIC --- */
    .user-nav {
        display: flex; justify-content: space-between; padding: 15px 40px;
        background: var(--bg-cream);
        align-items: center; position: sticky; top: 0; z-index: 1000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border-bottom: 3px solid var(--wood-light);
    }
    .lobby-action a {
        color: var(--wood-dark); text-decoration: none; font-weight: 800;
        font-size: 0.95rem; transition: var(--transition); display: flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: var(--radius-md); background: var(--bg-paper);
        border: 2px solid transparent;
    }
    .lobby-action a:hover { background: var(--bg-cream); border-color: var(--leaf-green); color: var(--leaf-green); }
    .nav-flags { display: flex; gap: 12px; align-items: center; }
    .flag-icon { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; border: 3px solid var(--bg-paper); cursor: pointer; transition: 0.3s; }
    .flag-icon:hover { transform: scale(1.1) rotate(-5deg); }
    .flag-active { border-color: var(--leaf-green); transform: scale(1.05); }
    .user-actions { display: flex; gap: 15px; align-items: center; }
    .user-link { color: var(--wood-dark); text-decoration: none; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
    .user-link:hover { color: var(--earth-orange); }
    .logout-btn { background: var(--wood-medium); color: var(--bg-cream); padding: 10px 20px; border-radius: var(--radius-md); }
    .logout-btn:hover { background: var(--earth-orange); color: white; }

    /* --- HEADER --- */
    header { padding: 60px 15px 40px; text-align: center; position: relative; }
    
    header::before {
        content: '\f06c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        position: absolute; top: 40px; left: 20%; color: rgba(78, 123, 84, 0.1); font-size: 4rem; transform: rotate(-20deg);
    }
    header::after {
        content: '\f028'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        position: absolute; top: 60px; right: 20%; color: rgba(200, 107, 60, 0.1); font-size: 3rem; transform: rotate(15deg);
    }

    .village-badge {
        display: inline-block; background: var(--leaf-green); color: white;
        font-size: 0.85rem; font-weight: 800; text-transform: uppercase;
        padding: 8px 24px; letter-spacing: 2px; margin-bottom: 15px; border-radius: 30px;
        box-shadow: 0 4px 10px rgba(78, 123, 84, 0.3);
    }
    .logo-text { font-family: 'Lora', serif; font-size: 3.8rem; color: var(--wood-dark); margin: 0; font-weight: 700; letter-spacing: -1px; }
    .subtitle { color: var(--wood-medium); font-weight: 600; font-size: 1.15rem; margin-top: 10px; font-family: 'Lora', serif; font-style: italic; }
    
    /* --- TOMBOL BACK --- */
    .back-container {
        width: 95%; max-width: 1100px; margin: 0 auto 30px; 
        display: flex; justify-content: flex-start;
    }
    .btn-back {
        display: inline-flex; align-items: center; gap: 10px;
        background: var(--bg-cream); color: var(--wood-dark);
        text-decoration: none; font-weight: 800; padding: 12px 25px;
        border-radius: 30px; transition: all 0.3s ease;
        border: 2px solid var(--wood-light); font-size: 1rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .btn-back:hover {
        background: var(--leaf-green); color: white; border-color: var(--leaf-green);
        transform: translateX(-5px); /* Geser ke kiri saat di-hover */
        box-shadow: 0 6px 15px rgba(78, 123, 84, 0.3);
    }

    /* --- MENU GRID UNTUK PILIHAN LATIHAN --- */
    .exercise-container {
        width: 95%; max-width: 1100px; margin: 0 auto 80px;
        display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 35px;
    }
    
    .exercise-card {
        background: var(--bg-cream);
        border: 3px solid var(--wood-light);
        border-radius: var(--radius-lg);
        padding: 40px 30px 35px;
        text-align: center;
        box-shadow: var(--shadow-soft);
        transition: var(--transition);
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        z-index: 1;
        background-image: 
            linear-gradient(rgba(253, 251, 247, 0.95), rgba(253, 251, 247, 0.95)),
            repeating-linear-gradient( 45deg, transparent, transparent 10px, rgba(196, 164, 132, 0.05) 10px, rgba(196, 164, 132, 0.05) 20px );
    }
    
    .exercise-card::before, .exercise-card::after {
        content: ''; position: absolute; width: 10px; height: 10px;
        background: #8b7355; border-radius: 50%; top: 15px;
        box-shadow: inset 1px 1px 3px rgba(0,0,0,0.4);
    }
    .exercise-card::before { left: 15px; }
    .exercise-card::after { right: 15px; }

    .exercise-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 20px 40px rgba(78, 123, 84, 0.15);
        border-color: var(--leaf-green);
    }

    .icon-wrapper {
        width: 80px; height: 80px;
        margin: 0 auto 25px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.2rem; color: white;
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        border: 4px solid var(--bg-cream);
        transition: transform 0.3s ease;
    }
    .exercise-card:hover .icon-wrapper { transform: scale(1.1) rotate(5deg); }

    .bg-pg { background-color: var(--leaf-green); }
    .bg-essay { background-color: var(--earth-orange); }
    .bg-tf { background-color: var(--sky-blue); }
    .bg-match { background-color: var(--wood-medium); }

    .exercise-title {
        font-family: 'Lora', serif; font-size: 1.6rem; font-weight: 700;
        color: var(--wood-dark); margin: 0 0 15px 0;
    }
    .exercise-desc {
        color: var(--wood-medium); font-size: 0.98rem; line-height: 1.6;
        margin-bottom: 30px; flex-grow: 1; font-weight: 600;
    }

    .btn-enter {
        display: block; background: var(--bg-paper); color: var(--wood-dark);
        text-decoration: none; font-weight: 800; padding: 14px 20px;
        border-radius: 30px; transition: var(--transition);
        border: 2px dashed var(--wood-medium);
        font-size: 1.05rem;
    }
    .exercise-card:hover .btn-enter { 
        background: var(--leaf-green); 
        border-color: var(--leaf-green); 
        border-style: solid;
        color: white; 
    }

    @media (max-width: 768px) {
        .user-nav { flex-direction: column; gap: 15px; padding: 15px; }
        .logo-text { font-size: 2.8rem; }
        .exercise-container { grid-template-columns: 1fr; padding: 0 20px; }
        header::before, header::after { display: none; }
        .back-container { justify-content: center; } /* Ketengahin tombol back di HP */
    }
</style>
</head>
<body>

<div class="user-nav">
    <div class="lobby-action">
        <a href="index.php"><i class="fa-solid fa-tree-city"></i> Village Square</a>
    </div>
    <div class="nav-flags">
        <img src="https://flagcdn.com/w80/id.png" alt="Indonesia" class="flag-icon">
        <img src="https://flagcdn.com/w80/us.png" alt="Inggris/Amerika" class="flag-icon flag-active"> 
    </div>
    <div class="user-actions">
        <a href="user_profile.php" class="user-link"><i class="fa-solid fa-address-card"></i> Villager ID</a>
        <a href="logout.php" class="user-link logout-btn"><i class="fa-solid fa-person-walking-arrow-right"></i> Leave</a>
    </div>
</div>

<header>
    <div class="village-badge"><i class="fa-solid fa-dumbbell"></i> THE BARN</div>
    <h1 class="logo-text">Training Grounds</h1>
    <p class="subtitle">"Sharpen your mind, young villager. Choose your path."</p>
</header>

<div class="back-container">
    <a href="english.php" class="btn-back"><i class="fa-solid fa-arrow-left-long"></i> Back to Stories</a>
</div>

<div class="exercise-container">
    
    <div class="exercise-card">
        <div class="icon-wrapper bg-pg"><i class="fa-solid fa-list-check"></i></div>
        <h3 class="exercise-title">Path of Choices</h3>
        <p class="exercise-desc">Test your logic and grammar by selecting the most accurate answer among the options provided.</p>
        <a href="latihan_pg.php" class="btn-enter">Begin Multiple Choice</a>
    </div>

    <div class="exercise-card">
        <div class="icon-wrapper bg-essay"><i class="fa-solid fa-feather-pointed"></i></div>
        <h3 class="exercise-title">The Scribe's Trial</h3>
        <p class="exercise-desc">Gather your thoughts and express them clearly. Write your own answers to complex questions.</p>
        <a href="latihan_essay.php" class="btn-enter">Begin Essay Writing</a>
    </div>

    <div class="exercise-card">
        <div class="icon-wrapper bg-tf"><i class="fa-solid fa-scale-balanced"></i></div>
        <h3 class="exercise-title">Scales of Truth</h3>
        <p class="exercise-desc">Examine the facts carefully. Decide whether the given statements are the truth or a deception.</p>
        <a href="latihan_tf.php" class="btn-enter">Begin True or False</a>
    </div>

    <div class="exercise-card">
        <div class="icon-wrapper bg-match"><i class="fa-solid fa-puzzle-piece"></i></div>
        <h3 class="exercise-title">The Missing Link</h3>
        <p class="exercise-desc">Connect the scattered pieces of vocabulary. Match words to their meanings or fill the void.</p>
        <a href="latihan_match.php" class="btn-enter">Begin Fill & Match</a>
    </div>

</div>

</body>
</html>