<?php
session_start();
$conn = new mysqli("localhost", "u960862048_roy", "Caracter_Cs321", "u960862048_elroy");
$uid = $_SESSION['user_id'];
$map_id = (int)$_GET['map'];

// Ambil status progres user untuk semua soal di map ini
$result = $conn->query("SELECT l.id, p.is_correct 
                        FROM latihan_artikel l 
                        LEFT JOIN user_progress p ON l.id = p.soal_id AND p.user_id = $uid 
                        WHERE l.map_id = $map_id ORDER BY l.id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bank Soal | Map <?= $map_id ?></title>
    <style>
        .grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; max-width: 500px; margin: 20px auto; }
        .box { padding: 20px; text-align: center; border-radius: 10px; color: white; font-weight: bold; text-decoration: none; }
        .hijau { background: #58cc02; border-bottom: 4px solid #46a302; cursor: default; }
        .merah { background: #ff4b4b; border-bottom: 4px solid #ea2b2b; }
        .abu { background: #e5e5e5; color: #3c3c3c; }
    </style>
</head>
<body style="font-family: sans-serif; text-align: center; background: #f7f7f7;">
    <h1>Ringkasan Map <?= $map_id ?></h1>
    <p>Klik kotak merah untuk memperbaiki jawaban!</p>
    <div class="grid">
        <?php while($r = $result->fetch_assoc()): ?>
            <?php 
                $status = "abu"; $link = "artikel.php?map=$map_id&retry_id=".$r['id'];
                if($r['is_correct'] === '1') { $status = "hijau"; $link = "#"; }
                elseif($r['is_correct'] === '0') { $status = "merah"; }
            ?>
            <a href="<?= $link ?>" class="box <?= $status ?>"><?= $r['id'] ?></a>
        <?php endwhile; ?>
    </div>
    <br>
    <a href="artikel_map.php" style="padding: 15px; background: #1cb0f6; color: white; border-radius: 10px; text-decoration: none;">Kembali ke Peta</a>
</body>
</html>