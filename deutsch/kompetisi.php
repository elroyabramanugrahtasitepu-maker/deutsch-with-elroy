<?php
session_start();

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

$uid = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'] ?? 'User';

// 1. Ambil Data Leaderboard (Top 10)
$leaderboard = $conn->query("SELECT nama, total_poin FROM users ORDER BY total_poin DESC LIMIT 10");

// 2. Ambil Statistik User Login
$user_data_query = $conn->query("SELECT total_poin FROM users WHERE id = $uid");
$user_stat = $user_data_query->fetch_assoc();

// Hitung Rank User
$rank_query = $conn->query("SELECT COUNT(*) + 1 as rank FROM users WHERE total_poin > (SELECT total_poin FROM users WHERE id = $uid)");
$rank_data = $rank_query->fetch_assoc();
$my_rank = $rank_data['rank'] ?? '-';

// Ambil Top 3 untuk Podium
$top3_query = $conn->query("SELECT nama, total_poin FROM users ORDER BY total_poin DESC LIMIT 3");
$winners = [];
while($w = $top3_query->fetch_assoc()) {
    $winners[] = $w;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meisterschaft Arena | Deutsch mit Elroy</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=UnifrakturMaguntia&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f172a;
            --de-red: #e11d48;
            --de-red-soft: rgba(225, 29, 72, 0.1);
            --de-gold: #d97706;
            --de-gold-glow: rgba(217, 119, 6, 0.15);
            --bg-main: #f8fafc;
            --surface: rgba(255, 255, 255, 0.72);
            --border: rgba(255, 255, 255, 0.6);
            --border-card: rgba(226, 232, 240, 0.8);
            --text-main: #0f172a;
            --text-muted: #475569;
            
            --silver: #64748b;
            --bronze: #b45309;
            
            --radius-lg: 20px;
            --radius-xl: 24px;
            --shadow-sm: 0 8px 32px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 16px 40px -8px rgba(15, 23, 42, 0.12);
            --shadow-lg: 0 20px 45px -10px rgba(225, 29, 72, 0.3);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 120px;
            /* Background gambar bening dengan overlay transparan tipis */
            background-image: 
                linear-gradient(to bottom, rgba(255, 255, 255, 0.25), rgba(241, 245, 249, 0.45)),
                url('https://images.unsplash.com/photo-1599946347371-68eb71b16afc?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Top German Flag Accent Strip */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 33.3%, var(--de-red) 33.3%, var(--de-red) 66.6%, var(--de-gold) 66.6%);
            z-index: 1000;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        /* Navigation Bar */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: var(--text-main);
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .btn-back:hover {
            background: rgba(225, 29, 72, 0.15);
            color: var(--de-red);
            border-color: rgba(225, 29, 72, 0.4);
            transform: translateX(-4px);
        }

        .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(254, 243, 199, 0.85);
            backdrop-filter: blur(12px);
            color: var(--de-gold);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.775rem;
            font-weight: 800;
            border: 1px solid rgba(253, 224, 71, 0.8);
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        /* Header Title Hero */
        .hero-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero-header h1 {
            font-family: 'UnifrakturMaguntia', serif;
            font-size: clamp(3.5rem, 9vw, 5.8rem);
            color: var(--primary);
            line-height: 1;
            margin-bottom: 6px;
            text-shadow: 0 4px 15px rgba(255, 255, 255, 0.8);
        }

        .hero-header .subtitle {
            display: inline-block;
            background: linear-gradient(135deg, var(--de-red) 0%, #be123c 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            padding: 6px 20px;
            border-radius: 50px;
            box-shadow: 0 6px 20px rgba(225, 29, 72, 0.3);
        }

        /* Player Stat Dashboard Card (Bening Glassmorphism) */
        .player-dashboard-card {
            background: var(--surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            box-shadow: var(--shadow-md);
            margin-bottom: 48px;
            position: relative;
            overflow: hidden;
        }

        .player-dashboard-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--primary) 33.3%, var(--de-red) 33.3%, var(--de-red) 66.6%, var(--de-gold) 66.6%);
        }

        .stat-box {
            text-align: center;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(10px);
            padding: 16px;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: var(--transition);
        }

        .stat-box:hover {
            background: rgba(255, 255, 255, 0.9);
            border-color: var(--de-red);
            box-shadow: var(--shadow-sm);
        }

        .stat-title {
            font-size: 0.725rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: clamp(1.4rem, 4vw, 2.1rem);
            font-weight: 900;
            color: var(--primary);
            line-height: 1;
        }

        .stat-value.highlight-red { color: var(--de-red); }
        .stat-value.highlight-gold { color: var(--de-gold); }

        /* Podium Layout (Transparan & Bening) */
        .podium-wrapper {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 16px;
            margin-bottom: 44px;
            padding-top: 24px;
        }

        .podium-card {
            flex: 1;
            background: var(--surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px 12px 20px;
            text-align: center;
            position: relative;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .podium-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
        }

        .podium-card.rank-1 {
            order: 2;
            min-height: 240px;
            border-color: #fde047;
            background: linear-gradient(180deg, rgba(254, 252, 232, 0.85) 0%, rgba(255, 255, 255, 0.8) 100%);
            box-shadow: 0 16px 35px rgba(217, 119, 6, 0.18);
        }

        .podium-card.rank-2 { order: 1; min-height: 195px; }
        .podium-card.rank-3 { order: 3; min-height: 170px; }

        .crown-pill {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            position: absolute;
            top: -23px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }

        .rank-1 .crown-pill { color: var(--de-gold); border-color: #fde047; background: #fefce8; }
        .rank-2 .crown-pill { color: var(--silver); }
        .rank-3 .crown-pill { color: var(--bronze); }

        .avatar-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(241, 245, 249, 0.8);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.15rem;
            margin-top: 10px;
            margin-bottom: 12px;
            border: 2px solid var(--border);
        }

        .rank-1 .avatar-circle { background: #fef3c7; color: var(--bronze); border-color: #fde047; }

        .p-name {
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--primary);
            margin-bottom: 6px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .p-pts {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--de-gold);
            margin-top: auto;
        }

        /* Leaderboard Table List */
        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .leader-item {
            background: var(--surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 20px;
            display: grid;
            grid-template-columns: 50px 1fr 110px;
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .leader-item:hover {
            transform: translateX(6px);
            border-color: var(--de-red);
            box-shadow: 0 8px 25px rgba(225, 29, 72, 0.15);
        }

        .leader-item.is-current-user {
            background: rgba(255, 241, 242, 0.85);
            border: 2px solid var(--de-red);
        }

        .r-rank {
            font-weight: 900;
            font-size: 1.1rem;
            color: var(--text-muted);
        }

        .leader-item.is-current-user .r-rank { color: var(--de-red); }

        .r-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .r-username {
            font-weight: 700;
            font-size: 0.975rem;
            color: var(--primary);
        }

        .badge-you {
            background: var(--de-red);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .r-score-val {
            font-weight: 900;
            font-size: 1.1rem;
            color: var(--primary);
            text-align: right;
        }

        .r-score-val small {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-left: 2px;
            font-weight: 700;
        }

        /* Floating CTA Button */
        .btn-floating-play {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--de-red) 0%, #be123c 100%);
            color: #ffffff;
            padding: 16px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 99;
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
        }

        .btn-floating-play:hover {
            transform: translateX(-50%) translateY(-4px);
            box-shadow: 0 25px 45px -10px rgba(225, 29, 72, 0.45);
            background: linear-gradient(135deg, #be123c 0%, #9f1239 100%);
        }

        /* Mobile Optimization */
        @media (max-width: 640px) {
            .container { padding: 20px 14px; }
            .player-dashboard-card { grid-template-columns: 1fr; gap: 10px; }
            .podium-wrapper { gap: 8px; }
            .podium-card { padding: 18px 6px 14px; }
            .p-name { font-size: 0.8rem; }
            .p-pts { font-size: 0.95rem; }
            .leader-item { grid-template-columns: 40px 1fr 90px; padding: 14px 14px; }
            .r-username { font-size: 0.875rem; }
            .btn-floating-play { width: 90%; justify-content: center; padding: 16px 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Navigation Bar -->
    <header class="top-nav">
        <a href="deutsch.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <div class="badge-live">
            <i class="fa-solid fa-fire"></i> Season Aktif
        </div>
    </header>

    <!-- Hero Header Title -->
    <div class="hero-header">
        <h1>Meisterschaft</h1>
        <div class="subtitle">Bundesliga der Sprachen</div>
    </div>

    <!-- Player Pass Stat Card -->
    <section class="player-dashboard-card">
        <div class="stat-box">
            <div class="stat-title">Pemain Aktiv</div>
            <div class="stat-value highlight-red"><?= htmlspecialchars($nama_user) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-title">Peringkat Kamu</div>
            <div class="stat-value highlight-gold">#<?= $my_rank ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-title">Total Poin</div>
            <div class="stat-value"><?= number_format($user_stat['total_poin'] ?? 0) ?></div>
        </div>
    </section>

    <!-- Top 3 Championship Podium -->
    <section class="podium-wrapper">
        <!-- Rank 2 -->
        <div class="podium-card rank-2">
            <div class="crown-pill"><i class="fa-solid fa-medal"></i></div>
            <div class="avatar-circle"><?= mb_substr($winners[1]['nama'] ?? '?', 0, 1, 'UTF-8') ?></div>
            <div class="p-name"><?= htmlspecialchars($winners[1]['nama'] ?? '---') ?></div>
            <div class="p-pts"><?= number_format($winners[1]['total_poin'] ?? 0) ?></div>
        </div>

        <!-- Rank 1 (Gold Winner) -->
        <div class="podium-card rank-1">
            <div class="crown-pill"><i class="fa-solid fa-crown"></i></div>
            <div class="avatar-circle"><?= mb_substr($winners[0]['nama'] ?? '?', 0, 1, 'UTF-8') ?></div>
            <div class="p-name" style="font-size: 1rem; font-weight: 900;"><?= htmlspecialchars($winners[0]['nama'] ?? '---') ?></div>
            <div class="p-pts" style="font-size: 1.25rem;"><?= number_format($winners[0]['total_poin'] ?? 0) ?></div>
        </div>

        <!-- Rank 3 -->
        <div class="podium-card rank-3">
            <div class="crown-pill"><i class="fa-solid fa-award"></i></div>
            <div class="avatar-circle"><?= mb_substr($winners[2]['nama'] ?? '?', 0, 1, 'UTF-8') ?></div>
            <div class="p-name"><?= htmlspecialchars($winners[2]['nama'] ?? '---') ?></div>
            <div class="p-pts"><?= number_format($winners[2]['total_poin'] ?? 0) ?></div>
        </div>
    </section>

    <!-- Leaderboard Table List -->
    <main class="leaderboard-list">
        <?php 
        $rank = 1;
        $leaderboard->data_seek(0);
        while($row = $leaderboard->fetch_assoc()): 
            $is_me = ($row['nama'] == $nama_user);
        ?>
        <div class="leader-item <?= $is_me ? 'is-current-user' : '' ?>">
            <div class="r-rank">#<?= $rank++ ?></div>
            <div class="r-user-info">
                <span class="r-username"><?= htmlspecialchars($row['nama']) ?></span>
                <?php if($is_me): ?>
                    <span class="badge-you">KAMU</span>
                <?php endif; ?>
            </div>
            <div class="r-score-val">
                <?= number_format($row['total_poin']) ?><small>PTS</small>
            </div>
        </div>
        <?php endwhile; ?>
    </main>
</div>

<!-- Floating Action Button -->
<a href="arena_kompetisi.php" class="btn-floating-play">
    <i class="fa-solid fa-gamepad"></i> MULAI ARENA MATCH
</a>

</body>
</html>
<?php $conn->close(); ?>