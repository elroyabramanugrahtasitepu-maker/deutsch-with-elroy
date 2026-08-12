<?php
session_start();

/**
 * 🔐 Proteksi Admin & Koneksi Database
 * User: El Roy Abram Anugrahta Sitepu
 */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


/* ==========================================
    1. LOGIKA PROSES (CRUD UTUH - TIDAK ADA YANG DIHAPUS)
   ========================================== */

// --- PROSES HÖREN-HEROS ---
if (isset($_POST['tambah_horen'])) {
    $pertanyaan = $conn->real_escape_string($_POST['pertanyaan_horen']);
    $instruksi  = $conn->real_escape_string($_POST['instruksi_horen']);
    $opsi_a     = $conn->real_escape_string($_POST['opsi_a']);
    $opsi_b     = $conn->real_escape_string($_POST['opsi_b']);
    $opsi_c     = $conn->real_escape_string($_POST['opsi_c']);
    $opsi_d     = $conn->real_escape_string($_POST['opsi_d']);
    $jawaban    = $conn->real_escape_string($_POST['jawaban_horen']);
    $tipe       = "pilihan_ganda";

    if (isset($_POST['id_edit_horen']) && !empty($_POST['id_edit_horen'])) {
        $id = (int)$_POST['id_edit_horen'];
        $conn->query("UPDATE latihan_horen SET pertanyaan='$pertanyaan', instruksi='$instruksi', opsi_a='$opsi_a', opsi_b='$opsi_b', opsi_c='$opsi_c', opsi_d='$opsi_d', jawaban='$jawaban' WHERE id=$id");
    } else {
        $conn->query("INSERT INTO latihan_horen (pertanyaan, instruksi, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, tipe) VALUES ('$pertanyaan', '$instruksi', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$jawaban', '$tipe')");
    }
    header("Location: admin_dashboard.php#horen-section"); exit();
}
if (isset($_GET['delete_horen'])) {
    $id = (int)$_GET['delete_horen'];
    $conn->query("DELETE FROM latihan_horen WHERE id=$id");
    header("Location: admin_dashboard.php#horen-section"); exit();
}

// --- PROSES SATZBAU-PROFI / PUZZLE ---
if (isset($_POST['tambah_puzzle'])) {
    $pertanyaan = $conn->real_escape_string($_POST['pertanyaan_puzzle']); 
    $jawaban = $conn->real_escape_string($_POST['jawaban_puzzle']);     
    
    if (isset($_POST['id_edit_puzzle']) && !empty($_POST['id_edit_puzzle'])) {
        $id = (int)$_POST['id_edit_puzzle'];
        $conn->query("UPDATE latihan_satzbau SET kata_acak='$pertanyaan', kalimat_benar='$jawaban' WHERE id=$id");
    } else {
        $conn->query("INSERT INTO latihan_satzbau (kata_acak, kalimat_benar) VALUES ('$pertanyaan', '$jawaban')");
    }
    header("Location: admin_dashboard.php#puzzle-section"); exit();
}

// --- PROSES BALAS FEEDBACK (Mendukung AJAX) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_feedback'])) {
    $fid = (int)$_POST['feedback_id'];
    $reply_text = $conn->real_escape_string($_POST['reply_text']);
    $conn->query("UPDATE feedback SET reply='$reply_text' WHERE id=$fid");
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { exit(); }
    header("Location: admin_dashboard.php#feedback-section"); exit();
}

// --- PROSES MODAL-MEISTER ---
if (isset($_POST['tambah_modal'])) {
    $pertanyaan = $conn->real_escape_string($_POST['pertanyaan_modal']);
    $opsi_a = $conn->real_escape_string($_POST['opsi_a']);
    $opsi_b = $conn->real_escape_string($_POST['opsi_b']);
    $opsi_c = $conn->real_escape_string($_POST['opsi_c']);
    $opsi_d = $conn->real_escape_string($_POST['opsi_d']);
    $jawaban = $conn->real_escape_string($_POST['jawaban_modal']);
    if (isset($_POST['id_edit_modal']) && !empty($_POST['id_edit_modal'])) {
        $id_mod = (int)$_POST['id_edit_modal'];
        $conn->query("UPDATE latihan_modalverben SET pertanyaan='$pertanyaan', opsi_a='$opsi_a', opsi_b='$opsi_b', opsi_c='$opsi_c', opsi_d='$opsi_d', jawaban='$jawaban' WHERE id=$id_mod");
    } else {
        $conn->query("INSERT INTO latihan_modalverben (pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban) VALUES ('$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$jawaban')");
    }
    header("Location: admin_dashboard.php#modal-section"); exit();
}
if (isset($_GET['delete_modal'])) {
    $id = (int)$_GET['delete_modal'];
    $conn->query("DELETE FROM latihan_modalverben WHERE id=$id");
    header("Location: admin_dashboard.php#modal-section"); exit();
}

// --- PROSES ARTIKEL ---
if (isset($_POST['tambah_artikel'])) {
    $map_id = (int)$_POST['map_id'];
    $pertanyaan = $conn->real_escape_string($_POST['pertanyaan']);
    $terjemahan = $conn->real_escape_string($_POST['terjemahan']);
    $jawaban = $conn->real_escape_string($_POST['jawaban']);
    if (isset($_POST['id_edit_artikel']) && !empty($_POST['id_edit_artikel'])) {
        $id_art = (int)$_POST['id_edit_artikel'];
        $conn->query("UPDATE latihan_artikel SET map_id=$map_id, pertanyaan='$pertanyaan', terjemahan='$terjemahan', jawaban='$jawaban' WHERE id=$id_art");
    } else {
        $conn->query("INSERT INTO latihan_artikel (map_id, pertanyaan, terjemahan, jawaban) VALUES ($map_id, '$pertanyaan', '$terjemahan', '$jawaban')");
    }
    header("Location: admin_dashboard.php#artikel-section"); exit();
}
if (isset($_GET['delete_artikel'])) {
    $id = (int)$_GET['delete_artikel'];
    $conn->query("DELETE FROM latihan_artikel WHERE id=$id");
    header("Location: admin_dashboard.php#artikel-section"); exit();
}

// --- PROSES USER, MATERI, BUKU, FEEDBACK ---
if (isset($_GET['block_user'])) {
    $uid = (int)$_GET['block_user'];
    $conn->query("UPDATE users SET is_banned = 1 WHERE id = $uid AND role != 'admin'");
    header("Location: admin_dashboard.php#user-section"); exit();
}
if (isset($_GET['unblock_user'])) {
    $uid = (int)$_GET['unblock_user'];
    $conn->query("UPDATE users SET is_banned = 0 WHERE id = $uid");
    header("Location: admin_dashboard.php#user-section"); exit();
}
if (isset($_GET['delete_story'])) {
    $id = (int)$_GET['delete_story'];
    $conn->query("DELETE FROM stories WHERE id=$id");
    header("Location: admin_dashboard.php#shelf"); exit();
}
if (isset($_POST['tambah_materi'])) {
    $judul = $conn->real_escape_string($_POST['judul']);
    $level = $conn->real_escape_string($_POST['level']);
    $desc = $conn->real_escape_string($_POST['deskripsi']);
    $icon = $conn->real_escape_string($_POST['icon']);
    $conn->query("INSERT INTO materi (judul, deskripsi, level, icon) VALUES ('$judul', '$desc', '$level', '$icon')");
    header("Location: admin_dashboard.php#materi-section"); exit();
}
if (isset($_GET['delete_materi'])) {
    $id = (int)$_GET['delete_materi'];
    $conn->query("DELETE FROM materi WHERE id=$id");
    header("Location: admin_dashboard.php#materi-section"); exit();
}

// Hapus Feedback AJAX Support
if (isset($_GET['delete_feedback'])) {
    $id = (int)$_GET['delete_feedback'];
    $conn->query("DELETE FROM feedback WHERE id=$id");
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') { 
        echo "success"; 
        exit(); 
    }
    header("Location: admin_dashboard.php#feedback-section"); 
    exit();
}

/* ==========================================
    2. PENGAMBILAN DATA (STATISTIK UTUH)
   ========================================== */
$totalBooks = $conn->query("SELECT COUNT(*) as total FROM stories")->fetch_assoc()['total'];
$materi_list = $conn->query("SELECT * FROM materi ORDER BY level ASC, id DESC");
$user_list = $conn->query("SELECT id, nama, role, is_banned FROM users ORDER BY role ASC, nama ASC");
$count_users = $user_list->num_rows;
$artikel_list = $conn->query("SELECT * FROM latihan_artikel ORDER BY map_id ASC, id ASC");
$count_artikel = $artikel_list->num_rows;
$modal_list = $conn->query("SELECT * FROM latihan_modalverben ORDER BY id DESC");
$count_modal = $modal_list->num_rows;
$puzzle_list = $conn->query("SELECT * FROM latihan_satzbau ORDER BY id DESC");
$count_puzzle = ($puzzle_list) ? $puzzle_list->num_rows : 0;
$horen_list = $conn->query("SELECT * FROM latihan_horen ORDER BY id DESC");
$count_horen = ($horen_list) ? $horen_list->num_rows : 0;
$stories_display = $conn->query("SELECT * FROM stories ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduventure Hub | Global Admin</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Warna Universal / Global Hub */
            --primary: #0ea5e9;      /* Light Ocean Blue */
            --primary-hover: #0284c7;
            --accent: #f59e0b;       /* Global Gold */
            
            /* UI Colors */
            --bg-body: #f8fafc;      /* Light airy background */
            --card-bg: #ffffff; 
            --border-color: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
            --sidebar-bg: #ffffff;   /* Sidebar terang/bersih */
            
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 10px;
            
            --shadow-soft: 0 10px 25px -5px rgba(0,0,0,0.03), 0 8px 10px -6px rgba(0,0,0,0.01);
            --shadow-hover: 0 20px 25px -5px rgba(0,0,0,0.06), 0 8px 10px -6px rgba(0,0,0,0.02);
            
            --success: #10b981; 
            --danger: #ef4444;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; scroll-behavior: smooth; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; background: var(--bg-body); display: flex; 
            color: var(--text-main); overflow-x: hidden; 
        }

        /* --- GLOBAL FLAG TICKER (GAYA GANTUNG / PENNANT) --- */
        .global-ticker {
            position: fixed; top: 0; left: 0; width: 100%; height: 75px; 
            background: #ffffff; z-index: 2000; display: flex; align-items: flex-start; 
            overflow: hidden; border-bottom: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        
        /* Tiang horizontal tempat bendera digantung */
        .global-ticker::before {
            content: '';
            position: absolute; top: 0; left: 0; width: 100%; height: 12px;
            background: #475569; /* Slate dark pole */
            border-bottom: 2px solid #1e293b;
            z-index: 10;
        }

        .ticker-content {
            display: flex; gap: 20px; align-items: flex-start;
            animation: scrollTicker 40s linear infinite;
            white-space: nowrap; padding-left: 20px;
            padding-top: 10px; /* Jarak dari tiang */
        }
        
        .flag-wrapper {
            position: relative;
            transform-origin: top center;
            animation: swing-flag 3s ease-in-out infinite alternate;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
            z-index: 5;
        }
        
        /* Variasi ayunan */
        .flag-wrapper:nth-child(odd) { animation-duration: 3.5s; animation-direction: alternate-reverse; }
        .flag-wrapper:nth-child(even) { animation-duration: 2.8s; }

        .flag-icon {
            width: 40px; height: 55px; object-fit: cover;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%);
            border: 2px solid #e2e8f0; border-top: none;
            display: block; background: #fff;
        }

        @keyframes scrollTicker {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        @keyframes swing-flag {
            0% { transform: rotate(6deg); }
            100% { transform: rotate(-6deg); }
        }

        /* --- ANIMASI SANTAI --- */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .anim { opacity: 0; animation: fadeUp 0.6s ease-out forwards; }
        .d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; }
        .d-3 { animation-delay: 0.3s; } .d-4 { animation-delay: 0.4s; }

        /* --- SIDEBAR ELEGAN BERSIIH --- */
        .sidebar { 
            width: 280px; height: calc(100vh - 75px); background: var(--sidebar-bg); color: var(--text-main); 
            position: fixed; top: 75px; z-index: 1000; padding: 30px 20px; display: flex; flex-direction: column; 
            border-right: 1px solid var(--border-color);
        }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; padding: 0 15px; margin-bottom: 40px; }
        .sidebar-brand img { width: 38px; height: auto; }
        .sidebar-brand span { font-weight: 800; font-size: 1.25rem; color: #1e293b; letter-spacing: -0.5px; }

        .sidebar-nav { flex-grow: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { 
            display: flex; align-items: center; gap: 15px; padding: 14px 20px; 
            color: var(--text-muted); text-decoration: none; border-radius: var(--radius-sm); 
            font-size: 0.95rem; font-weight: 600; transition: var(--transition);
        }
        .nav-item i { width: 20px; text-align: center; font-size: 1.2rem; }
        .nav-item:hover { background: #f8fafc; color: var(--primary); transform: translateX(5px); }
        .nav-item.active { 
            background: #f0f9ff; color: var(--primary); font-weight: 700;
        }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 280px; margin-top: 75px; width: calc(100% - 280px); padding: 40px 50px; }
        
        .page-header { margin-bottom: 40px; display: flex; align-items: center; gap: 20px;}
        .header-icon { width: 55px; height: 55px; background: #fff; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--primary); box-shadow: var(--shadow-soft); }
        .page-header h1 { font-size: 2rem; margin: 0 0 5px 0; color: #1e293b; font-weight: 800; letter-spacing: -1px; }
        .page-header p { margin: 0; color: var(--text-muted); font-size: 1rem; font-weight: 500;}

        /* --- STATS GRID (SOFT & CLEAN) --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-bottom: 50px; }
        .stat-card { 
            background: var(--card-bg); padding: 25px 20px; border-radius: var(--radius-lg); 
            text-align: center; transition: var(--transition); box-shadow: var(--shadow-soft);
            border: 1px solid rgba(0,0,0,0.02);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
        
        .stat-icon { width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.3rem; }
        .stat-card h4 { margin: 0; font-size: 1.8rem; font-weight: 800; color: #1e293b; }
        .stat-card span { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-top: 5px; }

        /* --- SECTION LAYOUT (CLEAN) --- */
        .admin-section { 
            display: grid; grid-template-columns: 380px 1fr; gap: 30px; 
            margin-bottom: 50px; 
        }
        .card-form { 
            background: var(--card-bg); padding: 35px; border-radius: var(--radius-lg); 
            height: fit-content; position: sticky; top: 100px; box-shadow: var(--shadow-soft);
        }
        .card-data { 
            background: var(--card-bg); border-radius: var(--radius-lg); overflow: hidden; 
            display: flex; flex-direction: column; box-shadow: var(--shadow-soft);
        }
        
        .card-header { 
            padding: 25px 30px; background: #fff; border-bottom: 1px solid var(--border-color); 
            font-weight: 800; font-size: 1.1rem; color: #1e293b;
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }

        /* --- FORM STYLING --- */
        .card-form h3 { margin-top: 0; margin-bottom: 25px; font-size: 1.2rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        label { display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 8px; color: var(--text-muted); }
        .control { 
            width: 100%; padding: 14px 18px; border: 1.5px solid var(--border-color); 
            border-radius: var(--radius-md); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; 
            font-weight: 500; margin-bottom: 20px; transition: var(--transition); background: #f8fafc; color: var(--text-main);
        }
        .control:focus { outline: none; background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1); }
        
        .btn-primary { 
            background: var(--primary); color: #fff; border: none; 
            padding: 16px; width: 100%; font-weight: 700; font-size: 0.95rem; border-radius: var(--radius-md);
            cursor: pointer; transition: var(--transition); box-shadow: 0 4px 6px rgba(2, 132, 199, 0.2);
        }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 8px 15px rgba(2, 132, 199, 0.3); }
        .btn-edit-mode { background: var(--success) !important; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2) !important; }
        .btn-edit-mode:hover { filter: brightness(1.05); }

        /* --- TABEL CLEAN --- */
        .scroll-area { max-height: 500px; overflow-y: auto; padding: 0 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 18px 20px; background: #fff; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid var(--border-color); }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; font-weight: 500; color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        .btn-action { 
            color: var(--text-muted); width: 35px; height: 35px; border-radius: 10px; background: #f1f5f9;
            transition: var(--transition); text-decoration: none; border: none; 
            cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem;
            margin-left: 5px;
        }
        .btn-action:hover { background: #e2e8f0; color: var(--primary); transform: translateY(-2px); }
        .btn-action-danger:hover { background: #fee2e2; color: var(--danger); }

        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: var(--text-main); }

        /* --- GRID BAWAH --- */
        .master-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 30px; margin-top: 40px; 
        }

        /* --- SCROLLBAR ELEGAN --- */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        @media (max-width: 1300px) { .admin-section { grid-template-columns: 320px 1fr; gap: 20px; } }
        @media (max-width: 1000px) { .admin-section { grid-template-columns: 1fr; } .card-form { position: relative; top: 0; } }
    </style>
</head>
<body>

<div class="global-ticker">
    <div class="ticker-content">
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/id.png" alt="ID" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/de.png" alt="DE" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/fr.png" alt="FR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/nl.png" alt="NL" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/sg.png" alt="SG" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/gb.png" alt="GB" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/us.png" alt="US" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/jp.png" alt="JP" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/kr.png" alt="KR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/au.png" alt="AU" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/ca.png" alt="CA" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/it.png" alt="IT" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/es.png" alt="ES" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/br.png" alt="BR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/ch.png" alt="CH" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/se.png" alt="SE" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/tr.png" alt="TR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/in.png" alt="IN" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/za.png" alt="ZA" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/mx.png" alt="MX" class="flag-icon"></div>
        
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/id.png" alt="ID" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/de.png" alt="DE" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/fr.png" alt="FR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/nl.png" alt="NL" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/sg.png" alt="SG" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/gb.png" alt="GB" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/us.png" alt="US" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/jp.png" alt="JP" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/kr.png" alt="KR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/au.png" alt="AU" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/ca.png" alt="CA" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/it.png" alt="IT" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/es.png" alt="ES" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/br.png" alt="BR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/ch.png" alt="CH" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/se.png" alt="SE" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/tr.png" alt="TR" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/in.png" alt="IN" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/za.png" alt="ZA" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/mx.png" alt="MX" class="flag-icon"></div>
    </div>
</div>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="logo_website/gambar.1.png" alt="Logo">
        <span>EDUVENTURE HUB</span>
    </div>
    <div class="sidebar-nav">
        <a href="#stats" class="nav-item active"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a>
        <a href="#horen-section" class="nav-item"><i class="fa-solid fa-headphones"></i> <span>Audio Mastery</span></a>
        <a href="index.php" class="nav-item"><i class="fa-solid fa-globe"></i> <span>Live Platform</span></a>
        <a href="tambah_user.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>Global Users</span></a>
        <a href="tambah_stories.php" class="nav-item"><i class="fa-solid fa-book"></i> <span>Library</span></a>
        <a href="admin_english.php" class="nav-item"><i class="fa-solid fa-book-open-reader"></i> <span>English Village</span></a>
    </div>
    <div style="margin-top: auto; padding-top: 25px; border-top: 1px solid var(--border-color);">
        <a href="logout.php" class="nav-item" style="color: #ef4444; background: #fef2f2;"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Secure Logout</span></a>
    </div>
</aside>

<main class="main-content">
    <header class="page-header anim d-1" id="stats">
        <div class="header-icon"><i class="fa-solid fa-earth-americas"></i></div>
        <div>
            <h1>Global Command Center</h1>
            <p>Selamat datang El Roy, pantau aktivitas Eduventure di seluruh dunia.</p>
        </div>
    </header>

    <div class="stats-grid anim d-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fa-solid fa-book-open"></i></div>
            <h4><?= $totalBooks ?></h4><span>Stories</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7; color:#10b981;"><i class="fa-solid fa-layer-group"></i></div>
            <h4><?= $materi_list->num_rows ?></h4><span>Modules</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;"><i class="fa-solid fa-headphones"></i></div>
            <h4><?= $count_horen ?></h4><span>Audio Logs</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-map-location-dot"></i></div>
            <h4><?= $count_artikel ?></h4><span>Articles</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fce7f3; color:#db2777;"><i class="fa-solid fa-lightbulb"></i></div>
            <h4><?= $count_modal ?></h4><span>Modals</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#ccfbf1; color:#0891b2;"><i class="fa-solid fa-puzzle-piece"></i></div>
            <h4><?= $count_puzzle ?></h4><span>Puzzles</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f1f5f9; color:#475569;"><i class="fa-solid fa-users"></i></div>
            <h4><?= $count_users ?></h4><span>Global Members</span>
        </div>
    </div>

    <div class="admin-section anim d-3" id="horen-section">
        <div class="card-form">
            <h3 id="hor-title"><i class="fa-solid fa-headphones" style="color: var(--accent);"></i> Audio Editor</h3>
            <form action="" method="POST" id="formHor">
                <input type="hidden" name="id_edit_horen" id="id_hor">
                <label>Teks / Transkrip</label>
                <textarea name="pertanyaan_horen" id="q_hor" class="control" style="height:100px; resize:vertical;" placeholder="Teks yang didengar..." required></textarea>
                <label>Instruksi Soal</label>
                <input type="text" name="instruksi_horen" id="i_hor" class="control" placeholder="Contoh: Was macht Markus?" required>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div><label>Opsi A</label><input type="text" name="opsi_a" id="oa_hor" class="control" required></div>
                    <div><label>Opsi B</label><input type="text" name="opsi_b" id="ob_hor" class="control" required></div>
                    <div><label>Opsi C</label><input type="text" name="opsi_c" id="oc_hor" class="control"></div>
                    <div><label>Opsi D</label><input type="text" name="opsi_d" id="od_hor" class="control"></div>
                </div>
                <label>Kunci Jawaban</label>
                <select name="jawaban_horen" id="a_hor" class="control">
                    <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                </select>
                <button type="submit" name="tambah_horen" id="btn-hor-submit" class="btn-primary">Publish Data</button>
                <button type="button" onclick="location.reload()" style="width:100%; border:none; background:transparent; padding:12px; margin-top:10px; font-weight:700; cursor:pointer; color:var(--text-muted); transition:0.2s;">Batal / Reset</button>
            </form>
        </div>
        <div class="card-data">
            <div class="card-header">Audio Log List</div>
            <div class="scroll-area">
                <table>
                    <thead><tr><th>Instruksi</th><th>Opsi</th><th>Kunci</th><th align="center">Aksi</th></tr></thead>
                    <tbody>
                        <?php while($h = $horen_list->fetch_assoc()): ?>
                        <tr>
                            <td><b><?= $h['instruksi'] ?></b></td>
                            <td><span style="color:var(--text-muted); font-size:0.85rem;"><?= $h['opsi_a'] ?> / <?= $h['opsi_b'] ?></span></td>
                            <td><span class="badge" style="background:#fef3c7; color:#b45309;"><?= strtoupper($h['jawaban']) ?></span></td>
                            <td align="center" style="white-space:nowrap;">
                                <button onclick='editHor(<?= json_encode($h) ?>)' class="btn-action"><i class="fa-solid fa-pen"></i></button>
                                <a href="?delete_horen=<?= $h['id'] ?>" class="btn-action btn-action-danger" onclick="return confirm('Yakin hapus soal ini?')"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-section anim d-4" id="materi-section">
        <div class="card-form">
            <h3><i class="fa-solid fa-layer-group" style="color: var(--success);"></i> Learning Modules</h3>
            <form action="" method="POST">
                <label>Judul Materi</label>
                <input type="text" name="judul" class="control" placeholder="E.g. Konjugation" required>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div><label>Level</label><select name="level" class="control"><option>A1</option><option>A2</option><option>B1</option></select></div>
                    <div><label>Icon Class</label><input type="text" name="icon" class="control" value="fa-solid fa-book"></div>
                </div>
                <label>Deskripsi Singkat</label>
                <textarea name="deskripsi" class="control" style="height:110px; resize:none;" placeholder="Tulis deskripsi..." required></textarea>
                <button type="submit" name="tambah_materi" class="btn-primary">Tambah Modul</button>
            </form>
        </div>
        <div class="card-data">
            <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 15px;">
                <div>Daftar Modul Belajar</div>
                <div style="display:flex; gap:10px; width: 100%; background: #f8fafc; padding: 5px; border-radius: 12px;">
                    <button onclick="filterLevel('A1')" id="btn-A1" style="flex:1; padding:8px; border-radius:8px; border:none; cursor:pointer; font-weight:700; background:#fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition:0.2s;">A1</button>
                    <button onclick="filterLevel('A2')" id="btn-A2" style="flex:1; padding:8px; border-radius:8px; border:none; cursor:pointer; font-weight:700; background:transparent; color: var(--text-muted); transition:0.2s;">A2</button>
                    <button onclick="filterLevel('B1')" id="btn-B1" style="flex:1; padding:8px; border-radius:8px; border:none; cursor:pointer; font-weight:700; background:transparent; color: var(--text-muted); transition:0.2s;">B1</button>
                </div>
            </div>
            <div class="scroll-area">
                <table>
                    <?php $materi_list->data_seek(0); while($m = $materi_list->fetch_assoc()): ?>
                    <tr class="materi-row level-<?= $m['level'] ?>">
                        <td><b style="font-size:1.05rem;"><?= $m['judul'] ?></b><br><span style="color:var(--text-muted); font-size:0.85rem;"><?= substr($m['deskripsi'],0,50) ?>...</span></td>
                        <td align="right" style="white-space: nowrap;">
                            <a href="edit_materi.php?id=<?= $m['id'] ?>" class="btn-action"><i class="fa-solid fa-pen"></i></a>
                            <a href="?delete_materi=<?= $m['id'] ?>" class="btn-action btn-action-danger" onclick="return confirm('Hapus modul?')"><i class="fa-solid fa-trash-can"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-section anim d-4" id="artikel-section">
        <div class="card-form">
            <h3 id="art-title"><i class="fa-solid fa-map-location-dot" style="color: var(--primary);"></i> Dictionary DB</h3>
            <form action="" method="POST" id="formArt">
                <input type="hidden" name="id_edit_artikel" id="id_art">
                <div style="display:grid; grid-template-columns:100px 1fr; gap:15px;">
                    <div><label>Map Unit</label><input type="number" name="map_id" id="map_art" class="control" required></div>
                    <div><label>Kata Benda (Nomen)</label><input type="text" name="pertanyaan" id="q_art" class="control" required></div>
                </div>
                <label>Terjemahan Indonesia</label><input type="text" name="terjemahan" id="t_art" class="control" required>
                <label>Artikel Benar</label>
                <select name="jawaban" id="a_art" class="control">
                    <option value="a">DER (Maskulin)</option>
                    <option value="b">DIE (Feminim / Plural)</option>
                    <option value="c">DAS (Neutral)</option>
                </select>
                <button type="submit" name="tambah_artikel" id="btn-art-submit" class="btn-primary">Update Database</button>
                <button type="button" onclick="location.reload()" style="width:100%; border:none; background:transparent; padding:12px; margin-top:10px; font-weight:700; cursor:pointer; color:var(--text-muted);">Batal / Reset</button>
            </form>
        </div>
        <div class="card-data">
            <div class="card-header">Dictionary Records</div>
            <div class="scroll-area">
                <table>
                    <thead><tr><th>Unit</th><th>Nomen</th><th>Artikel</th><th align="center">Aksi</th></tr></thead>
                    <tbody>
                        <?php while($art = $artikel_list->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge">Map <?= $art['map_id'] ?></span></td>
                            <td><b><?= $art['pertanyaan'] ?></b><br><span style="font-size:0.85rem; color:var(--text-muted);"><?= $art['terjemahan'] ?></span></td>
                            <td>
                                <?php 
                                    $bg = $art['jawaban'] == 'a' ? '#dbeafe' : ($art['jawaban'] == 'b' ? '#fee2e2' : '#dcfce7');
                                    $col = $art['jawaban'] == 'a' ? '#2563eb' : ($art['jawaban'] == 'b' ? '#dc2626' : '#059669');
                                ?>
                                <span class="badge" style="background:<?= $bg ?>; color:<?= $col ?>;"><?= strtoupper($art['jawaban']) ?></span>
                            </td>
                            <td align="center" style="white-space: nowrap;">
                                <button onclick='editArt(<?= json_encode($art) ?>)' class="btn-action"><i class="fa-solid fa-pen"></i></button>
                                <a href="?delete_artikel=<?= $art['id'] ?>" class="btn-action btn-action-danger" onclick="return confirm('Hapus data?')"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-section anim d-4" id="modal-section">
        <div class="card-form">
            <h3 id="mod-title"><i class="fa-solid fa-lightbulb" style="color: #ec4899;"></i> Grammar Engine</h3>
            <form action="" method="POST" id="formModal">
                <input type="hidden" name="id_edit_modal" id="id_mod">
                <label>Soal Rumpang (Gunakan ___)</label>
                <textarea name="pertanyaan_modal" id="q_mod" class="control" style="height:90px; resize:vertical;" placeholder="Ich ___ Deutsch." required></textarea>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div><label>Opsi A</label><input type="text" name="opsi_a" id="o_a" class="control" required></div>
                    <div><label>Opsi B</label><input type="text" name="opsi_b" id="o_b" class="control" required></div>
                    <div><label>Opsi C</label><input type="text" name="opsi_c" id="o_c" class="control"></div>
                    <div><label>Opsi D</label><input type="text" name="opsi_d" id="o_d" class="control"></div>
                </div>
                <label>Kunci Jawaban</label>
                <select name="jawaban_modal" id="a_mod" class="control"><option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option></select>
                <button type="submit" name="tambah_modal" id="btn-mod-submit" class="btn-primary">Simpan Latihan</button>
                <button type="button" onclick="location.reload()" style="width:100%; border:none; background:transparent; padding:12px; margin-top:10px; font-weight:700; cursor:pointer; color:var(--text-muted);">Batal / Reset</button>
            </form>
        </div>
        <div class="card-data">
            <div class="card-header">Grammar Questions</div>
            <div class="scroll-area">
                <table>
                    <thead><tr><th>Pertanyaan</th><th>Kunci</th><th align="center">Aksi</th></tr></thead>
                    <tbody>
                        <?php while($mod = $modal_list->fetch_assoc()): ?>
                        <tr>
                            <td><b style="font-size:0.95rem;"><?= substr($mod['pertanyaan'],0,50) ?>...</b></td>
                            <td><span class="badge" style="background:#fce7f3; color:#db2777;"><?= strtoupper($mod['jawaban']) ?></span></td>
                            <td align="center" style="white-space: nowrap;">
                                <button onclick='editModal(<?= json_encode($mod) ?>)' class="btn-action"><i class="fa-solid fa-pen"></i></button>
                                <a href="?delete_modal=<?= $mod['id'] ?>" class="btn-action btn-action-danger" onclick="return confirm('Hapus latihan?')"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-section anim d-4" id="puzzle-section">
        <div class="card-form">
            <h3 id="puz-title"><i class="fa-solid fa-puzzle-piece" style="color: #06b6d4;"></i> Syntax Builder</h3>
            <form action="" method="POST" id="formPuz">
                <input type="hidden" name="id_edit_puzzle" id="id_puz">
                <label>Kata Acak (Pemisah /)</label>
                <input type="text" name="pertanyaan_puzzle" id="q_puz" class="control" placeholder="Ich / lerne / Deutsch" required>
                <label>Kalimat Benar</label>
                <textarea name="jawaban_puzzle" id="a_puz" class="control" style="height:100px; resize:none;" placeholder="Ich lerne Deutsch" required></textarea>
                <button type="submit" name="tambah_puzzle" id="btn-puz-submit" class="btn-primary">Simpan Puzzle</button>
                <button type="button" onclick="location.reload()" style="width:100%; border:none; background:transparent; padding:12px; margin-top:10px; font-weight:700; cursor:pointer; color:var(--text-muted);">Batal / Reset</button>
            </form>
        </div>
        <div class="card-data">
            <div class="card-header">Syntax Logic</div>
            <div class="scroll-area">
                <table>
                    <thead><tr><th>Kalimat & Acak</th><th align="center">Aksi</th></tr></thead>
                    <tbody>
                        <?php if($puzzle_list): while($p = $puzzle_list->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <b><?= $p['kalimat_benar'] ?></b><br>
                                <span style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-shuffle"></i> <?= $p['kata_acak'] ?></span>
                            </td>
                            <td align="center" style="white-space: nowrap;">
                                <button onclick='editPuz(<?= json_encode(["id"=>$p["id"], "pertanyaan"=>$p["kata_acak"], "jawaban"=>$p["kalimat_benar"]]) ?>)' class="btn-action"><i class="fa-solid fa-pen"></i></button>
                                <a href="?delete_puzzle=<?= $p['id'] ?>" class="btn-action btn-action-danger" onclick="return confirm('Hapus puzzle?')"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="master-grid anim d-4">
        
        <div class="card-data" id="user-section">
            <div class="card-header"><div><i class="fa-solid fa-users" style="color:#64748b; margin-right:8px;"></i> Security Control</div></div>
            <div class="scroll-area" style="max-height:350px;">
                <table>
                    <?php while($u = $user_list->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <b><?= htmlspecialchars($u['nama']) ?></b><br>
                            <span class="badge" style="background:<?= $u['role']=='admin'?'#fef3c7':'#f1f5f9' ?>; color:<?= $u['role']=='admin'?'#b45309':'#64748b' ?>; margin-top:5px; display:inline-block; font-size:0.65rem;">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td align="right">
                            <?php if($u['role']!=='admin'): ?>
                                <a href="?<?= $u['is_banned']?'unblock_user':'block_user' ?>=<?= $u['id'] ?>" 
                                   class="badge" 
                                   style="text-decoration:none; background:<?= $u['is_banned']?'#dcfce7':'#fee2e2' ?>; color:<?= $u['is_banned']?'var(--success)':'var(--danger)' ?>;">
                                   <i class="fa-solid <?= $u['is_banned']?'fa-unlock':'fa-lock' ?>"></i> <?= $u['is_banned']?'Aktifkan':'Blokir' ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <div class="card-data" id="feedback-section">
            <div class="card-header">
                <div><i class="fa-solid fa-comments" style="color:var(--primary); margin-right:8px;"></i> Incoming Feedback</div>
                <i class="fa-solid fa-circle-notch fa-spin" id="fb-loader" style="display:none; color:var(--text-muted);"></i>
            </div>
            <div class="scroll-area" id="feedback-list-container" style="max-height:350px; background:#f8fafc; padding: 15px;">
                <div style="display:flex; justify-content:center; align-items:center; height:100%; color:var(--text-muted);">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;"></i> Loading data...
                </div>
            </div>
        </div>

        <div class="card-data" id="shelf">
            <div class="card-header"><div><i class="fa-solid fa-book" style="color:#0284c7; margin-right:8px;"></i> Library Records</div></div>
            <div class="scroll-area" style="max-height:350px;">
                <table>
                    <?php while($row = $stories_display->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:600;"><?= substr($row['title'],0,30) ?>...</td>
                        <td align="right" style="white-space: nowrap;">
                            <a href="edit_buku.php?id=<?= $row['id'] ?>" class="btn-action"><i class="fa-solid fa-pen"></i></a>
                            <a href="?delete_story=<?= $row['id'] ?>" class="btn-action btn-action-danger" onclick="return confirm('Hapus buku?')"><i class="fa-solid fa-trash-can"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        
    </div>
</main>

<script>
    // UX: Smooth Redirect Clean up
    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('delete_materi') || url.searchParams.has('delete_artikel') || url.searchParams.has('delete_modal') || url.searchParams.has('delete_puzzle') || url.searchParams.has('delete_horen') || url.searchParams.has('block_user') || url.searchParams.has('unblock_user') || url.searchParams.has('delete_feedback')) {
            url.search = '';
            window.history.replaceState({path: url.toString()}, '', url.toString());
        }
    }

    // Filter Materi Level 
    function filterLevel(lvl) {
        document.querySelectorAll('.materi-row').forEach(r => r.style.display = 'none');
        document.querySelectorAll('.level-'+lvl).forEach(r => r.style.display = 'table-row');
        document.querySelectorAll('[id^="btn-"]').forEach(b => {
            b.style.background = 'transparent'; b.style.color = 'var(--text-muted)'; b.style.boxShadow = 'none';
        });
        const active = document.getElementById('btn-'+lvl);
        active.style.background = '#fff'; active.style.color = '#1e293b'; active.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
    }

    // CRUD: Edit Form Fillers
    function editHor(d) {
        document.getElementById('id_hor').value = d.id;
        document.getElementById('q_hor').value = d.pertanyaan;
        document.getElementById('i_hor').value = d.instruksi;
        document.getElementById('oa_hor').value = d.opsi_a;
        document.getElementById('ob_hor').value = d.opsi_b;
        document.getElementById('oc_hor').value = d.opsi_c;
        document.getElementById('od_hor').value = d.opsi_d;
        document.getElementById('a_hor').value = d.jawaban;
        document.getElementById('hor-title').innerHTML = "<i class='fa-solid fa-pen text-success'></i> Edit Soal Hören";
        document.getElementById('btn-hor-submit').classList.add('btn-edit-mode');
        document.getElementById('btn-hor-submit').innerText = 'Update Data';
        document.getElementById('horen-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function editArt(d) {
        document.getElementById('id_art').value=d.id; document.getElementById('map_art').value=d.map_id; 
        document.getElementById('q_art').value=d.pertanyaan; document.getElementById('t_art').value=d.terjemahan; 
        document.getElementById('a_art').value=d.jawaban; 
        document.getElementById('art-title').innerHTML = "<i class='fa-solid fa-pen text-success'></i> Edit Artikel";
        document.getElementById('btn-art-submit').classList.add('btn-edit-mode');
        document.getElementById('btn-art-submit').innerText = 'Update Data';
        document.getElementById('artikel-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function editModal(d) {
        document.getElementById('id_mod').value=d.id; document.getElementById('q_mod').value=d.pertanyaan;
        document.getElementById('o_a').value=d.opsi_a; document.getElementById('o_b').value=d.opsi_b;
        document.getElementById('o_c').value=d.opsi_c; document.getElementById('o_d').value=d.opsi_d;
        document.getElementById('a_mod').value=d.jawaban; 
        document.getElementById('mod-title').innerHTML = "<i class='fa-solid fa-pen text-success'></i> Edit Latihan Modal";
        document.getElementById('btn-mod-submit').classList.add('btn-edit-mode');
        document.getElementById('btn-mod-submit').innerText = 'Update Data';
        document.getElementById('modal-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function editPuz(d) {
        document.getElementById('id_puz').value=d.id; document.getElementById('q_puz').value=d.pertanyaan;
        document.getElementById('a_puz').value=d.jawaban; 
        document.getElementById('puz-title').innerHTML = "<i class='fa-solid fa-pen text-success'></i> Edit Puzzle";
        document.getElementById('btn-puz-submit').classList.add('btn-edit-mode');
        document.getElementById('btn-puz-submit').innerText = 'Update Data';
        document.getElementById('puzzle-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Hapus Feedback Tanpa Reload
    function deleteFeedbackAjax(id) {
        if(confirm('Hapus masukan dari user ini?')) {
            const loader = document.getElementById('fb-loader');
            loader.style.display = 'inline-block';
            
            fetch('admin_dashboard.php?delete_feedback=' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(data => { loadFeedback(); })
            .catch(error => {
                alert('Gagal terkoneksi ke server.');
                loader.style.display = 'none';
            });
        }
    }

    // Load Data Feedback Ajax
    function loadFeedback() {
        const container = document.getElementById('feedback-list-container');
        const loader = document.getElementById('fb-loader');
        if(loader) loader.style.display = 'inline-block';
        
        fetch('ambil_feedback.php?t='+new Date().getTime())
        .then(r=>r.text()).then(h=>{
            container.innerHTML = h;
            if(loader) loader.style.display = 'none';
        }).catch(() => {
            if(loader) loader.style.display = 'none';
        });
    }

    window.onload = () => {
        loadFeedback();
        filterLevel('A1');
    };
    setInterval(loadFeedback, 15000);
</script>

</body>
</html>
