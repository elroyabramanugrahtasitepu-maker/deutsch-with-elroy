<?php
session_start();

// Koneksi Database
$host = "localhost"; 
$user = "u960862048_roy"; 
$pass = "Caracter_Cs321"; 
$db   = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil Data Feedback dan Nama User (JOIN tabel users)
$sql = "SELECT feedback.*, users.nama 
        FROM feedback 
        JOIN users ON feedback.user_id = users.id 
        ORDER BY feedback.id DESC";
$feedbackList = $conn->query($sql);

if($feedbackList && $feedbackList->num_rows > 0):
    while($fb = $feedbackList->fetch_assoc()): ?>
        
        <div style="background: var(--bg-light, #f4f4f4); padding: 20px; margin-bottom: 15px; border: 2px solid var(--de-black, #000); border-left: 6px solid var(--de-gold, #FFCE00); box-shadow: 4px 4px 0px rgba(0,0,0,0.1);">
            
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid var(--de-black, #000); padding-bottom: 10px; margin-bottom: 10px;">
                <strong style="color: var(--de-black, #000); font-family: 'Poppins', sans-serif; font-size: 1rem; text-transform: uppercase;">
                    <i class="fa-solid fa-user-check"></i> <?= htmlspecialchars($fb['nama']); ?>
                </strong>
                <span style="font-size: 0.75rem; font-weight: 700; color: #555;">
                    <?= date('d.m.Y', strtotime($fb['created_at'] ?? 'now')); ?>
                </span>
            </div>

            <p style="margin: 0; line-height: 1.6; font-family: 'Lora', serif; font-size: 1.05rem; color: #222;">
                "<?= htmlspecialchars($fb['pesan']); ?>"
            </p>

            <?php if (!empty($fb['reply'])): ?>
                <div style="margin-top: 15px; padding: 15px; background: #fff; border: 2px solid var(--de-black, #000); border-top: 6px solid var(--de-red, #DD0000);">
                    <strong style="color: var(--de-red, #DD0000); font-family: 'Poppins', sans-serif; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                        <i class="fa-solid fa-headset"></i> Admin Antwort:
                    </strong>
                    <p style="margin: 8px 0 0; color: #111; font-family: 'Lora', serif; font-weight: 600;">
                        <?= htmlspecialchars($fb['reply']); ?>
                    </p>
                </div>
            <?php endif; ?>
            
        </div>
    <?php endwhile;
else: ?>
    <div style="text-align:center; padding: 40px; border: 2px dashed var(--de-black, #000); background: #fafafa;">
        <i class="fa-regular fa-comment-dots" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
        <p style="font-family: 'Poppins', sans-serif; font-weight: 700; color: #888; margin: 0; text-transform: uppercase;">Noch kein Feedback vorhanden</p>
        <small style="color: #aaa;">(Belum ada masukan pengguna)</small>
    </div>
<?php endif; 

$conn->close();
?>