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


$conn = new mysqli($host, $user, $pass, $db);

// 1. Ambil data lama berdasarkan ID yang dikirim dari URL
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM stories WHERE id = $id");
    $data = $result->fetch_assoc();

    if (!$data) {
        die("Data buku tidak ditemukan!");
    }
}

// 2. Logika Update saat tombol Simpan ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category = $conn->real_escape_string($_POST['category']);

    $sql = "UPDATE stories SET title='$title', content='$content', category='$category' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Data buku berhasil diperbarui!'); window.location='admin_dashboard.php#shelf';</script>";
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
    <title>Edit Library | <?= htmlspecialchars($data['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ae0001;
            --dark: #0f172a;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --radius: 16px;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            color: var(--dark);
        }

        .container {
            width: 100%;
            max-width: 700px;
            padding: 20px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-card { 
            background: white; 
            padding: 40px; 
            border-radius: var(--radius); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            border: 1px solid var(--border);
        }

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .header i {
            background: var(--primary);
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-size: 1.2rem;
        }

        .header h2 {
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 1.5rem;
        }

        label { 
            display: block; 
            font-size: 0.75rem; 
            font-weight: 800; 
            margin-bottom: 8px; 
            color: #64748b; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input, textarea, select { 
            width: 100%; 
            padding: 14px 18px; 
            margin-bottom: 25px; 
            border: 1.5px solid var(--border); 
            border-radius: 12px; 
            font-family: inherit; 
            font-size: 0.95rem; 
            font-weight: 600;
            box-sizing: border-box;
            transition: 0.2s;
            background: #fcfcfc;
        }

        input:focus, textarea:focus, select:focus { 
            border-color: var(--primary); 
            outline: none; 
            background: white;
            box-shadow: 0 0 0 4px rgba(174, 0, 1, 0.05);
        }

        button { 
            background: var(--dark); 
            color: white; 
            border: none; 
            padding: 16px; 
            cursor: pointer; 
            width: 100%; 
            border-radius: 12px; 
            font-weight: 800; 
            font-size: 1rem;
            transition: 0.3s;
        }

        button:hover { 
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(174, 0, 1, 0.2);
        }

        .back-link { 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px; 
            text-decoration: none; 
            color: #94a3b8; 
            font-weight: 700;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="container">
    <div class="form-card">
        <div class="header">
            <i class="fa-solid fa-pen-nib"></i>
            <h2>Edit Library Meta</h2>
        </div>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">

            <label>Judul Buku / Cerita</label>
            <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" placeholder="Masukkan judul..." required>

            <label>Kategori Durasi</label>
            <select name="category">
                <option value="pendek" <?= ($data['category'] == 'pendek') ? 'selected' : '' ?>>Short Story (Pendek)</option>
                <option value="panjang" <?= ($data['category'] == 'panjang') ? 'selected' : '' ?>>Novelty (Panjang)</option>
            </select>

            <label>Konten Cerita (German Text)</label>
            <textarea name="content" rows="12" placeholder="Tuliskan cerita dalam Bahasa Jerman di sini..." required><?php echo $data['content']; ?></textarea>

            <button type="submit">Update & Sinkronisasi</button>
        </form>

        <a href="admin_dashboard.php#shelf" class="back-link">
            <i class="fa-solid fa-arrow-left-long"></i> Batal dan Kembali
        </a>
    </div>
</div>

</body>
</html>