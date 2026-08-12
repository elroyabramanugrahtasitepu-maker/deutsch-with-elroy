<?php
session_start();


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Koneksi database gagal. Silakan hubungi administrator.");
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $error = "Ihr Konto wurde vom Administrator gesperrt! (Akun Anda diblokir admin!)"; 
        } 
        else if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: " . ($_SESSION['role'] === 'admin' ? "admin_dashboard.php" : "index.php"));
            exit();
        } else {
            $error = "Passwort ist falsch! (Password salah!)";
        }
    } else {
        $error = "Benutzername nicht gefunden! (Username tidak ditemukan!)";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Deutsch with Elroy</title>
    <link rel="icon" type="image/png" href="logo_website/gambar.1.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0f0f11;
            /* Efek kaca untuk form */
            --glass-bg: rgba(18, 18, 20, 0.45);
            --glass-border: rgba(255, 255, 255, 0.08);
            --germany-red: #e3000f;
            --germany-red-gradient: linear-gradient(135deg, #e3000f, #ff3b4a);
            --germany-gold: #ffcc00;
            --germany-gold-gradient: linear-gradient(135deg, #ffcc00, #ffdb4d);
            --text-main: #ffffff;
            --text-muted: #8a8a93;
            --smooth-transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Pindahkan gambar background ke body agar full screen */
            background-color: var(--bg-dark);
            background-image: url('https://images.unsplash.com/photo-1599946347371-68eb71b16afc?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            overflow: hidden;
            display: flex;
        }

        /* --- SPLIT LAYOUT (DESKTOP) --- */
        .split-layout {
            display: flex;
            width: 100%;
            height: 100vh;
            /* Gradasi hitam tipis agar teks tetap jelas terbaca di atas gambar */
            background: linear-gradient(to right, rgba(10, 10, 12, 0.9) 0%, rgba(10, 10, 12, 0.6) 50%, rgba(10, 10, 12, 0.1) 100%);
            position: relative;
        }

        .split-layout::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; width: 100%; height: 6px;
            background: linear-gradient(to right, #111 33.3%, var(--germany-red) 33.3%, var(--germany-red) 66.6%, var(--germany-gold) 66.6%);
            box-shadow: 0 -4px 15px rgba(227, 0, 15, 0.2);
            z-index: 10;
        }

        /* --- LEFT SIDE (BRANDING & SLIDER) --- */
        .left-panel {
            flex: 1.3; 
            background: transparent; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 8%;
            position: relative;
            z-index: 2;
        }

        /* --- SLIDER GESER (HORIZONTAL) --- */
        .slider-wrapper {
            width: 100%;
            overflow: hidden; 
            position: relative;
        }

        .slider-track {
            display: flex;
            width: 300%; 
            transition: transform 0.8s cubic-bezier(0.25, 1, 0.5, 1); 
        }

        .slide {
            width: 33.333%; 
            flex-shrink: 0;
            padding-right: 40px; 
            opacity: 0.4;
            transition: var(--smooth-transition);
            transform: translateY(20px);
        }

        .slide.active-slide {
            opacity: 1;
            transform: translateY(0);
        }

        .slide h1 {
            font-size: 3.5rem; /* Disesuaikan sedikit agar nama yang lebih panjang tetap muat */
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -1px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .slide h1 span { 
            color: var(--germany-gold); 
            display: inline-block;
        }

        .slide p {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.85);
            max-width: 500px;
            line-height: 1.7;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }

        /* --- INDIKATOR TITIK --- */
        .slider-dots {
            display: flex;
            gap: 12px;
            margin-top: 50px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: var(--smooth-transition);
        }

        .dot:hover { background-color: rgba(255, 255, 255, 0.5); }

        .dot.active {
            background-color: var(--germany-gold);
            width: 35px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 204, 0, 0.4);
        }

        /* --- RIGHT SIDE (FORM - TRANSPARAN) --- */
        .right-panel {
            flex: 1;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-left: 1px solid var(--glass-border);
            
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            z-index: 2;
            box-shadow: -20px 0 50px rgba(0,0,0,0.3); 
        }

        .form-container {
            width: 100%;
            max-width: 420px;
        }

        .stagger-item {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.3s; }
        .delay-3 { animation-delay: 0.4s; }
        .delay-4 { animation-delay: 0.5s; }
        .delay-5 { animation-delay: 0.6s; }

        .form-header { margin-bottom: 40px; }
        
        .form-header h2 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .form-header p {
            color: #b5b5bf;
            font-size: 1rem;
        }

        /* --- INPUT STYLES --- */
        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: var(--smooth-transition);
            pointer-events: none;
        }

        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--smooth-transition);
            padding: 5px; 
        }

        .toggle-password:hover { color: var(--germany-gold); transform: translateY(-50%) scale(1.1); }

        input {
            width: 100%;
            padding: 18px 50px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: white;
            font-size: 1.05rem;
            outline: none;
            transition: var(--smooth-transition);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
        }

        input:hover {
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.2);
        }

        input:focus {
            background: rgba(0, 0, 0, 0.5);
            border-color: var(--germany-red);
            box-shadow: 0 0 0 4px rgba(227, 0, 15, 0.15), inset 0 2px 4px rgba(0,0,0,0.3);
        }

        input:focus + .input-icon, input:not(:placeholder-shown) + .input-icon {
            color: var(--germany-red);
        }

        /* --- BUTTONS --- */
        button.btn-login {
            width: 100%;
            padding: 18px;
            background: var(--germany-red-gradient);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 14px;
            transition: var(--smooth-transition);
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 20px rgba(227, 0, 15, 0.25);
        }

        button.btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(227, 0, 15, 0.4);
            filter: brightness(1.1);
        }
        
        button.btn-login:active { transform: translateY(1px); }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 35px 0;
            color: #b5b5bf;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .divider:not(:empty):before { margin-right: 1em; }
        .divider:not(:empty):after { margin-left: 1em; }

        .wa-button {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            background: var(--germany-gold-gradient); 
            color: #1a1a1f; 
            border: none;
            text-decoration: none;
            padding: 18px;
            border-radius: 14px;
            font-weight: 700; 
            font-size: 1.05rem;
            transition: var(--smooth-transition);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.15);
        }

        .wa-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(255, 204, 0, 0.3); 
            filter: brightness(1.05);
        }
        
        .wa-button:active { transform: translateY(1px); }

        .error-msg {
            background: rgba(227, 0, 15, 0.15);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(227, 0, 15, 0.4);
            border-left: 4px solid var(--germany-red);
            padding: 16px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        .error-msg i { color: var(--germany-red); font-size: 1.2rem; }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        /* =========================================
           RESPONSIVE DESIGN 
           ========================================= */
        @media (max-width: 1024px) {
            body { overflow-y: auto; display: block; }
            .split-layout {
                flex-direction: column; 
                height: auto;
                min-height: 100vh;
                background: rgba(10, 10, 12, 0.6); 
            }
            .left-panel {
                flex: none; width: 100%; min-height: 50vh; 
                padding: 60px 30px; align-items: center;
            }
            .slide {
                padding-right: 0; display: flex; flex-direction: column;
                align-items: center; text-align: center;
            }
            .slider-wrapper { max-width: 700px; margin: 0 auto; }
            .slider-dots { justify-content: center; margin-top: 35px; }
            
            .right-panel {
                flex: none; width: 100%; min-height: 50vh; 
                padding: 60px 20px;
                border-left: none;
                border-top: 1px solid var(--glass-border);
            }
            .form-container { margin: 0 auto; }
        }

        @media (max-width: 600px) {
            .left-panel { min-height: 40vh; padding: 50px 20px; }
            .slide h1 { font-size: 2.2rem; margin-bottom: 15px; } /* Diperkecil untuk HP agar Deutsch with Elroy muat */
            .slide p { font-size: 1rem; }
            .right-panel { padding: 50px 20px; }
            .form-header h2 { font-size: 1.9rem; }
            input { padding: 16px 45px; }
            button.btn-login, .wa-button { padding: 16px; }
        }
    </style>
</head>
<body>

    <div class="split-layout">
        
        <div class="left-panel">
            <div class="slider-wrapper">
                <div class="slider-track" id="sliderTrack">
                    
                    <div class="slide active-slide">
                        <h1>Willkommen bei<br><span>Deutsch with Elroy</span></h1>
                        <p>Entdecke eine neue Welt durch Sprachen. Dein Weg zu fließenden Sprachkenntnissen beginnt genau hier.</p>
                    </div>

                    <div class="slide">
                        <h1>Meistere<br><span>Neue Sprachen</span></h1>
                        <p>Mulai perjalananmu dengan bahasa Jerman, dan nantikan berbagai <strong>bahasa dunia lainnya</strong>! Belajar jadi lebih mudah, interaktif, dan terarah.</p>
                    </div>

                    <div class="slide">
                        <h1>Starte deine<br><span>Lernreise</span></h1>
                        <p><strong>Belum punya akun?</strong> Pendaftaran eksklusif khusus member. Klik tombol <strong>WhatsApp</strong> di bawah untuk mendapatkan akses belajarmu sekarang.</p>
                    </div>

                </div>
            </div>
            
            <div class="slider-dots">
                <span class="dot active" onclick="goToSlide(0)"></span>
                <span class="dot" onclick="goToSlide(1)"></span>
                <span class="dot" onclick="goToSlide(2)"></span>
            </div>
        </div>

        <div class="right-panel">
            <div class="form-container">
                
                <div class="form-header stagger-item delay-1">
                    <h2>Anmelden</h2>
                    <p>Bitte loggen Sie sich in Ihr Konto ein.</p>
                </div>

                <?php if($error): ?>
                    <div class="error-msg stagger-item delay-1">
                        <i class="fas fa-exclamation-circle"></i> 
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group stagger-item delay-2">
                        <input type="text" name="username" id="username" placeholder=" " required autocomplete="off">
                        <label for="username" style="position: absolute; left: 50px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; transition: 0.3s opacity; opacity: 1;">Benutzername</label>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    
                    <div class="input-group stagger-item delay-3">
                        <input type="password" name="password" id="password" placeholder=" " required>
                        <label for="password" style="position: absolute; left: 50px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; transition: 0.3s opacity; opacity: 1;">Passwort</label>
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>

                    <button type="submit" class="btn-login stagger-item delay-4" id="loginBtn">
                        Anmelden <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="divider stagger-item delay-5">oder</div>

                <a href="https://wa.me/6282363131543?text=Hallo%20Admin,%20ich%20möchte%20mich%20bei%20Deutsch%20with%20Elroy%20registrieren.%20(Saya%20ingin%20daftar%20akun)" 
                   target="_blank" 
                   class="wa-button stagger-item delay-5">
                    <i class="fab fa-whatsapp"></i> Registrierung via WhatsApp
                </a>

            </div>
        </div>

    </div>

    <script>
        // Placeholder animasi
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            const label = input.nextElementSibling;
            if(input.value.trim() !== "") label.style.opacity = '0';
            
            input.addEventListener('input', () => {
                if(input.value.trim() !== "") {
                    label.style.opacity = '0';
                } else {
                    label.style.opacity = '1';
                }
            });
        });

        // Toggle password
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Loading state
        const loginForm = document.querySelector('form');
        const loginBtn = document.querySelector('#loginBtn');

        loginForm.addEventListener('submit', function() {
            loginBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Lade...';
            loginBtn.style.opacity = '0.9';
            loginBtn.style.transform = 'scale(0.98)';
            loginBtn.style.pointerEvents = 'none';
        });

        // Slider Horizontal
        const track = document.getElementById('sliderTrack');
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        let currentIndex = 0;
        const totalSlides = dots.length;
        let slideInterval;

        function updateSlider() {
            track.style.transform = `translateX(-${currentIndex * 33.333}%)`;
            
            dots.forEach(dot => dot.classList.remove('active'));
            dots[currentIndex].classList.add('active');

            slides.forEach(slide => slide.classList.remove('active-slide'));
            slides[currentIndex].classList.add('active-slide');
        }

        function nextSlide() {
            currentIndex++;
            if (currentIndex >= totalSlides) currentIndex = 0; 
            updateSlider();
        }

        function startAutoSlide() {
            slideInterval = setInterval(nextSlide, 5000); 
        }

        function goToSlide(index) {
            currentIndex = index;
            updateSlider();
            clearInterval(slideInterval); 
            startAutoSlide();
        }

        startAutoSlide();
    </script>
</body>
</html>