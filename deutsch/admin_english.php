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
    Fungsi Pelacak Error Database
   ========================================== */
function jalankanQuery($conn, $sql, $redirect_url) {
    if ($conn->query($sql) === TRUE) {
        header("Location: " . $redirect_url);
        exit();
    } else {
        $error_msg = addslashes($conn->error);
        echo "<script>
            alert('❌ DATABASE ERROR:\\n\\n{$error_msg}\\n\\nMasalah ini terjadi karena nama kolom di kode PHP berbeda dengan nama kolom di PHPMyAdmin. Silakan samakan namanya!');
            window.history.back();
        </script>";
        exit();
    }
}

/* ==========================================
    1. LOGIKA PROSES CRUD BAHASA INGGRIS (STORIES SAJA)
   ========================================== */
if (isset($_POST['tambah_english'])) {
    $title = $conn->real_escape_string($_POST['title_en']);
    $content = $conn->real_escape_string($_POST['content_en']);
    $cat = $conn->real_escape_string($_POST['cat_en']);
    $q1 = $conn->real_escape_string($_POST['q1_en']);
    $q2 = $conn->real_escape_string($_POST['q2_en']);
    $a1 = $conn->real_escape_string($_POST['a1_en']);
    $a2 = $conn->real_escape_string($_POST['a2_en']);

    if (isset($_POST['id_edit_english']) && !empty($_POST['id_edit_english'])) {
        $id = (int)$_POST['id_edit_english'];
        $sql = "UPDATE stories_en SET title='$title', content='$content', category='$cat', question_1='$q1', question_2='$q2', answer_1='$a1', answer_2='$a2' WHERE id=$id";
    } else {
        $sql = "INSERT INTO stories_en (title, content, category, question_1, question_2, answer_1, answer_2) VALUES ('$title', '$content', '$cat', '$q1', '$q2', '$a1', '$a2')";
    }
    jalankanQuery($conn, $sql, "admin_english.php");
}

if (isset($_GET['delete_english'])) {
    $id = (int)$_GET['delete_english'];
    jalankanQuery($conn, "DELETE FROM stories_en WHERE id=$id", "admin_english.php");
}

/* ==========================================
    2. PENGAMBILAN DATA
   ========================================== */
$english_list = @$conn->query("SELECT * FROM stories_en ORDER BY id DESC");
$count_english = ($english_list) ? $english_list->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Admin | Eduventure Hub</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --accent: #be185d; 
            --bg-body: #f8fafc;
            --card-bg: #ffffff; 
            --border-color: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
            --sidebar-bg: #ffffff;
            
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 10px;
            
            --shadow-soft: 0 10px 25px -5px rgba(0,0,0,0.03), 0 8px 10px -6px rgba(0,0,0,0.01);
            --success: #10b981; 
            --danger: #ef4444;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; scroll-behavior: smooth; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; background: var(--bg-body); display: flex; color: var(--text-main); overflow-x: hidden; }

        /* --- GLOBAL FLAG TICKER --- */
        .global-ticker { position: fixed; top: 0; left: 0; width: 100%; height: 75px; background: #ffffff; z-index: 2000; display: flex; align-items: flex-start; overflow: hidden; border-bottom: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .global-ticker::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 12px; background: #475569; border-bottom: 2px solid #1e293b; z-index: 10; }
        .ticker-content { display: flex; gap: 20px; align-items: flex-start; animation: scrollTicker 40s linear infinite; white-space: nowrap; padding-left: 20px; padding-top: 10px; }
        .flag-wrapper { position: relative; transform-origin: top center; animation: swing-flag 3s ease-in-out infinite alternate; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08)); z-index: 5; }
        .flag-wrapper:nth-child(odd) { animation-duration: 3.5s; animation-direction: alternate-reverse; }
        .flag-wrapper:nth-child(even) { animation-duration: 2.8s; }
        .flag-icon { width: 40px; height: 55px; object-fit: cover; clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%); border: 2px solid #e2e8f0; border-top: none; display: block; background: #fff; }
        
        @keyframes scrollTicker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes swing-flag { 0% { transform: rotate(6deg); } 100% { transform: rotate(-6deg); } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .anim { opacity: 0; animation: fadeUp 0.6s ease-out forwards; }
        .d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; } .d-3 { animation-delay: 0.3s; }

        /* --- SIDEBAR --- */
        .sidebar { width: 280px; height: calc(100vh - 75px); background: var(--sidebar-bg); color: var(--text-main); position: fixed; top: 75px; z-index: 1000; padding: 30px 20px; display: flex; flex-direction: column; border-right: 1px solid var(--border-color); }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; padding: 0 15px; margin-bottom: 40px; }
        .sidebar-brand img { width: 38px; height: auto; }
        .sidebar-brand span { font-weight: 800; font-size: 1.25rem; color: #1e293b; letter-spacing: -0.5px; }
        .sidebar-nav { flex-grow: 1; display: flex; flex-direction: column; gap: 8px; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-sm); font-size: 0.95rem; font-weight: 600; transition: var(--transition); }
        .nav-item i { width: 20px; text-align: center; font-size: 1.2rem; }
        .nav-item:hover { background: #f8fafc; color: var(--accent); transform: translateX(5px); }
        .nav-item.active { background: #fdf2f8; color: var(--accent); font-weight: 700; }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 280px; margin-top: 75px; width: calc(100% - 280px); padding: 40px 50px; }
        .page-header { margin-bottom: 40px; display: flex; align-items: center; gap: 20px;}
        .header-icon { width: 55px; height: 55px; background: #fff; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--accent); box-shadow: var(--shadow-soft); }
        .page-header h1 { font-size: 2rem; margin: 0 0 5px 0; color: #1e293b; font-weight: 800; letter-spacing: -1px; }
        .page-header p { margin: 0; color: var(--text-muted); font-size: 1rem; font-weight: 500;}

        /* --- STATS GRID --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 50px; }
        .stat-card { background: var(--card-bg); padding: 25px 20px; border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow-soft); border: 1px solid rgba(0,0,0,0.02); }
        .stat-icon { width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.3rem; }
        .stat-card h4 { margin: 0; font-size: 1.8rem; font-weight: 800; color: #1e293b; }
        .stat-card span { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-top: 5px; }

        /* --- SECTION LAYOUT --- */
        .admin-section { display: grid; grid-template-columns: 380px 1fr; gap: 30px; margin-bottom: 60px; }
        .card-form { background: var(--card-bg); padding: 35px; border-radius: var(--radius-lg); height: fit-content; position: sticky; top: 100px; box-shadow: var(--shadow-soft); }
        .card-data { background: var(--card-bg); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-soft); }
        .card-header { padding: 25px 30px; background: #fff; border-bottom: 1px solid var(--border-color); font-weight: 800; font-size: 1.1rem; color: #1e293b; }

        /* --- FORM STYLING --- */
        .card-form h3 { margin-top: 0; margin-bottom: 25px; font-size: 1.2rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        label { display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 8px; color: var(--text-muted); }
        .control { width: 100%; padding: 14px 18px; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; font-weight: 500; margin-bottom: 20px; background: #f8fafc; color: var(--text-main); transition: 0.2s; }
        .control:focus { outline: none; background: #fff; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(190, 24, 93, 0.1); }
        .btn-primary { background: var(--accent); color: #fff; border: none; padding: 16px; width: 100%; font-weight: 700; font-size: 0.95rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition); box-shadow: 0 4px 6px rgba(190, 24, 93, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 8px 15px rgba(190, 24, 93, 0.3); }
        .btn-edit-mode { background: var(--success) !important; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2) !important; }

        /* --- TABEL --- */
        .scroll-area { max-height: 650px; overflow-y: auto; padding: 0 10px 10px 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 18px 20px; background: #fff; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid var(--border-color); }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; font-weight: 500; color: var(--text-main); vertical-align: middle; }
        tr:hover td { background: #f8fafc; }
        .btn-action { color: var(--text-muted); width: 35px; height: 35px; border-radius: 10px; background: #f1f5f9; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; margin-left: 5px; transition: 0.2s; }
        .btn-action:hover { background: #e2e8f0; color: var(--accent); transform: translateY(-2px); }
        .btn-action-danger:hover { background: #fee2e2; color: var(--danger); }
        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: var(--text-main); }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        @media (max-width: 1000px) { .admin-section { grid-template-columns: 1fr; } .card-form { position: relative; top: 0; } }
    </style>
</head>
<body>

<div class="global-ticker">
    <div class="ticker-content">
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/us.png" alt="US" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/gb.png" alt="GB" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/au.png" alt="AU" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/ca.png" alt="CA" class="flag-icon"></div>
        <div class="flag-wrapper"><img src="https://flagcdn.com/w40/nz.png" alt="NZ" class="flag-icon"></div>
    </div>
</div>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="logo_website/gambar.1.png" alt="Logo">
        <span>EDUVENTURE HUB</span>
    </div>
    <div class="sidebar-nav">
        <a href="admin_dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i> <span>Dashboard (DE)</span></a>
        
        <a href="#english-section" class="nav-item active"><i class="fa-solid fa-book-open-reader"></i> <span>Village Stories</span></a>
        
        <a href="english.php" class="nav-item" style="margin-top:20px; border-top:1px solid var(--border-color); padding-top:20px;"><i class="fa-solid fa-globe"></i> <span>Live Platform</span></a>
    </div>
    <div style="margin-top: auto; padding-top: 25px; border-top: 1px solid var(--border-color);">
        <a href="logout.php" class="nav-item" style="color: #ef4444; background: #fef2f2;"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Secure Logout</span></a>
    </div>
</aside>

<main class="main-content">
    <header class="page-header anim d-1">
        <div class="header-icon"><i class="fa-solid fa-book-open-reader"></i></div>
        <div>
            <h1>English Command Center</h1>
            <p>Kelola materi bacaan (Stories) untuk pengguna English Village.</p>
        </div>
    </header>

    <div class="stats-grid anim d-2" style="max-width: 300px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fce7f3; color:var(--accent);"><i class="fa-solid fa-book-open-reader"></i></div>
            <h4><?= $count_english ?></h4><span>Total Stories Database</span>
        </div>
    </div>

    <div class="admin-section anim d-3" id="english-section">
        <div class="card-form">
            <h3 id="en-title"><i class="fa-solid fa-book-open-reader" style="color: var(--accent);"></i> Stories Editor</h3>
            <form action="" method="POST" id="formEn">
                <input type="hidden" name="id_edit_english" id="id_en">
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:15px;">
                    <div><label>Judul Cerita</label><input type="text" name="title_en" id="title_en" class="control" placeholder="The Old Tree..." required></div>
                    <div><label>Kategori</label><select name="cat_en" id="cat_en" class="control"><option value="pendek">Pendek</option><option value="panjang">Panjang</option></select></div>
                </div>
                <label>Teks Bacaan</label><textarea name="content_en" id="content_en" class="control" style="height:120px; resize:vertical;" placeholder="Write the story here..." required></textarea>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div><label>Pertanyaan 1</label><input type="text" name="q1_en" id="q1_en" class="control"><label>Kunci 1</label><input type="text" name="a1_en" id="a1_en" class="control"></div>
                    <div><label>Pertanyaan 2</label><input type="text" name="q2_en" id="q2_en" class="control"><label>Kunci 2</label><input type="text" name="a2_en" id="a2_en" class="control"></div>
                </div>
                <button type="submit" name="tambah_english" id="btn-en-submit" class="btn-primary">Publish Story</button>
                <button type="button" onclick="location.reload()" style="width:100%; border:none; background:transparent; padding:12px; margin-top:10px; font-weight:700; cursor:pointer; color:var(--text-muted);">Batal / Reset</button>
            </form>
        </div>
        <div class="card-data">
            <div class="card-header">Stories Database</div>
            <div class="scroll-area">
                <table>
                    <thead><tr><th>Judul & Teks</th><th>Kategori</th><th align="center">Aksi</th></tr></thead>
                    <tbody>
                        <?php if($english_list): while($en = $english_list->fetch_assoc()): ?>
                        <tr>
                            <td><b><?= htmlspecialchars(isset($en['title']) ? $en['title'] : 'N/A') ?></b><br><span style="font-size:0.85rem; color:var(--text-muted);"><?= substr(htmlspecialchars(isset($en['content']) ? $en['content'] : ''), 0, 60) ?>...</span></td>
                            <td><span class="badge" style="background:#fce7f3; color:var(--accent);"><?= strtoupper(isset($en['category']) ? $en['category'] : 'N/A') ?></span></td>
                            <td align="center" style="white-space: nowrap;">
                                <button onclick='editEnglish(<?= json_encode($en) ?>)' class="btn-action"><i class="fa-solid fa-pen"></i></button>
                                <a href="?delete_english=<?= isset($en['id']) ? $en['id'] : '' ?>" class="btn-action btn-action-danger" onclick="return confirm('Hapus data ini?')"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<script>
    // URL Cleanup
    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('delete_english')) {
            url.search = '';
            window.history.replaceState({path: url.toString()}, '', url.toString());
        }
    }

    // JS EDIT HANDLERS
    function editEnglish(d) {
        document.getElementById('id_en').value = d.id;
        document.getElementById('title_en').value = d.title !== undefined ? d.title : '';
        document.getElementById('cat_en').value = d.category !== undefined ? d.category : '';
        document.getElementById('content_en').value = d.content !== undefined ? d.content : '';
        document.getElementById('q1_en').value = d.question_1 !== undefined ? d.question_1 : ''; 
        document.getElementById('a1_en').value = d.answer_1 !== undefined ? d.answer_1 : '';
        document.getElementById('q2_en').value = d.question_2 !== undefined ? d.question_2 : ''; 
        document.getElementById('a2_en').value = d.answer_2 !== undefined ? d.answer_2 : '';
        
        document.getElementById('en-title').innerHTML = "<i class='fa-solid fa-pen text-success'></i> Edit Story";
        document.getElementById('btn-en-submit').classList.add('btn-edit-mode');
        document.getElementById('btn-en-submit').innerText = 'Update Story';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>

</body>
</html>
