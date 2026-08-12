<?php
session_start();
$host = "localhost"; // biasanya tetap localhost
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; // ganti dengan password MySQL user (bukan password login hosting!)
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $question1 = $_POST['question_1'];
    $answer1 = $_POST['answer_1'];
    $question2 = $_POST['question_2'];
    $answer2 = $_POST['answer_2'];
    $level = $_POST['level'];
    $category = $_POST['category'];

    $stmt = $conn->prepare("INSERT INTO stories (title, content, question_1, answer_1, question_2, answer_2, level, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $title, $content, $question1, $answer1, $question2, $answer2, $level, $category);

    if ($stmt->execute()) {
        $message = "Story berhasil ditambahkan ke database!";
        $status = "success";
    } else {
        $message = "Gagal menyimpan data!";
        $status = "error";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Story | DeutschAktiv Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-body: #f8fafc;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .top-bar h2 {
            font-weight: 700;
            font-size: 1.75rem;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--white);
            border: 1px solid var(--border);
        }

        .btn-back:hover {
            color: var(--text-dark);
            background: #f1f5f9;
        }

        /* Card & Form Styles */
        .card {
            background: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 10px 15px -3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .section-header:first-of-type { margin-top: 0; }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        input, textarea, select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fcfcfd;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background: var(--white);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Toast Message */
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.4s ease-out;
        }
        .alert-success { background: #ecfdf5; color: var(--success); border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: var(--error); border: 1px solid #fecaca; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Button Submit */
        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .btn-submit:active { transform: translateY(0); }

        /* Question Box Special Styling */
        .question-container {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px dashed var(--border);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Tambah Story Baru</h2>
        <a href="admin_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Panel Admin
        </a>
    </div>

    <div class="card">
        <?php if($message): ?>
            <div class="alert alert-<?php echo $status; ?>">
                <i class="fas <?php echo $status == 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="section-header">
                <i class="fas fa-book-open"></i> Konten Utama
            </div>

            <div class="form-group">
                <label>Judul Cerita</label>
                <input type="text" name="title" placeholder="Contoh: Ein Tag im Park" required>
            </div>

            <div class="form-group">
                <label>Isi Cerita (Bahasa Jerman)</label>
                <textarea name="content" rows="8" placeholder="Tuliskan cerita lengkap di sini..." required></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Level Kompetensi</label>
                    <select name="level" required>
                        <option value="" disabled selected>Pilih Level</option>
                        <option value="A1">A1 (Beginner)</option>
                        <option value="A2 (Elementary)">A2</option>
                        <option value="B1 (Intermediate)">B1</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category" required>
                        <option value="" disabled selected>Pilih Kategori</option>
                        <option value="Kurzgeschichte">Kurzgeschichte (Cerita Pendek)</option>
                        <option value="Romane">Romane (Novel)</option>
                        <option value="Alltag">Alltag (Harian)</option>
                    </select>
                </div>
            </div>

            <div class="section-header">
                <i class="fas fa-tasks"></i> Latihan Pemahaman
            </div>

            <div class="question-container">
                <div class="form-group">
                    <label><i class="fas fa-question-circle"></i> Pertanyaan 1</label>
                    <input type="text" name="question_1" placeholder="Misal: Wer ist die Hauptperson?" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><i class="fas fa-key"></i> Jawaban Benar</label>
                    <input type="text" name="answer_1" placeholder="Masukkan jawaban kunci" required>
                </div>
            </div>

            <div class="question-container">
                <div class="form-group">
                    <label><i class="fas fa-question-circle"></i> Pertanyaan 2</label>
                    <input type="text" name="question_2" placeholder="Misal: Wohin geht er?" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><i class="fas fa-key"></i> Jawaban Benar</label>
                    <input type="text" name="answer_2" placeholder="Masukkan jawaban kunci" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Publikasikan Story
            </button>
        </form>
    </div>
</div>

</body>
</html>