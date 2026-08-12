<?php
session_start();
$host = "localhost"; $user = "u960862048_roy"; $pass = "Caracter_Cs321"; $db = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$uid = $_SESSION['user_id'];
// Mengambil data dari URL (GET)
$level = $_GET['level'] ?? null;
$tipe = $_GET['tipe'] ?? null;

if ($level) {
    if ($level == 'ARTIKEL') {
        $conn->query("DELETE p FROM user_progress p JOIN latihan_artikel a ON p.soal_id = a.id WHERE p.user_id = $uid");
    } elseif ($level == 'MODALVERBEN') {
        $conn->query("DELETE p FROM user_progress p JOIN latihan_modalverben m ON p.soal_id = m.id WHERE p.user_id = $uid");
    } elseif ($level == 'PUZZLE') {
        $conn->query("DELETE p FROM user_progress p JOIN latihan_satzbau s ON p.soal_id = s.id WHERE p.user_id = $uid");
    } else {
        // Reset level standar (A1, A2, B1) sesuai tipe
        $conn->query("DELETE p FROM user_progress p 
                      JOIN latihan_soal t ON p.soal_id = t.id 
                      WHERE p.user_id = $uid AND t.level = '$level' AND t.tipe = '$tipe'");
    }

    // Redirect kembali ke halaman latihan soal nomor 1
    header("Location: latihan.php?level=$level&tipe=$tipe");
} else {
    header("Location: latihan.php");
}
exit();