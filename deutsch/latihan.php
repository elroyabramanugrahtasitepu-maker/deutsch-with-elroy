<?php
// ==========================================
// BAGIAN PHP ASLI MILIKMU (TIDAK DIUBAH SAMA SEKALI)
// ==========================================
session_start();
$host = "localhost";
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321";
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

$level = $_GET['level'] ?? $_POST['level'] ?? null;
$tipe = $_GET['tipe'] ?? $_POST['tipe'] ?? null;

// --- PENENTUAN TABEL DINAMIS ---
if ($level == 'MODALVERBEN') {
    $table_name = 'latihan_modalverben';
} elseif ($level == 'HOREN') {
    $table_name = 'latihan_horen';
} else {
    $table_name = 'latihan_soal';
}

// --- LOGIKA RESET ---
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'reset') {
    if ($level == 'ARTIKEL') {
        $conn->query("DELETE FROM user_progress WHERE user_id = $uid AND soal_id IN (SELECT id FROM latihan_artikel)");
    } else {
        $stmt = $conn->prepare("DELETE up FROM user_progress up 
                                JOIN $table_name t ON up.soal_id = t.id 
                                WHERE up.user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
    }
    header("Location: latihan.php?level=$level&tipe=$tipe");
    exit();
}

// 1. REDIRECT UNTUK MODUL KHUSUS
if ($level == 'ARTIKEL') {
    header("Location: artikel_map.php");
    exit();
} elseif ($level == 'PERCAKAPAN') {
    header("Location: simulasi_percakapan.php");
    exit();
} elseif ($level == 'PUZZLE') { 
    header("Location: puzzle_map.php");
    exit();
} elseif ($level == 'HOREN') {
    header("Location: horen.php");
    exit();
} else {
    $res_done = $conn->query("SELECT soal_id FROM user_progress 
                              WHERE user_id = $uid 
                              AND soal_id IN (SELECT id FROM $table_name)");
}

$already_done_ids = [];
if($res_done) {
    while($row = $res_done->fetch_assoc()) { $already_done_ids[] = $row['soal_id']; }
}
$exclude_ids = !empty($already_done_ids) ? implode(",", $already_done_ids) : "0";

// 2. HITUNG PROGRES & AMBIL SOAL (A1, A2, B1, Modalverben)
$count_type_done = 0;
$count_current_batch = 0;

if ($level && $tipe) {
    $where_clause = ($level == 'MODALVERBEN') ? "WHERE 1=1" : "WHERE level = '$level' AND tipe = '$tipe'";

    $res_count = $conn->query("SELECT COUNT(*) as total FROM user_progress 
                               JOIN $table_name ON user_progress.soal_id = $table_name.id 
                               WHERE user_progress.user_id = $uid");
    $row_count = $res_count->fetch_assoc();
    $count_type_done = $row_count['total'] ?? 0;

    $query_soal = $conn->query("SELECT * FROM $table_name 
                                 $where_clause 
                                 AND id NOT IN ($exclude_ids) 
                                 ORDER BY id ASC LIMIT 15");
    
    if($query_soal) {
        $count_current_batch = $query_soal->num_rows;
    }
}
// ==========================================
// AKHIR BAGIAN PHP ASLI
// ==========================================
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>International Terminal | DeutschAktiv</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            --airport-dark: #0b1120; 
            --airport-blue: #1e293b;
            --airport-yellow: #facc15; 
            --led-orange: #f97316;
            --led-green: #4ade80;
            --bg-quiz: #f8fafc; 
            --text-dark: #0f172a; 
            --text-slate: #64748b; 
            --white: #ffffff;
            --primary-blue: #2563eb;
        }

        * { box-sizing: border-box; scroll-behavior: smooth; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; padding-bottom: 140px; color: var(--text-dark); overflow-x: hidden; 
            background-color: #f1f5f9;
            background-image: 
                /* Efek Overlay Gelap untuk Keterbacaan Teks */
                linear-gradient(rgba(11, 17, 32, 0.4), rgba(11, 17, 32, 0.6)),
                /* =========================================
                   BACKGROUND BANDARA DI JERMAN
                   Ganti URL ini dengan file lokal jika hotlink lambat.
                   Contoh hotlink terminal Unsplash.
                   ========================================= */
                url('https://images.unsplash.com/photo-1549488344-1f9b8d2bd1f3?q=80&w=2070&auto=format&fit=crop'); 
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }

        /* NAVBAR TEMA TERMINAL */
        .navbar { 
            background: rgba(11, 17, 32, 0.9); backdrop-filter: blur(10px); 
            padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; 
            position: sticky; top: 0; z-index: 1000; border-bottom: 3px solid var(--airport-yellow); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            animation: slideDown 0.6s ease-out forwards;
        }
        .btn-circle { 
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; 
            border-radius: 12px; background: rgba(255,255,255,0.1); color: var(--white); 
            text-decoration: none; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); font-size: 1.2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-circle:hover { background: var(--airport-yellow); color: var(--airport-dark); transform: scale(1.1) rotate(-5deg); border-color: var(--airport-yellow); }

        .prog-chip { 
            background: #000; color: var(--led-green); padding: 8px 18px; border-radius: 8px; 
            font-size: 0.9rem; font-weight: 800; display: flex; align-items: center; gap: 10px; 
            font-family: monospace; letter-spacing: 2px; border: 2px solid #222; 
            box-shadow: inset 0 0 15px rgba(74, 222, 128, 0.3), 0 0 10px rgba(74, 222, 128, 0.2);
        }
        .prog-chip i { animation: radarSpin 4s linear infinite; }

        /* ====================================================
           HERO: PAPAN LED JADWAL PENERBANGAN (FULL ANIMASI)
           ==================================================== */
        .fids-board-container { padding: 40px 20px; display: flex; justify-content: center; perspective: 1200px; z-index: 2;}
        .fids-board {
            background: #000; border: 10px solid #1a1a1a; border-radius: 16px; padding: 30px 40px;
            text-align: center; width: 100%; max-width: 850px; position: relative; overflow: hidden;
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.6), inset 0 0 40px rgba(0,0,0,0.9);
            transform-origin: top center;
            animation: swingIn 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        /* Efek Kilauan Kaca Layar */
        .fids-board::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
            transform: skewX(-25deg); animation: screenGlare 6s infinite; z-index: 3;
        }
        /* Efek Scanlines LED */
        .fids-board::after { 
            content:''; position:absolute; top:0; left:0; right:0; bottom:0; 
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.3) 2px, rgba(0,0,0,0.3) 4px); 
            pointer-events: none; z-index: 2;
        }
        
        .fids-header { 
            color: #555; font-family: monospace; font-size: 0.95rem; font-weight: 800; 
            letter-spacing: 5px; display: flex; justify-content: space-between; 
            border-bottom: 2px solid #2a2a2a; padding-bottom: 15px; margin-bottom: 20px; 
            position: relative; z-index: 4;
        }
        .fids-title { 
            color: var(--airport-yellow); font-family: monospace; font-size: 3.2rem; font-weight: 900; 
            margin: 0; letter-spacing: 8px; text-transform: uppercase; 
            text-shadow: 0 0 18px rgba(250, 204, 21, 0.7), 0 0 2px rgba(255,255,255,0.5);
            animation: textFlicker 4s infinite;
            position: relative; z-index: 4;
        }
        .fids-subtitle { 
            color: var(--led-orange); font-family: monospace; font-size: 1.3rem; margin-top: 15px; 
            font-weight: 700; letter-spacing: 3px; position: relative; z-index: 4;
            text-shadow: 0 0 10px rgba(249, 115, 22, 0.4);
        }
        .fids-subtitle i { animation: planeFly 4s ease-in-out infinite alternate; display: inline-block;}

        /* ====================================================
           MENU LEVEL: PLANG GATE JERMAN MENYALA (FULL GATE)
           ==================================================== */
        .grid-layout { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 35px; padding: 20px 5% 50px; max-width: 1200px; margin: auto; perspective: 1000px; z-index: 2; position: relative;}
        
        .gate-sign {
            background: var(--white); border-radius: 16px; text-decoration: none; color: var(--text-dark);
            box-shadow: 0 15px 35px -5px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; border: 2px solid transparent;
            opacity: 0; transform: translateY(60px) rotateX(-10deg); 
            animation: popUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        /* Delay Animasi Gate (Muncul Bergantian) */
        .gate-sign:nth-child(1) { animation-delay: 0.2s; } .gate-sign:nth-child(2) { animation-delay: 0.3s; }
        .gate-sign:nth-child(3) { animation-delay: 0.4s; } .gate-sign:nth-child(4) { animation-delay: 0.5s; }
        .gate-sign:nth-child(5) { animation-delay: 0.6s; } .gate-sign:nth-child(6) { animation-delay: 0.7s; }
        .gate-sign:nth-child(7) { animation-delay: 0.8s; } /* Der/Die/Das */

        .gate-sign:hover { 
            transform: translateY(-15px) scale(1.03) rotateX(0deg); 
            box-shadow: 0 35px 60px -15px rgba(250, 204, 21, 0.4), 0 0 20px rgba(250, 204, 21, 0.2); 
            border-color: var(--airport-yellow); 
        }

        /* Kepala Plang Kuning Jerman */
        .gate-sign-top {
            background: var(--airport-yellow); color: #000; padding: 20px 25px;
            display: flex; justify-content: space-between; align-items: center; border-bottom: 6px solid var(--airport-dark);
            position: relative; overflow: hidden;
        }
        .gate-number { font-size: 2.1rem; font-weight: 900; letter-spacing: 2px; display: flex; align-items: center; gap: 15px;}
        .gate-icon { 
            background: #000; color: var(--airport-yellow); width: 48px; height: 48px; 
            display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 1.5rem; 
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .gate-sign:hover .gate-icon { transform: scale(1.15) rotate(15deg); box-shadow: 0 0 20px rgba(0,0,0,0.4); }
        
        .gate-status { 
            background: #000; color: var(--led-green); font-family: monospace; font-size: 0.9rem; 
            font-weight: 800; padding: 7px 14px; border-radius: 6px; border: 1px solid #333;
            animation: blinkStatus 2s infinite; letter-spacing: 2px;
            box-shadow: 0 0 10px rgba(74, 222, 128, 0.2);
        }

        /* Badan Plang */
        .gate-sign-body { padding: 35px 25px 30px; display: flex; flex-direction: column; gap: 10px; position: relative; background: url('https://www.transparenttextures.com/patterns/cubes.png'); }
        .dest-label { font-size: 0.85rem; color: var(--text-slate); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }
        .dest-name { font-size: 1.7rem; font-weight: 900; color: var(--text-dark); margin: 0; text-shadow: 0 1px 2px rgba(255,255,255,0.8); }
        
        /* Barcode Tiket */
        .gate-barcode { 
            margin-top: 25px; height: 38px; width: 100%; 
            background: repeating-linear-gradient(90deg, #0b1120, #0b1120 4px, transparent 4px, transparent 9px, #0b1120 9px, #0b1120 13px, transparent 13px, transparent 16px); 
            opacity: 0.12; transition: 0.4s; 
        }
        .gate-sign:hover .gate-barcode { opacity: 0.45; }
        
        .gate-arrow { position: absolute; right: 25px; bottom: 45px; font-size: 2rem; color: #cbd5e1; transition: 0.4s; }
        .gate-sign:hover .gate-arrow { color: var(--primary-blue); transform: translateX(12px); animation: pulseArrow 1s infinite alternate;}

        /* Tipe Plang Biru Transit */
        .gate-blue .gate-sign-top { background: var(--airport-blue); color: var(--white); border-bottom-color: var(--airport-yellow); }
        .gate-blue .gate-icon { background: var(--airport-yellow); color: var(--airport-dark); }
        .gate-blue .gate-status { background: rgba(0,0,0,0.5); color: var(--airport-yellow); border-color: rgba(250, 204, 21, 0.3); box-shadow: 0 0 10px rgba(250, 204, 21, 0.2); }


        /* =========================================
           BAGIAN DALAM KUIS (UI TIKET PREMIUM)
           ========================================= */
        .q-container { width: 92%; max-width: 850px; margin: auto; animation: popUp 0.6s ease-out forwards; z-index: 2; position: relative; margin-top: 40px;}
        .q-card { 
            background: var(--bg-quiz); border-radius: 20px; padding: 50px; margin-bottom: 40px; 
            border: 1px solid #e2e8f0; box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            border-top: 10px solid var(--airport-blue);
            position: relative; overflow: hidden;
        }
        /* Aksen Sobekan Tiket */
        .q-card::before {
            content: ''; position: absolute; left: -15px; top: 50%; transform: translateY(-50%);
            width: 30px; height: 30px; background: #cbd5e1; border-radius: 50%; box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        }
        .q-card::after {
            content: ''; position: absolute; right: -15px; top: 50%; transform: translateY(-50%);
            width: 30px; height: 30px; background: #cbd5e1; border-radius: 50%; box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        }

        .q-meta { 
            display: inline-block; font-weight: 800; color: #000; background: var(--airport-yellow); 
            padding: 7px 20px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 25px; 
            text-transform: uppercase; letter-spacing: 2px; font-family: monospace;
            box-shadow: 0 5px 12px rgba(250, 204, 21, 0.3);
        }
        .q-text { font-size: 1.6rem; font-weight: 800; margin-bottom: 40px; line-height: 1.6; color: #1e293b; text-shadow: 0 1px 1px var(--white); }

        .option-box { 
            display: flex; align-items: center; padding: 22px 30px; background: var(--white); 
            border: 2px solid #e2e8f0; border-radius: 16px; margin-bottom: 18px; cursor: pointer; 
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); font-weight: 700; width: 100%; color: #475569; font-size: 1.15rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .option-box:hover { border-color: #cbd5e1; transform: translateX(6px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .option-box.active { 
            border-color: var(--primary-blue); background: #eff6ff; color: var(--primary-blue); 
            transform: translateX(12px) scale(1.02); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15); 
        }
        .opt-label { 
            width: 38px; height: 38px; border: 2px solid #cbd5e1; border-radius: 8px; 
            display: flex; align-items: center; justify-content: center; margin-right: 22px; 
            font-size: 0.95rem; background: var(--white); transition: all 0.3s; 
        }
        .option-box.active .opt-label { border-color: var(--primary-blue); background: var(--primary-blue); color: white; transform: rotate(10deg);}

        .essai-wrapper { position: relative; width: 100%; }
        .essai-input { 
            width: 100%; border: 2px solid #e2e8f0; background: var(--white); padding: 25px 35px; 
            border-radius: 16px; font-family: inherit; font-size: 1.2rem; font-weight: 600; 
            transition: all 0.3s; outline: none; color: var(--text-dark);
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .essai-input:focus { border-color: var(--primary-blue); box-shadow: 0 12px 30px rgba(37, 99, 235, 0.18); transform: translateY(-3px); }
        .essai-input::placeholder { color: #94a3b8; font-weight: 400; }
        .essai-wrapper i { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); color: #cbd5e1; font-size: 1.6rem; pointer-events: none; transition: 0.3s; }
        .essai-input:focus + i { color: var(--primary-blue); transform: translateY(-50%) scale(1.2) rotate(15deg); }

        /* BOTTOM BAR TERMINAL */
        .bottom-bar { 
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 850px; 
            background: rgba(11, 17, 32, 0.95); backdrop-filter: blur(12px); 
            padding: 20px 35px; border-radius: 20px; display: flex; gap: 20px; 
            z-index: 1000; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6); border: 2px solid #2a2a2a; 
            animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        .btn-full { 
            flex: 1; padding: 20px; border-radius: 12px; border: none; font-weight: 900; cursor: pointer; 
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); font-size: 1.05rem; display: flex; 
            align-items: center; justify-content: center; gap: 12px; text-decoration: none; color: white; 
            text-transform: uppercase; letter-spacing: 2px;
        }
        .btn-primary { background: var(--airport-yellow); color: #000; box-shadow: 0 5px 20px rgba(250, 204, 21, 0.3); }
        .btn-dark { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);}
        .btn-full:hover { transform: translateY(-6px) scale(1.02); }
        .btn-primary:hover { background: #ffd000; box-shadow: 0 10px 30px rgba(250, 204, 21, 0.6); }
        .btn-dark:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.25); }

        /* =========================================
           KEYFRAMES ANIMASI BANDARA
           ========================================= */
        @keyframes popUp { from { opacity: 0; transform: translateY(70px) rotateX(-10deg); } to { opacity: 1; transform: translateY(0) rotateX(0deg); } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-100%); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideUp { from { opacity: 0; transform: translate(-50%, 120px); } to { opacity: 1; transform: translate(-50%, 0); } }
        @keyframes swingIn { 0% { opacity: 0; transform: rotateX(-40deg) scale(0.8); } 100% { opacity: 1; transform: rotateX(0deg) scale(1); } }
        @keyframes blinkStatus { 0%, 100% { opacity: 1; text-shadow: 0 0 10px var(--led-green); } 50% { opacity: 0.3; text-shadow: none; } }
        @keyframes textFlicker { 0%, 100% { opacity: 1; text-shadow: 0 0 18px rgba(250, 204, 21, 0.7); } 92% { opacity: 0.95; } 96% { opacity: 0.3; text-shadow: none; } 98% { opacity: 0.9; } }
        @keyframes screenGlare { 0% { left: -110%; } 25% { left: 220%; } 100% { left: 220%; } }
        @keyframes planeFly { 0% { transform: translateX(-6px) translateY(2px) rotate(-1deg); } 100% { transform: translateX(6px) translateY(-2px) rotate(1deg); } }
        @keyframes radarSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes pulseArrow { from { transform: translateX(12px); opacity: 1; } to { transform: translateX(18px); opacity: 0.7; } }

        @media (max-width: 600px) { .fids-board { padding: 25px 20px; border-width: 6px; } .fids-title { font-size: 1.9rem; letter-spacing: 4px; } .grid-layout { grid-template-columns: 1fr; gap: 25px;} .bottom-bar span { display: none; } .q-text { font-size: 1.25rem; } .q-card { padding: 35px 25px; } .gate-number { font-size: 1.7rem; } .gate-sign-body { padding: 25px 20px; } }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="<?= (!$level) ? 'deutsch.php' : 'latihan.php' ?>" class="btn-circle">
        <i class="fa-solid <?= (!$level) ? 'fa-house' : 'fa-arrow-left' ?>"></i>
    </a>
    <div class="prog-chip">
        <i class="fa-solid fa-plane-arrival"></i> 
        <span>FLIGHT LOG: <?= sprintf("%04d", $count_type_done) ?> Soal</span>
    </div>
</nav>

<div class="main-content">
    <?php if(!$level): ?>
        <div class="fids-board-container">
            <div class="fids-board">
                <div class="fids-header">
                    <span>FLIGHT</span>
                    <span>DEPARTURE</span>
                    <span>REMARK</span>
                </div>
                <h1 class="fids-title">ABFLUG DEUTSCH</h1>
                <div class="fids-subtitle"><i class="fa-solid fa-plane-departure"></i> PLEASE PROCEED TO YOUR DEPARTURE GATE</div>
            </div>
        </div>

        <div class="grid-layout">
            <a href="?level=A1" class="gate-sign">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-seedling"></i></div> GATE A1</div>
                    <div class="gate-status">BOARDING</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Destination Route</span>
                    <h3 class="dest-name">Anfänger (Pemula)</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
            
            <a href="?level=A2" class="gate-sign gate-blue">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-leaf"></i></div> GATE A2</div>
                    <div class="gate-status">ON TIME</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Destination Route</span>
                    <h3 class="dest-name">Grundlegend (Dasar)</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
            
            <a href="?level=B1" class="gate-sign">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-tree"></i></div> GATE B1</div>
                    <div class="gate-status">OPEN</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Destination Route</span>
                    <h3 class="dest-name">Fortgeschritten (Menengah)</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
            
            <a href="?level=MODALVERBEN&tipe=pilihan_ganda" class="gate-sign gate-blue">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-lightbulb"></i></div> GATE MV</div>
                    <div class="gate-status">BOARDING</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Special Route</span>
                    <h3 class="dest-name">Modalverben Meister</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
            
            <a href="?level=HOREN" class="gate-sign">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-headphones"></i></div> GATE HR</div>
                    <div class="gate-status">OPEN</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Audio Terminal</span>
                    <h3 class="dest-name">Hören-Heros</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
            
            <a href="?level=PUZZLE" class="gate-sign gate-blue">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-puzzle-piece"></i></div> GATE PZ</div>
                    <div class="gate-status">OPEN</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Baggage Claim Area</span>
                    <h3 class="dest-name">Satzbau-Profi</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>

            <a href="artikel_map.php" class="gate-sign">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-map-location-dot"></i></div> GATE AM</div>
                    <div class="gate-status" style="color:var(--white);">TRANSIT</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Transit Map Route</span>
                    <h3 class="dest-name">Artikel (Der/Die/Das)</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
        </div>

    <?php elseif($level && !$tipe): ?>
        <div class="fids-board-container">
            <div class="fids-board">
                <div class="fids-header"><span>TICKET</span><span>GATE <?= $level ?></span><span>CABIN</span></div>
                <h1 class="fids-title">WÄHLE CABIN</h1>
                <div class="fids-subtitle"><i class="fa-solid fa-ticket"></i> SELECT YOUR TICKET CLASS /CABIN</div>
            </div>
        </div>
        
        <div class="grid-layout" style="max-width: 850px;">
            <a href="?level=<?= $level ?>&tipe=pilihan_ganda" class="gate-sign">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-chair"></i></div> TIER 1</div>
                    <div class="gate-status">ECONOMY</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Format Latihan</span>
                    <h3 class="dest-name">Multiple Choice</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
            
            <a href="?level=<?= $level ?>&tipe=essai" class="gate-sign gate-blue">
                <div class="gate-sign-top">
                    <div class="gate-number"><div class="gate-icon"><i class="fa-solid fa-martini-glass"></i></div> TIER 2</div>
                    <div class="gate-status">BUSINESS</div>
                </div>
                <div class="gate-sign-body">
                    <span class="dest-label">Format Latihan</span>
                    <h3 class="dest-name">Latihan Menulis (Essai)</h3>
                    <div class="gate-barcode"></div>
                    <i class="fa-solid fa-arrow-right-long gate-arrow"></i>
                </div>
            </a>
        </div>

    <?php else: ?>
        <form action="proses_jawaban.php" method="POST" id="quizForm">
            <input type="hidden" name="level" value="<?= $level ?>">
            <input type="hidden" name="tipe" value="<?= $tipe ?>">
            
            <div class="q-container">
                <?php if($count_current_batch > 0): ?>
                    <?php $no = $count_type_done + 1; while($s = $query_soal->fetch_assoc()): ?>
                        <div class="q-card">
                            <span class="q-meta">Frage <?= $no++ ?></span>
                            <div class="q-text"><?= htmlspecialchars($s['pertanyaan']) ?></div>
                            
                            <?php if($tipe == 'pilihan_ganda'): ?>
                                <div class="options-group">
                                    <?php foreach(['a','b','c','d'] as $o): ?>
                                        <?php if(!empty($s['opsi_'.$o])): ?>
                                            <label class="option-box">
                                                <input type="radio" name="ans[<?= $s['id'] ?>]" value="<?= $o ?>" style="display:none">
                                                <div class="opt-label"><?= strtoupper($o) ?></div>
                                                <span><?= htmlspecialchars($s['opsi_'.$o]) ?></span>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="essai-wrapper">
                                    <input type="text" 
                                           name="ans[<?= $s['id'] ?>]" 
                                           class="essai-input" 
                                           placeholder="Tulis jawaban lengkapmu di sini..." 
                                           autocomplete="off">
                                    <i class="fa-solid fa-feather-pointed"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:110px 20px; animation: popUp 0.6s ease-out forwards; background:var(--white); border-radius:20px; box-shadow:0 15px 35px rgba(0,0,0,0.1); border-top:10px solid var(--airport-blue);">
                        <div style="font-size: 7rem; margin-bottom: 30px;">🛬</div>
                        <h2 style="font-weight: 900; text-transform: uppercase; font-size: 2.8rem; margin:0; text-shadow:0 1px 1px var(--airport-dark);">Touchdown!</h2>
                        <p style="color:var(--text-slate); font-weight: 600; font-size: 1.3rem; margin-top:15px; text-shadow:0 1px 1px var(--white);">Kamu telah mendarat dengan selamat. Semua materi di sesi ini telah diselesaikan.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="bottom-bar">
                <button type="submit" name="action" value="lihat_nilai" class="btn-full btn-dark"><i class="fa-solid fa-chart-simple"></i> <span>Hasil</span></button>
                <a href="latihan.php?level=<?= $level ?>&tipe=<?= $tipe ?>&action=reset" class="btn-full btn-dark" onclick="return confirm('Hapus riwayat penerbangan (progres) dan mulai ulang?')"><i class="fa-solid fa-rotate-left"></i> <span>Reset</span></a>
                <button type="submit" name="action" value="lanjut" class="btn-full btn-primary"><span>Simpan & Lanjut</span> <i class="fa-solid fa-plane-departure"></i></button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    // Script interaktif untuk pilihan ganda (Original Logic dipertahankan)
    document.querySelectorAll('.option-box').forEach(box => {
        box.addEventListener('click', function() {
            const group = this.closest('.options-group');
            if(group) {
                group.querySelectorAll('.option-box').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input').checked = true;
            }
        });
    });
</script>
</body>
</html>