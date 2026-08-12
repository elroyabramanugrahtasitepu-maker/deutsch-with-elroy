<?php
session_start();

// 1. Proteksi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Koneksi Database (Disesuaikan dengan config admin_dashboard Anda)

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi agar tidak error 500 diam-diam
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 3. Ambil data materi lama
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = (int)$_GET['id'];
// Menggunakan parameter binding agar lebih aman dari SQL Injection
$stmt = $conn->prepare("SELECT * FROM materi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) { 
    echo "<script>alert('Materi tidak ditemukan!'); window.location='admin_dashboard.php';</script>"; 
    exit(); 
}

// 4. Proses Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_materi'])) {
    $judul = $conn->real_escape_string($_POST['judul']);
    $level = $conn->real_escape_string($_POST['level']);
    $icon = $conn->real_escape_string($_POST['icon']);
    $desc  = $conn->real_escape_string($_POST['deskripsi']);
    $konten = $conn->real_escape_string($_POST['konten']);

    // Query update sesuai struktur tabel 'materi' Anda
    $sql = "UPDATE materi SET 
            judul='$judul', 
            level='$level', 
            icon='$icon', 
            deskripsi='$desc',
            konten='$konten' 
            WHERE id=$id";

    if ($conn->query($sql)) {
        header("Location: admin_dashboard.php#materi-section");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Materi | DeutschAktiv</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #ae0001; --accent: #ff4d00; --border: #e2e8f0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; padding: 40px; color: #1e293b; margin: 0; }
        .edit-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 30px; display: flex; align-items: center; gap: 12px; }
        .label { display: block; font-size: 0.75rem; font-weight: 800; margin-bottom: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-field { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: 12px; margin-bottom: 20px; font-family: inherit; font-size: 0.95rem; outline: none; transition: 0.2s; }
        .input-field:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(174, 0, 1, 0.1); }
        .btn-group { display: flex; gap: 15px; margin-top: 20px; }
        .btn { padding: 14px 25px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-save { background: var(--primary); color: white; flex: 2; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(174, 0, 1, 0.2); }
        .btn-cancel { background: #f1f5f9; color: #475569; flex: 1; }
        code { background: #fff7ed; color: #c2410c; padding: 2px 6px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; }
    </style>
</head>
<body>

<div class="edit-container">
    <h2><i class="fa-solid fa-pen-to-square"></i> Edit Modul Materi</h2>
    
    <form action="" method="POST">
        <label class="label">Judul Materi</label>
        <input type="text" name="judul" class="input-field" value="<?= htmlspecialchars($data['judul']) ?>" placeholder="E.g. Konjugation von Verben" required>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label class="label">Level</label>
                <select name="level" class="input-field">
                    <option value="A1" <?= $data['level'] == 'A1' ? 'selected' : '' ?>>Level A1</option>
                    <option value="A2" <?= $data['level'] == 'A2' ? 'selected' : '' ?>>Level A2</option>
                    <option value="B1" <?= $data['level'] == 'B1' ? 'selected' : '' ?>>Level B1</option>
                </select>
            </div>
            <div>
                <label class="label">Ikon (FontAwesome)</label>
                <input type="text" name="icon" class="input-field" value="<?= htmlspecialchars($data['icon']) ?>" placeholder="fa-solid fa-book">
            </div>
        </div>

        <label class="label">Deskripsi Singkat</label>
        <textarea name="deskripsi" class="input-field" rows="2" placeholder="Penjelasan singkat materi..." required><?= htmlspecialchars($data['deskripsi']) ?></textarea>

        <label class="label">Konten Materi (Gunakan HTML <code>h2, p, table, div</code>)</label>
        <textarea name="konten" class="input-field" rows="15" style="font-family: 'Consolas', monospace; font-size: 0.85rem; line-height: 1.6; background: #fafafa;"><?= htmlspecialchars($data['konten']) ?></textarea>

        <div class="btn-group">
            <a href="admin_dashboard.php" class="btn btn-cancel">Batal</a>
            <button type="submit" name="update_materi" class="btn btn-save">
                <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

</body>
</html>