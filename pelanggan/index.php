<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di ParkirKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        brand: { orange: '#F57C00', pink: '#D81B60', dark: '#0F172A' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @keyframes pulse-slow { 0%, 100% { opacity: 0.5; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } }
        .bg-grid { background-image: linear-gradient(to right, #f1f5f9 1px, transparent 1px), linear-gradient(to bottom, #f1f5f9 1px, transparent 1px); background-size: 40px 40px; }
        .dark .bg-grid { background-image: linear-gradient(to right, #1e293b 1px, transparent 1px), linear-gradient(to bottom, #1e293b 1px, transparent 1px); opacity: 0.1; }
        .blob { position: absolute; filter: blur(80px); opacity: 0.4; z-index: -1; animation: pulse-slow 10s infinite; }
        
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        .dark .glass-card { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255,255,255,0.05); }
        
        /* CSS Kamera Responsif */
        .scanner-wrapper {
            width: 100%;
            aspect-ratio: 1 / 1; /* Kotak Presisi */
            max-height: 250px;
            margin: 0 auto;
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }
        
        #camera-reader { width: 100%; height: 100%; position: absolute; top: 0; left: 0; }
        #camera-reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }

        /* Media Queries untuk Responsivitas */
        @media (max-width: 640px) {
            .blob { width: 20rem; height: 20rem; filter: blur(50px); }
            h1 { font-size: 2.5rem; }
        }
    </style>
</head>
<body class="font-sans bg-slate-50 dark:bg-slate-900 min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 md:p-8 relative overflow-x-hidden transition-colors duration-300">

    <div class="blob bg-orange-300 dark:bg-orange-900/40 w-[20rem] sm:w-[30rem] md:w-[40rem] h-[20rem] sm:h-[30rem] md:h-[40rem] rounded-full top-0 left-0 -translate-x-1/3 -translate-y-1/3 fixed"></div>
    <div class="blob bg-pink-300 dark:bg-pink-900/40 w-[20rem] sm:w-[30rem] md:w-[40rem] h-[20rem] sm:h-[30rem] md:h-[40rem] rounded-full bottom-0 right-0 translate-x-1/3 translate-y-1/3 fixed"></div>
    <div class="fixed inset-0 bg-grid z-[-2] pointer-events-none"></div>

    <button id="theme-toggle" class="absolute top-4 right-4 sm:top-6 sm:right-6 p-2 sm:p-3 rounded-full bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 shadow-lg hover:scale-110 transition-transform duration-300 z-50">
        <i class="fas fa-moon dark:hidden text-lg"></i>
        <i class="fas fa-sun hidden dark:block text-lg text-yellow-400"></i>
    </button>

    <div class="container mx-auto max-w-5xl w-full relative z-10 my-6 sm:my-10">
        <header class="text-center mb-8 sm:mb-10">
            <div class="inline-flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-br from-orange-500 to-pink-600 rounded-2xl shadow-lg mb-3 sm:mb-4 text-white text-2xl sm:text-3xl">
                <i class="fas fa-parking"></i>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-800 dark:text-white tracking-tight mb-2">
                Parkir<span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-pink-600">Kita</span>
            </h1>
            <p class="text-base sm:text-lg text-slate-500 dark:text-slate-400">Gerbang Masuk Otomatis</p>
        </header>

        <?php if (isset($_SESSION['pesan'])): ?>
            <div class="max-w-2xl mx-auto w-full bg-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-100 dark:bg-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-900/30 border-l-4 border-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-500 text-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-700 dark:text-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-300 p-3 sm:p-4 mb-6 sm:mb-8 rounded-r-xl shadow-lg flex items-center gap-3 animate-bounce" role="alert">
                <i class="fas fa-info-circle text-lg sm:text-xl"></i>
                <p class="font-bold text-sm sm:text-base"><?= htmlspecialchars($_SESSION['pesan']); ?></p>
            </div>
            <?php unset($_SESSION['pesan']); unset($_SESSION['pesan_tipe']); ?>
        <?php endif; ?>

        <main class="w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 w-full items-stretch">
                
                <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-xl flex flex-col text-center items-center hover:-translate-y-2 transition-all duration-300 group h-full">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 flex-shrink-0 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-full mb-4 sm:mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-ticket-alt text-3xl sm:text-4xl"></i>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2 sm:mb-3">Pengunjung Umum</h2>
                    <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mb-6 sm:mb-8 flex-grow">Tekan tombol di bawah untuk mencetak tiket parkir masuk kendaraan Anda.</p>
                    
                    <form action="proses_masuk.php" method="POST" class="w-full mt-auto">
                        <input type="hidden" name="action" value="cetak_tiket">
                        <button type="submit" class="w-full py-3 sm:py-4 px-4 sm:px-6 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold text-base sm:text-lg rounded-xl shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transition-all flex items-center justify-center gap-2 sm:gap-3">
                            <i class="fas fa-print"></i> CETAK TIKET
                        </button>
                    </form>
                </div>

                <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-xl flex flex-col text-center items-center hover:-translate-y-2 transition-all duration-300 group h-full">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 flex-shrink-0 flex items-center justify-center bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 rounded-full mb-4 sm:mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-id-card text-3xl sm:text-4xl"></i>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2 sm:mb-3">Khusus Member</h2>
                    <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mb-4 sm:mb-6">Scan kartu member Anda pada alat scanner atau gunakan kamera.</p>
                    
                    <form action="proses_masuk.php" method="POST" id="member-scan-form" class="w-full mb-4">
                        <input type="hidden" name="action" value="scan_member">
                        
                        <div class="relative w-full group/input">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-barcode text-slate-400 group-focus-within/input:text-pink-500 transition-colors text-lg sm:text-xl"></i>
                            </div>
                            <input type="text" name="member_card_id" id="manual_input" autofocus autocomplete="off"
                                class="w-full pl-10 sm:pl-12 pr-4 py-3 sm:py-4 bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all text-base sm:text-lg font-bold text-center tracking-widest placeholder:font-normal placeholder:text-sm placeholder:tracking-normal text-slate-800 dark:text-white"
                                placeholder="Klik & Scan Kartu...">
                        </div>
                    </form>

                    <div class="scanner-wrapper mb-4 hidden shadow-inner" id="camera-container-wrapper">
                        <div id="camera-reader"></div>
                    </div>
                    
                    <div id="scan-status" class="font-medium text-slate-500 dark:text-slate-400 h-6 mb-2 text-xs sm:text-sm flex items-center justify-center gap-2">
                        <span class="animate-pulse">●</span> Siap menerima input...
                    </div>
                    
                    <button id="toggle-scan-btn" type="button" class="w-full mt-auto py-3 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-2 text-sm sm:text-base">
                        <i class="fas fa-camera"></i> <span>Gunakan Kamera HP</span>
                    </button>
                </div>
            </div>
        </main>
        
        <footer class="text-center mt-8 sm:mt-10 pb-4 sm:pb-6">
            <div id="live-clock" class="text-lg sm:text-xl font-bold text-slate-700 dark:text-slate-300 bg-white/50 dark:bg-black/20 backdrop-blur px-4 sm:px-6 py-2 rounded-full inline-block mb-2 shadow-sm"></div>
            <p class="text-xs sm:text-sm text-slate-400">&copy; <?= date('Y') ?> ParkirKita System</p>
        </footer>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Dark Mode Logic ---
        const themeToggle = document.getElementById('theme-toggle');
        
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        themeToggle.addEventListener('click', toggleTheme);

        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // --- 1. JAM DIGITAL ---
        function updateClock() {
            const clockElement = document.getElementById('live-clock');
            if (!clockElement) return;
            const now = new Date();
            const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            // Responsive date format: shorter on mobile
            const isMobile = window.innerWidth < 640;
            const dateString = isMobile 
                ? now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: '2-digit' })
                : now.toLocaleDateString('id-ID', options);
                
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            clockElement.textContent = `${dateString} • ${timeString}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- 2. LOGIKA INPUT SCANNER (TEXT BOX) ---
        const manualInput = document.getElementById('manual_input');
        const memberForm = document.getElementById('member-scan-form');
        const scanStatus = document.getElementById('scan-status');

        // Fitur Auto Focus
        document.addEventListener('click', function(e) {
            const target = e.target;
            const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
            const isButton = target.tagName === 'BUTTON' || target.closest('button') || target.closest('a');
            
            // Hanya autofocus jika bukan di mobile (di mobile keyboard virtual mengganggu)
            if (!isInput && !isButton && window.innerWidth > 768) {
                manualInput.focus();
            }
        });
        
        if (window.innerWidth > 768) {
            manualInput.focus();
        }

        manualInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== "") {
                    scanStatus.innerHTML = `<span class="text-green-600 dark:text-green-400 font-bold"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>`;
                    setTimeout(() => memberForm.submit(), 200);
                } else {
                    scanStatus.innerHTML = `<span class="text-red-500 dark:text-red-400 font-bold">Kode kosong!</span>`;
                    setTimeout(() => {
                        scanStatus.innerHTML = `<span class="animate-pulse">●</span> Siap menerima input...`;
                    }, 2000);
                }
            }
        });

        // --- 3. LOGIKA KAMERA SCANNER (HTML5-QRCODE) ---
        const toggleBtn = document.getElementById('toggle-scan-btn');
        const cameraWrapper = document.getElementById('camera-container-wrapper');
        let isScannerActive = false;
        let html5QrCode = null;

        function onCameraScanSuccess(decodedText, decodedResult) {
            manualInput.value = decodedText;
            scanStatus.innerHTML = `<span class="text-green-600 dark:text-green-400 font-bold"><i class="fas fa-check-circle"></i> Scan Berhasil!</span>`;
            stopScanner();
            setTimeout(() => memberForm.submit(), 300);
        }

        function startScanner() {
            cameraWrapper.classList.remove('hidden');
            scanStatus.innerHTML = `<i class="fas fa-spinner fa-spin text-orange-500"></i> Memulai kamera...`;

            html5QrCode = new Html5Qrcode("camera-reader");
            // QRBox Lebih Besar agar mudah scan
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            html5QrCode.start({ facingMode: "environment" }, config, onCameraScanSuccess)
            .then(() => {
                isScannerActive = true;
                updateButtonState(true);
                scanStatus.innerHTML = `<i class="fas fa-camera text-pink-500"></i> Kamera aktif. Arahkan kode...`;
            }).catch(err => {
                console.error("Gagal memulai scanner:", err);
                scanStatus.innerHTML = `<i class="fas fa-exclamation-triangle text-red-500"></i> Gagal akses kamera.`;
                stopScanner();
            });
        }

        function stopScanner() {
            if (html5QrCode && isScannerActive) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                }).catch(err => console.warn(err));
            }
            isScannerActive = false;
            updateButtonState(false);
            cameraWrapper.classList.add('hidden');
            scanStatus.innerHTML = `<span class="animate-pulse">●</span> Siap menerima input...`;
            manualInput.focus();
        }

        function updateButtonState(isScanning) {
            const icon = toggleBtn.querySelector('i');
            const text = toggleBtn.querySelector('span');
            if (isScanning) {
                toggleBtn.classList.replace('bg-white', 'bg-red-50');
                toggleBtn.classList.replace('dark:bg-slate-800', 'dark:bg-red-900/30');
                toggleBtn.classList.replace('border-slate-200', 'border-red-200');
                toggleBtn.classList.replace('text-slate-700', 'text-red-600');
                toggleBtn.classList.replace('dark:text-slate-300', 'dark:text-red-400');
                icon.className = 'fas fa-stop-circle';
                text.textContent = 'Tutup Kamera';
            } else {
                toggleBtn.className = "w-full mt-auto py-3 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-2 text-sm sm:text-base";
                icon.className = 'fas fa-camera';
                text.textContent = 'Gunakan Kamera HP';
            }
        }

        toggleBtn.addEventListener('click', () => {
            if (isScannerActive) stopScanner();
            else startScanner();
        });
    });
    </script>
</body>
</html>
