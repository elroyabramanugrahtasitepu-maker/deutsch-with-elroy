<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}



$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$current_uid = (int)$_SESSION['user_id'];
$is_admin    = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

/* --- Cek Status Banned (Prepared Statement) --- */
$stmt_check = $conn->prepare("SELECT is_banned FROM users WHERE id = ?");
$stmt_check->bind_param("i", $current_uid);
$stmt_check->execute();
$check_user = $stmt_check->get_result()->fetch_assoc();

if ($check_user && $check_user['is_banned'] == 1) {
    session_destroy(); 
    echo "<script>alert('Your account has been banned! (Akun Anda telah diblokir)'); window.location.href='login.php';</script>";
    exit();
}

/* --- LOGIKA HAPUS PESAN --- */
if (isset($_GET['delete_chat'])) {
    $chat_id = (int)$_GET['delete_chat'];
    
    if ($is_admin) {
        $stmt_del = $conn->prepare("DELETE FROM obrolan WHERE id = ?");
        $stmt_del->bind_param("i", $chat_id);
    } else {
        $stmt_del = $conn->prepare("DELETE FROM obrolan WHERE id = ? AND user_id = ?");
        $stmt_del->bind_param("ii", $chat_id, $current_uid);
    }
    $stmt_del->execute();
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        exit();
    }
    header("Location: obrolan_en.php"); 
    exit();
}

/* --- LOGIKA KIRIM & EDIT PESAN --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_chat'])) {
    $pesan   = trim($_POST['pesan'] ?? '');
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if ($edit_id > 0) {
        if ($is_admin) {
            $stmt_edit = $conn->prepare("UPDATE obrolan SET pesan = ? WHERE id = ?");
            $stmt_edit->bind_param("si", $pesan, $edit_id);
        } else {
            $stmt_edit = $conn->prepare("UPDATE obrolan SET pesan = ? WHERE id = ? AND user_id = ?");
            $stmt_edit->bind_param("sii", $pesan, $edit_id, $current_uid);
        }
        $stmt_edit->execute();
    } else {
        $tipe = 'teks'; 
        $path = '';

        if (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_ext)) {
                $tipe = 'gambar';
                if (!is_dir('uploads')) { mkdir('uploads', 0755, true); }
                $path = 'uploads/img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $path);
            }
        } elseif (!empty($_FILES['voice_data']['name']) && $_FILES['voice_data']['error'] === UPLOAD_ERR_OK) {
            $tipe = 'suara';
            if (!is_dir('uploads')) { mkdir('uploads', 0755, true); }
            $path = 'uploads/vn_' . time() . '_' . bin2hex(random_bytes(4)) . '.wav';
            move_uploaded_file($_FILES['voice_data']['tmp_name'], $path);
        }

        if (!empty($pesan) || !empty($path)) {
            $stmt_ins = $conn->prepare("INSERT INTO obrolan (user_id, pesan, tipe_pesan, file_path) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("isss", $current_uid, $pesan, $tipe, $path);
            $stmt_ins->execute();
        }
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        exit();
    }
    header("Location: obrolan_en.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Campfire Chat | Elroy English</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-body: #0b0f17;
            --bg-sidebar: #111827;
            --bg-chat: #0f172a;
            --bg-card: #1e293b;
            --bg-header: #1e293b;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --accent: #10b981;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --bubble-me: #2563eb;
            --bubble-other: #1e293b;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100%;
            height: 100dvh;
            width: 100%;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-main);
            -webkit-tap-highlight-color: transparent;
        }

        .app-container {
            display: flex;
            width: 100%;
            height: 100%;
            position: relative;
        }

        .sidebar { 
            width: 320px;
            min-width: 320px;
            background: var(--bg-sidebar); 
            display: flex; 
            flex-direction: column; 
            border-right: 1px solid var(--border-color); 
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-header { 
            padding: 20px; 
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .user-nav-card { 
            padding: 14px; 
            background: var(--bg-card); 
            margin: 16px; 
            border-radius: 14px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            border: 1px solid var(--border-color);
        }

        .avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            color: white;
            flex-shrink: 0;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
            box-shadow: 0 0 8px var(--accent);
        }

        .nav-link {
            color: var(--text-muted); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            font-weight: 600;
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }

        .chat-main { 
            flex: 1;
            display: flex; 
            flex-direction: column;
            height: 100%; 
            min-width: 0;
            background: var(--bg-chat); 
            position: relative;
        }

        .chat-header { 
            height: 65px;
            min-height: 65px;
            background: var(--bg-header); 
            padding: 0 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            z-index: 10;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .mobile-toggle-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 1.3rem;
            cursor: pointer;
            margin-right: 8px;
        }

        .flag-badge {
            width: 38px;
            height: 38px;
            background: #1e293b;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            color: #f59e0b;
        }

        .chat-window { 
            flex: 1;
            min-height: 0;
            overflow-y: auto; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 14px; 
            -webkit-overflow-scrolling: touch;
        }

        .msg-row { display: flex; width: 100%; }
        .msg-row.me { justify-content: flex-end; }
        .msg-row.others { justify-content: flex-start; }

        .bubble { 
            max-width: 75%; 
            padding: 10px 14px; 
            border-radius: 16px; 
            font-size: 0.92rem; 
            position: relative; 
            line-height: 1.45; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .bubble img, .bubble video, .bubble audio {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 6px;
            display: block;
        }

        .me .bubble { 
            background: var(--bubble-me); 
            color: #ffffff;
            border-bottom-right-radius: 4px; 
        }

        .others .bubble { 
            background: var(--bubble-other); 
            color: var(--text-main);
            border-bottom-left-radius: 4px; 
            border: 1px solid var(--border-color);
        }

        .sender-name { 
            font-size: 0.72rem; 
            font-weight: 700; 
            color: #60a5fa; 
            display: block; 
            margin-bottom: 2px; 
        }

        .time { 
            font-size: 0.62rem; 
            opacity: 0.7;
            display: block; 
            text-align: right; 
            margin-top: 4px; 
            font-weight: 500; 
        }

        .input-bar { 
            min-height: 65px;
            background: var(--bg-header); 
            padding: 10px 16px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            border-top: 1px solid var(--border-color); 
        }

        .input-container { flex: 1; position: relative; min-width: 0; }
        .input-container input { 
            width: 100%; 
            padding: 12px 18px; 
            border-radius: 25px; 
            border: 1px solid var(--border-color); 
            background: var(--bg-body);
            color: var(--text-main);
            outline: none; 
            font-size: 0.9rem; 
            transition: all 0.2s;
        }

        .input-container input:focus { 
            border-color: var(--primary); 
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .btn-action { 
            color: var(--text-muted); 
            font-size: 1.2rem; 
            cursor: pointer; 
            transition: 0.2s; 
            background: none; 
            border: none; 
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .btn-action:hover { 
            color: var(--text-main); 
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-send { 
            background: var(--primary); 
            color: white; 
            width: 42px; 
            height: 42px; 
            border-radius: 50%; 
            border: none; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); 
            transition: 0.2s; 
            flex-shrink: 0;
        }

        .btn-send:hover { 
            background: var(--primary-hover); 
            transform: scale(1.05); 
        }

        #recordingStatus { 
            display: none; 
            position: absolute; 
            left: 0; 
            top: 0;
            width: 100%; 
            height: 100%; 
            background: #ef4444; 
            align-items: center; 
            padding: 0 16px; 
            border-radius: 25px; 
            color: #ffffff; 
            font-weight: 700; 
            font-size: 0.8rem; 
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            z-index: 5;
        }

        .overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 90;
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 10px; }

        @media (max-width: 768px) { 
            .sidebar {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                transform: translateX(-100%);
                box-shadow: 10px 0 25px rgba(0,0,0,0.5);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay.active {
                display: block;
            }

            .mobile-toggle-btn {
                display: block;
            }

            .bubble { max-width: 88%; } 
            .chat-window { padding: 14px; }
            .input-bar { padding: 10px; gap: 6px; } 
        }
    </style>
</head>
<body>

    <div class="app-container">
        <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="brand-box">
                    <div class="brand-logo"><i class="fa-solid fa-fire-flame-curved"></i></div>
                    <div>
                        <h2 style="font-size:1rem; font-weight:800;">ELROY ENGLISH</h2>
                        <span style="font-size:0.68rem; color:var(--text-muted);">Campfire Community</span>
                    </div>
                </div>
                <button class="btn-action mobile-toggle-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="user-nav-card">
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; font-size:0.88rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
                    </div>
                    <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                        <span class="status-dot"></span> Online (Active)
                    </div>
                </div>
            </div>

            <div style="padding: 16px; margin-top: auto;">
                <a href="english.php" class="nav-link">
                    <i class="fa-solid fa-house"></i> Back to Village
                </a>
            </div>
        </div>

        <div class="chat-main">
            <header class="chat-header">
                <div class="chat-header-info">
                    <button class="mobile-toggle-btn" onclick="toggleSidebar()" title="Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="flag-badge"><i class="fa-solid fa-campground"></i></div>
                    <div style="min-width:0;">
                        <div style="font-weight:800; font-size:0.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Campfire Chatroom</div>
                        <div style="font-size:0.7rem; color:var(--accent); font-weight:600;">
                            ● English Discussion & Practice
                        </div>
                    </div>
                </div>
            </header>

            <div class="chat-window" id="chatWindow">
                <p style="text-align:center; color:var(--text-muted); font-size: 0.8rem;">Loading messages...</p>
            </div>

            <form action="obrolan_en.php" method="POST" enctype="multipart/form-data" class="input-bar" id="chatForm">
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                
                <label class="btn-action" title="Upload Image">
                    <i class="fa-solid fa-image"></i>
                    <input type="file" name="gambar" id="gambarInput" accept="image/*" style="display:none;" onchange="alert('Image ready to send!')">
                </label>

                <button type="button" class="btn-action" id="recordBtn" title="Voice Note">
                    <i class="fa-solid fa-microphone"></i>
                </button>

                <div class="input-container">
                    <input type="text" name="pesan" id="textInput" placeholder="Write a message in English..." autocomplete="off">
                    <div id="recordingStatus"><i class="fa-solid fa-circle-dot fa-beat"></i> &nbsp; Recording audio...</div>
                </div>

                <input type="hidden" name="send_chat" value="1">
                <div id="audioStore"></div>

                <button type="submit" class="btn-send" title="Send">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const chatWindow = document.getElementById('chatWindow');
        const chatForm = document.getElementById('chatForm');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        let isAutoScroll = true;

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        chatWindow.onscroll = function() {
            isAutoScroll = (chatWindow.scrollTop + chatWindow.clientHeight >= chatWindow.scrollHeight - 50);
        };

        // SMART DOM PARSING: MENCEGAH KEDIP DI SEMUA DEVICE
        function loadMessages() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'ambil_pesan.php?t=' + new Date().getTime(), true);
            xhr.onload = function() {
                if (this.status !== 200) return;

                const parser = new DOMParser();
                const doc = parser.parseFromString(this.responseText, 'text/html');
                const newMsgs = Array.from(doc.querySelectorAll('.msg-row'));
                const currentMsgs = Array.from(chatWindow.querySelectorAll('.msg-row'));

                // Pemuatan Pertama
                if (currentMsgs.length === 0) {
                    chatWindow.innerHTML = this.responseText;
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                    return;
                }

                // Append Pesan Baru Tanpa Re-render DOM Lama (Tanpa Kedip)
                if (newMsgs.length > currentMsgs.length) {
                    for (let i = currentMsgs.length; i < newMsgs.length; i++) {
                        chatWindow.appendChild(doc.importNode(newMsgs[i], true));
                    }
                    if (isAutoScroll) {
                        chatWindow.scrollTop = chatWindow.scrollHeight;
                    }
                    return;
                }

                // Update Pesan Jika Ada Edit
                if (newMsgs.length === currentMsgs.length) {
                    newMsgs.forEach((newEl, idx) => {
                        if (currentMsgs[idx] && currentMsgs[idx].innerHTML !== newEl.innerHTML) {
                            currentMsgs[idx].innerHTML = newEl.innerHTML;
                        }
                    });
                    return;
                }

                // Update Total Jika Ada Pesan Dihapus
                if (newMsgs.length < currentMsgs.length) {
                    chatWindow.innerHTML = this.responseText;
                }
            };
            xhr.send();
        }

        function hapusPesan(id) {
            if(confirm('Delete message? (Hapus pesan ini?)')) {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'obrolan_en.php?delete_chat=' + id, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function() {
                    loadMessages();
                };
                xhr.send();
            }
        }

        chatForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'obrolan_en.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                document.getElementById('textInput').value = '';
                document.getElementById('edit_id').value = '0';
                document.getElementById('gambarInput').value = '';
                document.getElementById('audioStore').innerHTML = '';
                isAutoScroll = true;
                loadMessages();
            };
            xhr.send(formData);
        };

        function editMsg(id) {
            const msgElem = document.getElementById('msg-text-' + id);
            if (msgElem) {
                document.getElementById('textInput').value = msgElem.innerText.trim();
                document.getElementById('edit_id').value = id;
                document.getElementById('textInput').focus();
            }
        }

        setInterval(loadMessages, 1500);
        window.onload = loadMessages;

        let mediaRecorder; 
        let chunks = [];
        const recordBtn = document.getElementById('recordBtn');

        if(recordBtn) {
            recordBtn.onclick = async () => {
                if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        mediaRecorder = new MediaRecorder(stream);
                        chunks = [];
                        mediaRecorder.start();
                        
                        document.getElementById('recordingStatus').style.display = 'flex';
                        recordBtn.style.color = "#ef4444";
                        
                        mediaRecorder.ondataavailable = e => chunks.push(e.data);
                        mediaRecorder.onstop = () => {
                            const blob = new Blob(chunks, { type: 'audio/wav' });
                            const file = new File([blob], "vn.wav", { type: "audio/wav" });
                            const dt = new DataTransfer(); 
                            dt.items.add(file);
                            
                            const input = document.createElement('input');
                            input.type = 'file'; 
                            input.name = 'voice_data'; 
                            input.style.display = 'none';
                            input.files = dt.files;
                            
                            const audioStore = document.getElementById('audioStore');
                            audioStore.innerHTML = '';
                            audioStore.appendChild(input);
                            
                            document.getElementById('recordingStatus').style.display = 'none';
                            recordBtn.style.color = "var(--text-muted)";
                            alert("Audio ready to send!");
                        };
                    } catch (err) { 
                        alert("Microphone access denied or error."); 
                    }
                } else { 
                    mediaRecorder.stop(); 
                }
            };
        }
    </script>
</body>
</html>