<?php
session_start();

// 🔐 Proteksi hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = "localhost"; // biasanya tetap localhost
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; // ganti dengan password MySQL user (bukan password login hosting!)
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

$message = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $user = $conn->real_escape_string($_POST['username']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Gunakan Prepared Statement untuk keamanan lebih baik
    $stmt = $conn->prepare("INSERT INTO users (nama, email, username, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $email, $user, $pass);

    if ($stmt->execute()) {
        $message = "Akun <strong>$nama</strong> berhasil dibuat!";
        $status = "success";
    } else {
        $message = "Error: " . $conn->error;
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
    <title>Tambah User | DeutschAktiv</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-page: #f8fafc;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .btn-back {
            text-decoration: none;
            color: var(--text-sub);
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-back:hover { color: var(--primary); }

        .card {
            background: var(--white);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }

        h2 {
            margin: 0 0 8px 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        p.desc {
            color: var(--text-sub);
            font-size: 0.9rem;
            margin-bottom: 32px;
        }

        /* Alert Styling */
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .alert-success { background: #ecfdf5; color: var(--success); border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: var(--error); border: 1px solid #fecaca; }

        /* Form Styling */
        .section-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 16px;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 1rem;
        }

        input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fcfcfd;
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        hr {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
        }

        .btn-submit:active { transform: translateY(0); }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <a href="admin_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="card">
        <h2>Pendaftaran User</h2>
        <p class="desc">Buat kredensial baru untuk siswa atau pengajar.</p>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $status; ?>">
                <i class="fas <?php echo ($status == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <span class="section-label">Informasi Personal</span>
            
            <div class="input-wrapper">
                <i class="fas fa-id-card"></i>
                <input type="text" name="nama" placeholder="Nama Lengkap" required>
            </div>

            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Aktif" required>
            </div>

            <hr>

            <span class="section-label">Akses Keamanan</span>

            <div class="input-wrapper">
                <i class="fas fa-user-circle"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-wrapper">
                <i class="fas fa-key"></i>
                <input type="password" name="password" placeholder="Password Sementara" required>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Buat Akun Sekarang
            </button>
        </form>
    </div>
</div>

</body>
</html>