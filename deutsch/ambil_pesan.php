<?php
session_start();
// Gunakan kredensial yang sudah kamu tentukan


// Cek sesi agar aman
if (!isset($_SESSION['user_id'])) exit();

$current_uid = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');

// Ambil data obrolan terbaru
$chats = $conn->query("SELECT o.*, u.nama, u.role, u.id as sender_id FROM obrolan o JOIN users u ON o.user_id = u.id ORDER BY o.created_at ASC");

if ($chats->num_rows > 0) {
    while($c = $chats->fetch_assoc()): 
        $isMe = ($c['user_id'] == $current_uid);
        $isAdminMsg = ($c['role'] == 'admin');
        $class = $isAdminMsg ? 'admin' : ($isMe ? 'me' : 'others');
?>
    <div class="msg-row <?= $class ?>">
        <div class="bubble">
            <?php if(!$isMe): ?>
                <span class="sender-name"><?= $isAdminMsg ? '🛡️ SYSTEM' : htmlspecialchars($c['nama']) ?></span>
            <?php endif; ?>

            <div id="msg-text-<?= $c['id'] ?>">
                <?php if($c['tipe_pesan'] == 'teks'): ?>
                    <?= htmlspecialchars($c['pesan']) ?>
                <?php elseif($c['tipe_pesan'] == 'gambar'): ?>
                    <img src="<?= $c['file_path'] ?>" style="max-width:100%; border-radius:8px; cursor: pointer;" onclick="window.open(this.src)">
                <?php elseif($c['tipe_pesan'] == 'suara'): ?>
                    <audio controls style="height:30px; width:100%;"><source src="<?= $c['file_path'] ?>" type="audio/wav"></audio>
                <?php endif; ?>
            </div>

            <span class="time">
                <?= date('H:i', strtotime($c['created_at'])) ?>
                
                <?php if($isMe || $is_admin): ?>
                    <a href="javascript:void(0)" 
                       onclick="hapusPesan(<?= $c['id'] ?>)" 
                       style="color:red; font-size: 0.65rem; text-decoration:none; margin-left:10px; font-weight:bold;">
                       Hapus
                    </a>
                <?php endif; ?>
            </span>
        </div>
    </div>
<?php 
    endwhile; 
} else {
    echo "<p style='text-align:center; color:#888; padding:20px; font-size:0.8rem;'>Belum ada obrolan.</p>";
}
?>
