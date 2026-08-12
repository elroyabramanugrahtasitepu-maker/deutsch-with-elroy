<?php
session_start();
// Koneksi ke database kamu


if (!isset($_SESSION['user_id'])) { exit(); }

$uid = $_SESSION['user_id'];
$map_id = isset($_POST['map_id']) ? (int)$_POST['map_id'] : 1;
$answers = $_POST['ans'] ?? [];

foreach ($answers as $soal_id => $user_ans) {
    $soal_id = (int)$soal_id;
    
    // 1. Ambil jawaban asli dari tabel latihan_artikel
    $res = $conn->query("SELECT jawaban FROM latihan_artikel WHERE id = $soal_id");
    $data = $res->fetch_assoc();
    
    if ($data) {
        $is_correct = ($data['jawaban'] === $user_ans) ? 1 : 0;

        // 2. Gunakan REPLACE INTO agar status Merah bisa berubah jadi Hijau
        // Pastikan sudah menjalankan: ALTER TABLE user_progress ADD UNIQUE KEY (user_id, soal_id);
        $stmt = $conn->prepare("REPLACE INTO user_progress (user_id, soal_id, jawaban_user, is_correct, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $uid, $soal_id, $user_ans, $is_correct);
        $stmt->execute();
    }
}

// 3. Logika Buka Map Selanjutnya (Contoh: Jika sudah benar 40 soal di map ini)
$check_lulus = $conn->query("SELECT COUNT(*) as total FROM user_progress 
                             WHERE user_id = $uid AND is_correct = 1 
                             AND soal_id IN (SELECT id FROM latihan_artikel WHERE map_id = $map_id)");
$lulus_data = $check_lulus->fetch_assoc();

if ($lulus_data['total'] >= 40) {
    $next_map = $map_id + 1;
    $conn->query("UPDATE users SET current_artikel_map = $next_map WHERE id = $uid AND current_artikel_map <= $map_id");
}

// Redirect kembali ke hasil bank soal
header("Location: hasil_artikel.php?map=$map_id");
exit();
