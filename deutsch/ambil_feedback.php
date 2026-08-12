<?php
session_start();
// Proteksi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit();

// Koneksi Database
$host = "localhost"; $user = "u960862048_roy"; $pass = "Caracter_Cs321"; $db = "u960862048_elroy";
$conn = new mysqli($host, $user, $pass, $db);

// Ambil data feedback
$feedback = $conn->query("SELECT f.*, u.nama FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.id DESC");

if ($feedback->num_rows > 0) {
    while($fb = $feedback->fetch_assoc()): 
        $is_replied = !empty($fb['reply']);
        $bg = $is_replied ? '#f8fafc' : '#ffffff';
        $border = $is_replied ? '#cbd5e1' : '#e2e8f0';
    ?>
        <div style="margin-bottom:15px; padding:20px; border:1px solid <?= $border ?>; border-radius:12px; background: <?= $bg ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <strong style="color: #1e293b; font-size: 0.95rem;">
                    <i class="fa-solid fa-user-circle" style="color: #64748b; margin-right: 5px;"></i> <?= htmlspecialchars($fb['nama']) ?>
                </strong>
                <button onclick="deleteFeedbackAjax(<?= $fb['id'] ?>)" style="background:none; border:none; color:#ef4444; font-size: 1rem; cursor:pointer;" title="Hapus Permanen">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
            
            <div style="background: #f1f5f9; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 3px solid #94a3b8;">
                <p style="font-size:0.9rem; color: #334155; margin: 0; line-height: 1.5;">
                    <?= htmlspecialchars($fb['pesan']) ?>
                </p>
            </div>

            <?php if($is_replied): ?>
                <div style="margin-bottom:15px; padding:12px 15px; background:#ecfdf5; border-radius:8px; border-left:4px solid #10b981;">
                    <small style="display:block; font-weight:800; color:#065f46; font-size:0.7rem; text-transform:uppercase; margin-bottom:5px;">Balasan Admin:</small>
                    <span style="font-size:0.85rem; color:#064e3b; font-weight:500;"><?= htmlspecialchars($fb['reply']) ?></span>
                </div>
            <?php endif; ?>

            <form action="admin_dashboard.php" method="POST" style="display:flex; gap:10px;">
                <input type="hidden" name="reply_feedback" value="1">
                <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                <input type="text" name="reply_text" 
                       placeholder="<?= $is_replied ? 'Ubah balasan...' : 'Tulis balasan...' ?>" 
                       required 
                       style="flex:1; padding:10px 15px; font-size:0.85rem; font-family:inherit; border-radius:8px; border:1px solid #cbd5e1; outline:none;">
                
                <button type="submit" style="background:#ae0001; color:white; border:none; padding:0 20px; border-radius:8px; font-weight:700; cursor:pointer; font-size:0.85rem; transition:0.2s;">
                    <i class="fa-solid fa-reply"></i> <?= $is_replied ? 'Update' : 'Balas' ?>
                </button>
            </form>
        </div>
    <?php endwhile; 
} else {
    echo "<div style='text-align:center; padding:30px; color:#94a3b8; font-weight:600;'><i class='fa-solid fa-inbox' style='font-size:2rem; margin-bottom:10px; display:block;'></i>Belum ada feedback.</div>";
}
?>