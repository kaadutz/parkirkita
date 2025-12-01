<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkirKita - Smart Parking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    <style>
        /* Custom Animations */
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes float-delayed { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
        @keyframes pulse-slow { 0%, 100% { opacity: 0.5; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } }
        
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delay { animation: float-delayed 7s ease-in-out infinite; animation-delay: 1s; }
        
        /* Background Elements */
        .bg-grid { background-image: linear-gradient(to right, #f1f5f9 1px, transparent 1px), linear-gradient(to bottom, #f1f5f9 1px, transparent 1px); background-size: 40px 40px; }
        .dark .bg-grid { background-image: linear-gradient(to right, #1e293b 1px, transparent 1px), linear-gradient(to bottom, #1e293b 1px, transparent 1px); opacity: 0.1; }
        .blob { position: absolute; filter: blur(80px); opacity: 0.4; z-index: -1; animation: pulse-slow 10s infinite; }
        
        /* Glassmorphism */
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); }
        .dark .glass-nav { background: rgba(15, 23, 42, 0.8); border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 8px 32px rgba(0,0,0,0.05); }
        .dark .glass-card { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255,255,255,0.1); }

        /* Text Gradient */
        .text-gradient { background: linear-gradient(135deg, #F57C00, #D81B60); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Scroll Reveal */
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s ease-out; }
        .reveal.active { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="font-sans text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900 relative overflow-x-hidden transition-colors duration-300">

    <div class="blob bg-orange-300 dark:bg-orange-900 w-96 h-96 rounded-full top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="blob bg-pink-300 dark:bg-pink-900 w-96 h-96 rounded-full bottom-0 right-0 translate-x-1/3 translate-y-1/3"></div>
    <div class="fixed inset-0 bg-grid z-[-2] pointer-events-none"></div>

    <nav class="fixed w-full top-0 z-50 glass-nav transition-all duration-300">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="flex items-center gap-2 group">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-pink-600 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition">
                    <i class="fas fa-parking text-xl"></i>
                </div>
                <span class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Parkir<span class="text-gradient">Kita</span></span>
            </a>

            <div class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition">Fitur</a>
                <a href="#workflow" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition">Cara Kerja</a>
                <a href="#contact" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition">Kontak</a>
                <a href="#faq" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition">FAQ</a>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <button id="theme-toggle" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <a href="login.php" class="text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-orange-600 transition">Login Admin</a>
                <a href="pelanggan/index.php" class="px-5 py-2.5 bg-slate-900 dark:bg-orange-600 text-white text-sm font-bold rounded-full hover:bg-slate-800 dark:hover:bg-orange-700 hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <i class="fas fa-car-side"></i> Masuk Parkir
                </a>
            </div>

            <div class="flex items-center gap-4 md:hidden">
                <button id="theme-toggle-mobile" class="p-2 rounded-lg text-slate-500 dark:text-slate-400">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <button class="md:hidden text-2xl text-slate-700 dark:text-slate-200" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-100 dark:border-slate-800 absolute w-full">
            <div class="flex flex-col p-6 gap-4">
                <a href="#features" class="font-medium text-slate-700 dark:text-slate-200">Fitur</a>
                <a href="#workflow" class="font-medium text-slate-700 dark:text-slate-200">Cara Kerja</a>
                <a href="#faq" class="font-medium text-slate-700 dark:text-slate-200">FAQ</a>
                <a href="login.php" class="font-medium text-slate-700 dark:text-slate-200">Login Admin</a>
                <a href="pelanggan/index.php" class="bg-gradient-to-r from-orange-500 to-pink-600 text-white py-3 rounded-xl text-center font-bold">Masuk Parkir</a>
            </div>
        </div>
    </nav>

    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 px-6">
        <div class="container mx-auto grid lg:grid-cols-2 gap-12 items-center">
            
            <div class="max-w-2xl reveal">
                <div class="inline-block px-4 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                    ✨ Sistem Parkir #1 Indonesia
                </div>
                <h1 class="text-5xl lg:text-7xl font-black text-slate-900 dark:text-white leading-[1.1] mb-6">
                    Parkir Cerdas <br>
                    <span class="text-gradient">Tanpa Batas.</span>
                </h1>
                <p class="text-lg text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
                    Platform manajemen parkir terintegrasi untuk efisiensi maksimal. Kelola member, transaksi harian, dan laporan keuangan dalam satu dashboard modern.
                </p>

                <!-- REAL TIME WIDGET -->
                <?php
                include 'koneksi.php';
                $occupied_query = mysqli_query($conn, "SELECT COUNT(id) as total FROM parking_transactions WHERE check_out_time IS NULL");
                $occupied_data = mysqli_fetch_assoc($occupied_query);
                $occupied = $occupied_data['total'] ?? 0;
                $capacity = 200; // Mock capacity
                $percent = ($occupied / $capacity) * 100;
                ?>
                <div class="mb-8 p-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 max-w-md">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="font-bold text-slate-700 dark:text-slate-200 text-sm">Ketersediaan Parkir (Live)</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400"><?= $occupied ?> / <?= $capacity ?> Terisi</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2.5">
                        <div class="bg-gradient-to-r from-orange-500 to-pink-600 h-2.5 rounded-full transition-all duration-1000" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="pelanggan/index.php" class="px-8 py-4 bg-gradient-to-r from-orange-500 to-pink-600 text-white font-bold rounded-2xl shadow-xl shadow-orange-500/30 hover:shadow-orange-500/50 hover:-translate-y-1 transition-all text-center flex items-center justify-center gap-3">
                        <i class="fas fa-ticket-alt"></i> Mulai Transaksi
                    </a>
                    <a href="#features" class="px-8 py-4 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all text-center flex items-center justify-center gap-3">
                        <i class="fas fa-info-circle"></i> Pelajari Lebih
                    </a>
                </div>
                
                <div class="mt-10 flex items-center gap-6 text-sm text-slate-500 dark:text-slate-400 font-medium">
                    <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Instant Setup</div>
                    <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Secure Data</div>
                    <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> 24/7 Support</div>
                </div>
            </div>

            <div class="relative hidden lg:block reveal">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-500/20 to-purple-500/20 rounded-full blur-3xl animate-pulse"></div>
                
                <div class="glass-card p-6 rounded-3xl relative z-10 animate-float">
                    <div class="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-600 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="text-xs font-mono text-slate-400">dashboard.parkirkita.id</div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="w-1/2 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-slate-700 dark:to-slate-800 p-4 rounded-2xl">
                                <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center text-white mb-3">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div class="text-2xl font-bold text-slate-800 dark:text-white">1,240</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Kendaraan Masuk</div>
                            </div>
                            <div class="w-1/2 bg-gradient-to-br from-pink-50 to-pink-100 dark:from-slate-700 dark:to-slate-800 p-4 rounded-2xl">
                                <div class="w-10 h-10 bg-pink-500 rounded-xl flex items-center justify-center text-white mb-3">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="text-2xl font-bold text-slate-800 dark:text-white">Rp 5.2M</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Pendapatan</div>
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-600">
                            <div class="flex items-end justify-between gap-2 h-24">
                                <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-t-lg h-[40%]"></div>
                                <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-t-lg h-[70%]"></div>
                                <div class="w-full bg-orange-400 rounded-t-lg h-[50%]"></div>
                                <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-t-lg h-[80%]"></div>
                                <div class="w-full bg-pink-500 rounded-t-lg h-[90%]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute -bottom-6 -left-6 bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-xl animate-float-delay z-20 flex items-center gap-3 border border-slate-100 dark:border-slate-700">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                    <div>
                        <div class="font-bold text-slate-800 dark:text-white">Sistem Aktif</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Server Online</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-24 bg-white dark:bg-slate-900 relative transition-colors duration-300">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16 reveal">
                <h2 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white mb-4">Fitur <span class="text-gradient">Canggih</span></h2>
                <p class="text-slate-500 dark:text-slate-400">Kami menyediakan alat terbaik untuk mengelola area parkir Anda dengan efisiensi tinggi.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="group p-8 rounded-3xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-700 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 reveal">
                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-3">Scan QR Code</h3>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed">Member masuk lebih cepat dengan teknologi scan QR Code yang terintegrasi langsung dengan kamera atau scanner.</p>
                </div>

                <div class="group p-8 rounded-3xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-700 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 reveal">
                    <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-3">Laporan Keuangan</h3>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed">Pantau pendapatan harian, bulanan, dan performa petugas dengan laporan PDF otomatis yang rapi.</p>
                </div>

                <div class="group p-8 rounded-3xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-700 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 reveal">
                    <div class="w-14 h-14 bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-3">Manajemen Member</h3>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed">Kelola data member, perpanjangan langganan, dan cetak kartu member fisik dengan mudah.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="workflow" class="py-24 relative overflow-hidden bg-slate-50 dark:bg-slate-800 transition-colors duration-300">
        <div class="container mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                
                <div class="relative order-2 lg:order-1 reveal">
                     <div class="absolute top-4 left-4 w-full h-full bg-gradient-to-r from-orange-400 to-pink-500 rounded-3xl opacity-20 transform rotate-6"></div>
                    <div class="glass-card p-8 rounded-3xl relative bg-white dark:bg-slate-900">
                        <div class="space-y-6">
                            <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                                <div class="w-12 h-12 bg-slate-800 dark:bg-slate-700 text-white rounded-full flex items-center justify-center font-bold">1</div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-white">Check In</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Cetak tiket / Scan Member</p>
                                </div>
                            </div>
                            <div class="flex justify-center"><i class="fas fa-arrow-down text-slate-300 dark:text-slate-600"></i></div>
                            <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                                <div class="w-12 h-12 bg-slate-800 dark:bg-slate-700 text-white rounded-full flex items-center justify-center font-bold">2</div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-white">Parkir Aman</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Sistem monitoring durasi</p>
                                </div>
                            </div>
                            <div class="flex justify-center"><i class="fas fa-arrow-down text-slate-300 dark:text-slate-600"></i></div>
                            <div class="flex items-center gap-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-900/30">
                                <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold">3</div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-white">Check Out & Bayar</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Kalkulasi otomatis & Struk</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-1 lg:order-2 reveal">
                    <h2 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white mb-6">Alur Kerja yang <span class="text-gradient">Sederhana</span></h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed">
                        Kami menyederhanakan proses parkir yang rumit menjadi alur yang mulus. Dari gerbang masuk hingga keluar, semua terdata secara digital.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-500 mt-1 text-xl"></i>
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Tiket dengan Barcode Unik untuk setiap kendaraan.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-500 mt-1 text-xl"></i>
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Hitungan tarif progresif otomatis (Jam pertama + berikutnya).</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-500 mt-1 text-xl"></i>
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Dukungan Alat Scanner USB & Kamera Web.</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-24 bg-white dark:bg-slate-900 transition-colors duration-300">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16 reveal">
                <h2 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white mb-4">Pertanyaan <span class="text-gradient">Umum</span></h2>
                <p class="text-slate-500 dark:text-slate-400">Jawaban untuk hal-hal yang paling sering ditanyakan tentang ParkirKita.</p>
            </div>
            
            <div class="max-w-3xl mx-auto space-y-4">
                <details class="group p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer hover:bg-orange-50 dark:hover:bg-slate-700 transition duration-300 reveal border border-transparent dark:border-slate-700">
                    <summary class="flex justify-between items-center font-bold text-slate-800 dark:text-slate-200">
                        Apakah sistem ini bisa digunakan offline?
                        <span class="transition group-open:rotate-180"><i class="fas fa-chevron-down"></i></span>
                    </summary>
                    <p class="text-slate-600 dark:text-slate-400 mt-4 leading-relaxed">Ya, ParkirKita dapat dikonfigurasi pada jaringan lokal (LAN) sehingga tidak bergantung sepenuhnya pada koneksi internet publik, namun tetap membutuhkan server lokal.</p>
                </details>

                <details class="group p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer hover:bg-orange-50 dark:hover:bg-slate-700 transition duration-300 reveal border border-transparent dark:border-slate-700">
                    <summary class="flex justify-between items-center font-bold text-slate-800 dark:text-slate-200">
                        Bagaimana cara mendaftar member parkir?
                        <span class="transition group-open:rotate-180"><i class="fas fa-chevron-down"></i></span>
                    </summary>
                    <p class="text-slate-600 dark:text-slate-400 mt-4 leading-relaxed">Pendaftaran member dilakukan oleh petugas melalui dashboard admin. Pelanggan cukup membawa STNK dan KTP untuk didata.</p>
                </details>

                <details class="group p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer hover:bg-orange-50 dark:hover:bg-slate-700 transition duration-300 reveal border border-transparent dark:border-slate-700">
                    <summary class="flex justify-between items-center font-bold text-slate-800 dark:text-slate-200">
                        Apakah mendukung pembayaran non-tunai?
                        <span class="transition group-open:rotate-180"><i class="fas fa-chevron-down"></i></span>
                    </summary>
                    <p class="text-slate-600 dark:text-slate-400 mt-4 leading-relaxed">Saat ini sistem utama kami mencatat pembayaran tunai, namun integrasi QRIS sedang dalam tahap pengembangan untuk update berikutnya.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="container mx-auto">
            <div class="bg-slate-900 rounded-[3rem] p-10 md:p-16 text-center relative overflow-hidden reveal">
                <div class="absolute top-0 left-0 w-64 h-64 bg-orange-500 rounded-full blur-[100px] opacity-20"></div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-pink-500 rounded-full blur-[100px] opacity-20"></div>

                <div class="relative z-10">
                    <h2 class="text-3xl md:text-5xl font-black text-white mb-6">Siap Mengelola Parkir?</h2>
                    <p class="text-slate-400 text-lg mb-10 max-w-2xl mx-auto">
                        Bergabunglah sekarang dan rasakan kemudahan manajemen parkir yang modern, aman, dan efisien.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="login.php" class="px-8 py-4 bg-gradient-to-r from-orange-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-orange-500/30 transition transform hover:-translate-y-1">
                            Login Petugas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 pt-16 pb-8 transition-colors duration-300">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-pink-600 rounded-lg flex items-center justify-center text-white">
                            <i class="fas fa-parking"></i>
                        </div>
                        <span class="text-xl font-bold text-slate-800 dark:text-white">ParkirKita</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed max-w-xs">
                        Sistem manajemen parkir modern berbasis web yang dirancang untuk efisiensi, keamanan, dan kemudahan penggunaan.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 dark:text-white mb-4">Produk</h4>
                    <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li><a href="#" class="hover:text-orange-600 transition">Fitur</a></li>
                        <li><a href="#" class="hover:text-orange-600 transition">Hardware</a></li>
                        <li><a href="#" class="hover:text-orange-600 transition">Integrasi</a></li>
                    </ul>
                </div>
                <div id="contact">
                    <h4 class="font-bold text-slate-800 dark:text-white mb-4">Kontak</h4>
                    <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li><i class="fas fa-envelope mr-2 text-orange-500"></i> support@parkirkita.id</li>
                        <li><i class="fas fa-phone mr-2 text-orange-500"></i> +62 812-3456-7890</li>
                        <li><i class="fas fa-map-marker-alt mr-2 text-orange-500"></i> Jakarta, Indonesia</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-100 dark:border-slate-800 pt-8 text-center text-sm text-slate-400">
                &copy; <?php echo date('Y'); ?> ParkirKita System. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // --- Dark Mode Logic ---
        const themeToggle = document.getElementById('theme-toggle');
        const themeToggleMobile = document.getElementById('theme-toggle-mobile');
        
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
        themeToggleMobile.addEventListener('click', toggleTheme);

        // Check local storage
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // --- Scroll Reveal Logic ---
        const revealElements = document.querySelectorAll('.reveal');
        
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });

        revealElements.forEach(el => revealObserver.observe(el));
    </script>
</body>
</html>
