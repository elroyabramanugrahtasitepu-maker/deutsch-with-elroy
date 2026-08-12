<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? 'lihat_nilai'; 
    $level    = $_POST['level'] ?? '';
    $tipe     = $_POST['tipe'] ?? 'pg'; 
    $language = strtolower($_POST['language'] ?? 'de'); // 'de', 'en', atau 'jp'

    // Normalisasi Tipe Kuis (PG, B/S / TF, MATCH, ESSAY)
    $tipe_map = [
        'pilihan_ganda' => 'pg',
        'pg'            => 'pg',
        'tf'            => 'tf',
        'bs'            => 'tf',
        'b_s'           => 'tf',
        'true_false'    => 'tf',
        'match'         => 'match',
        'matching'      => 'match',
        'essay'         => 'essay',
        'essai'         => 'essay'
    ];
    $norm_tipe = $tipe_map[strtolower($tipe)] ?? 'pg';

    // 1. Penentuan Tabel Soal, Tabel Progres, dan Kunci Jawaban
    if ($language === 'en' || $language === 'jp') {
        $table_name = "latihan_{$language}_{$norm_tipe}";
        $prog_table = "latihan_{$language}_{$norm_tipe}_progress";
        $key_column = 'jawaban_benar';
    } else {
        // Default: Bahasa Jerman (de)
        $prog_table = "user_progress";
        if ($level === 'MODALVERBEN') {
            $table_name = 'latihan_modalverben';
            $key_column = 'jawaban';
        } elseif ($level === 'PUZZLE') {
            $table_name = 'latihan_satzbau';
            $key_column = 'kalimat_benar';
        } elseif ($level === 'HOREN') {
            $table_name = 'latihan_horen';
            $key_column = 'jawaban';
        } else {
            $table_name = 'latihan_soal';
            $key_column = 'jawaban_benar';
        }
    }

    $total_dikerjakan = 0;

    // 2. Proses Simpan Banyak Jawaban (Array)
    if (isset($_POST['ans']) && is_array($_POST['ans'])) {
        foreach ($_POST['ans'] as $soal_id => $jawaban) {
            if (simpanJawabanUniversal($conn, $uid, $soal_id, $jawaban, $table_name, $prog_table, $key_column, $norm_tipe, $language)) {
                $total_dikerjakan++;
            }
        }
    } 

    // 3. Proses Simpan Jawaban Tunggal (Khusus Horen / Single Form)
    if (isset($_POST['user_ans']) && isset($_POST['soal_id'])) {
        if (simpanJawabanUniversal($conn, $uid, $_POST['soal_id'], $_POST['user_ans'], $table_name, $prog_table, $key_column, $norm_tipe, $language)) {
            $total_dikerjakan++;
        }
        $action = 'lanjut';
    }

    // 4. Catat Statistik Harian & Riwayat Aktivitas
    if ($total_dikerjakan > 0) {
        $today = date('Y-m-d');
        
        // A. Update Waktu / Total Soal Dikerjakan
        $stmt_stat = $conn->prepare("INSERT INTO user_daily_stats (user_id, log_date, exercises_completed) 
                                     VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE exercises_completed = exercises_completed + VALUES(exercises_completed)");
        $stmt_stat->bind_param("isi", $uid, $today, $total_dikerjakan);
        $stmt_stat->execute();

        // B. Catat Log Aktivitas
        $modul_name = $level ? $level : strtoupper($norm_tipe);
        $desc = "Selesai " . $total_dikerjakan . " soal latihan (" . strtoupper($language) . " - " . $modul_name . ")";
        $stmt_log = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, language) 
                                    VALUES (?, 'exercise', ?, ?)");
        $stmt_log->bind_param("iss", $uid, $desc, $language);
        $stmt_log->execute();
    }

    // 5. Redirect Setelah Selesai
    if ($action === 'lanjut') {
        if ($language === 'en') {
            $target = "english.php";
        } elseif ($language === 'jp') {
            $target = "japan.php";
        } elseif ($level === 'HOREN') {
            $target = "horen.php";
        } else {
            $target = "latihan.php?level=" . urlencode($level) . "&tipe=" . urlencode($tipe);
        }
        header("Location: $target");
    } else {
        header("Location: hasil_latihan.php?level=" . urlencode($level) . "&tipe=" . urlencode($tipe) . "&lang=" . urlencode($language));
    }
    exit();
}

/**
 * Fungsi Helper menyimpan jawaban dengan deteksi otomatis kolom ID tabel progres
 */
function simpanJawabanUniversal($conn, $uid, $soal_id, $jawaban, $table_name, $prog_table, $key_column, $norm_tipe, $language) {
    $soal_id = (int)$soal_id;
    $jawaban_user = trim($jawaban ?? '');
    if ($jawaban_user === '') return false;

    // Ambil Kunci Jawaban
    $stmt_key = $conn->prepare("SELECT `$key_column` FROM `$table_name` WHERE id = ?");
    if (!$stmt_key) return false;
    
    $stmt_key->bind_param("i", $soal_id);
    $stmt_key->execute();
    $res = $stmt_key->get_result();
    $data = $res->fetch_assoc();

    if ($data) {
        $is_correct = (strcasecmp($jawaban_user, trim($data[$key_column])) === 0) ? 1 : 0;

        // Deteksi Kolom ID Soal pada Tabel Progres (soal_id / id_pg / id_tf / id_match / id_essay)
        $id_col = 'soal_id'; // Default Jerman
        if ($language === 'en' || $language === 'jp') {
            $cols_res = $conn->query("SHOW COLUMNS FROM `$prog_table`");
            if ($cols_res) {
                $cols = [];
                while ($c = $cols_res->fetch_assoc()) {
                    $cols[] = strtolower($c['Field']);
                }
                $possible_cols = ['soal_id', "id_{$norm_tipe}", 'id_soal', 'question_id'];
                foreach ($possible_cols as $p) {
                    if (in_array($p, $cols)) {
                        $id_col = $p;
                        break;
                    }
                }
            }
        }

        // Query Simpan Progres
        $sql = "INSERT INTO `$prog_table` (user_id, `$id_col`, jawaban_user, is_correct, created_at) 
                VALUES (?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE is_correct = VALUES(is_correct), jawaban_user = VALUES(jawaban_user)";
        
        $stmt_up = $conn->prepare($sql);
        if ($stmt_up) {
            $stmt_up->bind_param("iisi", $uid, $soal_id, $jawaban_user, $is_correct);
            $stmt_up->execute();
            return true;
        }
    }

    return false;
}
?>