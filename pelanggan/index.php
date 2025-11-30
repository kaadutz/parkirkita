<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di ParkirKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { opacity: 0; animation: fadeInUp 0.6s ease-out forwards; }
        .scanner-container {
            width: 100%; max-width: 280px; height: 200px; /* Sedikit dikecilkan agar muat */
            margin: 0 auto; position: relative; background: #f8fafc;
            border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0;
        }
        #camera-reader { width: 100%; height: 100%; }
        #camera-reader video { width: 100% !important; height: 100% !important; object-fit: cover; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-indigo-100 flex items-center justify-center min-h-screen p-4">

    <div class="container mx-auto max-w-5xl w-full">
        <header class="text-center mb-6 fade-in-up">
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight">
                <i class="fas fa-parking text-orange-500"></i> Parkir<span class="text-pink-600">Kita</span>
            </h1>
            <p class="text-base md:text-lg text-slate-500 mt-1">Sistem Parkir Modern dan Terintegrasi</p>
        </header>

        <?php if (isset($_SESSION['pesan'])): ?>
            <div class="max-w-2xl mx-auto w-full bg-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-100 border-l-4 border-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-500 text-<?= $_SESSION['pesan_tipe'] == 'sukses' ? 'green' : 'red'; ?>-700 p-3 mb-6 rounded-r-lg shadow-md fade-in-up" style="animation-delay: 0.2s;" role="alert">
                <p class="font-bold text-sm"><?= htmlspecialchars($_SESSION['pesan']); ?></p>
            </div>
            <?php unset($_SESSION['pesan']); unset($_SESSION['pesan_tipe']); ?>
        <?php endif; ?>

        <main class="w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                <!-- CARD PENGUNJUNG UMUM -->
                <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col text-center items-center transform transition-all duration-300 hover:scale-105 hover:shadow-2xl fade-in-up" style="animation-delay: 0.4s;">
                    <div class="w-20 h-20 flex items-center justify-center bg-orange-100 rounded-full mb-3"><i class="fas fa-ticket-alt text-4xl text-orange-500"></i></div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-800 mb-2">Pengunjung Umum</h2>
                    <p class="text-slate-600 mb-4 flex-grow text-sm md:text-base">Tekan tombol di bawah untuk mencetak tiket parkir masuk.</p>
                    <form action="proses_masuk.php" method="POST" class="w-full mt-auto">
                        <input type="hidden" name="action" value="cetak_tiket">
                        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold text-base md:text-lg py-4 px-4 rounded-xl shadow-lg transition-transform duration-200 hover:translate-y-[-2px]"><i class="fas fa-print mr-2"></i> CETAK TIKET</button>
                    </form>
                </div>

                <!-- CARD KHUSUS MEMBER -->
                <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col text-center items-center transform transition-all duration-300 hover:scale-105 hover:shadow-2xl fade-in-up" style="animation-delay: 0.6s;">
                    <div class="w-20 h-20 flex items-center justify-center bg-indigo-100 rounded-full mb-3"><i class="fas fa-id-card text-4xl text-indigo-600"></i></div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-800 mb-2">Khusus Member</h2>
                    <p class="text-slate-600 mb-2 text-sm">Scan barcode pada kartu member.</p>
                    
                    <!-- FORM INPUT SCANNER -->
                    <form action="proses_masuk.php" method="POST" id="member-scan-form" class="w-full mb-3">
                        <input type="hidden" name="action" value="scan_member">
                        
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-barcode text-indigo-400 text-lg"></i>
                            </div>
                            <!-- INPUT BOX KHUSUS SCANNER -->
                            <input type="text" name="member_card_id" id="manual_input" autofocus autocomplete="off"
                                class="w-full pl-10 pr-3 py-3 border-2 border-indigo-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors text-lg font-bold text-center tracking-widest placeholder:font-normal placeholder:text-sm placeholder:tracking-normal text-slate-700"
                                placeholder="Klik di sini & Scan Kartu...">
                        </div>
                    </form>

                    <!-- AREA KAMERA (OPSIONAL) -->
                    <div class="scanner-container mb-3 hidden" id="camera-container-wrapper">
                        <div id="camera-reader" class="w-full h-full"></div>
                    </div>
                    
                    <div id="scan-status" class="font-medium text-slate-500 h-5 mb-2 text-xs">Siap menerima input scanner...</div>
                    
                    <button id="toggle-scan-btn" type="button" class="w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold py-2 px-4 rounded-xl transition-all duration-300 border border-indigo-200 text-sm mt-auto">
                        <i class="fas fa-camera mr-2"></i><span>Gunakan Kamera HP</span>
                    </button>
                </div>
            </div>
        </main>
        
        <footer class="text-center mt-6 fade-in-up" style="animation-delay: 0.8s;">
            <p id="live-clock" class="text-base font-semibold text-slate-600"></p>
            <p class="text-sm text-slate-500">&copy; <?= date('Y') ?> ParkirKita. Hak Cipta Dilindungi.</p>
        </footer>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. JAM DIGITAL ---
        function updateClock() {
            const clockElement = document.getElementById('live-clock');
            if (!clockElement) return;
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            clockElement.textContent = `${now.toLocaleDateString('id-ID', options)} | ${now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- 2. LOGIKA INPUT SCANNER (TEXT BOX) ---
        const manualInput = document.getElementById('manual_input');
        const memberForm = document.getElementById('member-scan-form');
        const scanStatus = document.getElementById('scan-status');

        // Fitur Auto Focus (Agar scanner selalu ngetik di kotak input)
        // Jika user klik sembarang tempat di luar tombol/input, kembalikan fokus ke input box
        document.addEventListener('click', function(e) {
            const target = e.target;
            const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
            const isButton = target.tagName === 'BUTTON' || target.closest('button');
            
            if (!isInput && !isButton) {
                manualInput.focus();
            }
        });

        // Pastikan fokus saat load
        manualInput.focus();

        // Listener saat user mengetik (atau scanner mengetik cepat)
        manualInput.addEventListener('keypress', function(e) {
            // Scanner biasanya mengirim tombol 'Enter' di akhir scan
            if (e.key === 'Enter') {
                e.preventDefault(); // Cegah submit default dulu
                
                if (this.value.trim() !== "") {
                    scanStatus.innerHTML = `<span class="text-green-600"><i class="fas fa-spinner fa-spin"></i> Memproses Data...</span>`;
                    // Delay sedikit agar user melihat status (opsional)
                    setTimeout(() => memberForm.submit(), 200);
                } else {
                    scanStatus.innerHTML = `<span class="text-red-500">Kode kosong, silakan scan ulang.</span>`;
                }
            }
        });

        // --- 3. LOGIKA KAMERA SCANNER (HTML5-QRCODE) ---
        const toggleBtn = document.getElementById('toggle-scan-btn');
        const cameraWrapper = document.getElementById('camera-container-wrapper');
        let isScannerActive = false;
        let html5QrCode = null;

        function onCameraScanSuccess(decodedText, decodedResult) {
            // Jika kamera berhasil scan, masukkan ke input box dan submit
            manualInput.value = decodedText;
            scanStatus.innerHTML = `<span class="text-green-600"><i class="fas fa-check-circle"></i> Berhasil scan via Kamera!</span>`;
            
            // Matikan kamera
            stopScanner();
            
            // Submit form
            setTimeout(() => memberForm.submit(), 300);
        }

        function startScanner() {
            cameraWrapper.classList.remove('hidden');
            scanStatus.innerHTML = `<i class="fas fa-spinner fa-spin text-blue-500"></i> Memulai kamera...`;

            html5QrCode = new Html5Qrcode("camera-reader");
            const config = { fps: 10, qrbox: { width: 200, height: 200 } };

            html5QrCode.start({ facingMode: "environment" }, config, onCameraScanSuccess)
            .then(() => {
                isScannerActive = true;
                updateButtonState(true);
                scanStatus.innerHTML = `<i class="fas fa-camera text-indigo-500"></i> Kamera aktif. Arahkan kode...`;
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
            scanStatus.innerHTML = "Siap menerima input scanner...";
            manualInput.focus(); // Kembalikan fokus ke input box
        }

        function updateButtonState(isScanning) {
            const icon = toggleBtn.querySelector('i');
            const text = toggleBtn.querySelector('span');
            if (isScanning) {
                toggleBtn.classList.replace('bg-indigo-50', 'bg-red-100');
                toggleBtn.classList.replace('text-indigo-700', 'text-red-700');
                toggleBtn.classList.replace('hover:bg-indigo-100', 'hover:bg-red-200');
                icon.className = 'fas fa-stop-circle mr-2';
                text.textContent = 'Tutup Kamera';
            } else {
                toggleBtn.classList.replace('bg-red-100', 'bg-indigo-50');
                toggleBtn.classList.replace('text-red-700', 'text-indigo-700');
                toggleBtn.classList.replace('hover:bg-red-200', 'hover:bg-indigo-100');
                icon.className = 'fas fa-camera mr-2';
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