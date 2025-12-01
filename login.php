<?php
// Selalu mulai session di baris paling awal
session_start();

// Ganti 'koneksi.php' dengan path yang benar ke file koneksi Anda
include 'koneksi.php'; // Sesuaikan path ini jika perlu, misal 'koneksi.php'

// Inisialisasi variabel pesan error
$error_message = null;

// Arahkan jika pengguna sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'super_admin') {
        header("Location: superadmin/dashboard.php"); // Pastikan folder ini ada
        exit();
    } elseif ($_SESSION['user_role'] == 'petugas') {
        header("Location: petugas/dashboard.php"); // Pastikan folder ini ada
        exit();
    }
}

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Password dari form (teks biasa)

    if (empty($email) || empty($password)) {
        $error_message = "Email dan Password wajib diisi!";
    } else {
        // Kueri menargetkan tabel 'users' untuk semua role
        $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
             $error_message = "Terjadi kesalahan pada sistem. Coba lagi nanti.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                // PERINGATAN: Perbandingan Password Teks Biasa (TIDAK AMAN)
                // Metode ini digunakan sesuai dengan struktur database Anda saat ini.
                // Sangat disarankan untuk beralih ke password_hash() dan password_verify().
                if ($password === $user['password']) {
                    
                    // Jika password cocok, lanjutkan proses login
                    session_regenerate_id(true); // Mencegah session fixation

                    // Simpan data penting ke dalam sesi
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    $stmt->close();
                    $conn->close();

                    // Arahkan ke folder yang benar berdasarkan peran
                    if ($user['role'] == 'super_admin') {
                        header("Location: superadmin/dashboard.php");
                    } elseif ($user['role'] == 'petugas') {
                        header("Location: petugas/dashboard.php");
                    } else {
                        // Untuk role lain yang mungkin ada tapi tidak punya dashboard
                        $error_message = "Anda tidak memiliki akses ke sistem.";
                    }
                    exit();

                } else {
                    // Password tidak cocok
                    $error_message = "Kombinasi Email atau Password salah!";
                }
            } else {
                // Email tidak ditemukan
                $error_message = "Kombinasi Email atau Password salah!";
            }
            $stmt->close();
        }
    }
    // Tutup koneksi jika login gagal atau ada error
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Parkir Kita</title>
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
        
        .decorative-panel { background: linear-gradient(135deg, #F57C00, #D81B60); position: relative; overflow: hidden; }
        .decorative-panel::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('https://www.transparenttextures.com/patterns/cubes.png'); opacity: 0.1; }
        
        .text-gradient { background: linear-gradient(135deg, #F57C00, #D81B60); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="font-sans bg-slate-50 dark:bg-slate-900 min-h-screen flex items-center justify-center p-4 relative overflow-hidden transition-colors duration-300">

    <!-- Background Elements -->
    <div class="blob bg-orange-300 dark:bg-orange-900/40 w-[30rem] h-[30rem] rounded-full top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="blob bg-pink-300 dark:bg-pink-900/40 w-[30rem] h-[30rem] rounded-full bottom-0 right-0 translate-x-1/2 translate-y-1/2"></div>
    <div class="fixed inset-0 bg-grid z-[-2] pointer-events-none"></div>
    
    <!-- Theme Toggle -->
    <button id="theme-toggle" class="absolute top-6 right-6 p-3 rounded-full bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 shadow-lg hover:scale-110 transition-transform duration-300 z-50">
        <i class="fas fa-moon dark:hidden text-lg"></i>
        <i class="fas fa-sun hidden dark:block text-lg text-yellow-400"></i>
    </button>

    <div class="glass-card w-full max-w-5xl rounded-3xl shadow-2xl flex flex-col md:flex-row overflow-hidden relative z-10 transition-colors duration-300">

        <!-- Left Panel -->
        <div class="w-full md:w-5/12 decorative-panel p-10 flex flex-col justify-between text-white relative">
            <div class="z-10">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-white/20 backdrop-blur rounded-lg flex items-center justify-center">
                        <i class="fas fa-parking text-white"></i>
                    </div>
                    <span class="font-bold tracking-wide text-sm opacity-90">PARKIRKITA SYSTEM</span>
                </div>
            </div>
            
            <div class="z-10 my-10 md:my-0">
                <h1 class="text-4xl md:text-5xl font-black mb-4 leading-tight">Selamat<br>Datang.</h1>
                <p class="text-white/80 text-lg font-medium leading-relaxed">Kelola sistem parkir Anda dengan lebih cerdas, aman, dan efisien hari ini.</p>
            </div>
            
            <div class="z-10 text-sm text-white/60 font-medium">
                &copy; <?= date('Y') ?> ParkirKita. All rights reserved.
            </div>
            
            <!-- Abstract Shapes -->
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute top-20 right-10 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
        </div>

        <!-- Right Panel (Form) -->
        <div class="w-full md:w-7/12 p-10 md:p-14 bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm">
            <div class="mb-10 text-center md:text-left">
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Login ke Akun Anda</h2>
                <p class="text-slate-500 dark:text-slate-400">Silakan masukkan kredensial admin atau petugas.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 flex items-start gap-3 text-red-600 dark:text-red-400 animate-pulse">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <span class="text-sm font-semibold"><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Alamat Email</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-orange-500 transition-colors">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <input type="email" id="email" name="email" 
                            class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all placeholder:text-slate-400" 
                            placeholder="admin@parkirkita.id" required>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Password</label>
                        <a href="forgot_password.php" class="text-xs font-bold text-orange-500 hover:text-orange-600 transition">Lupa Password?</a>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-pink-500 transition-colors">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                            class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-all placeholder:text-slate-400" 
                            placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-orange-500 to-pink-600 text-white font-bold text-lg shadow-xl shadow-orange-500/20 hover:shadow-orange-500/40 hover:-translate-y-0.5 transition-all duration-300 group">
                    <span>Masuk Sekarang</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Kembali ke <a href="landing.php" class="font-bold text-slate-700 dark:text-white hover:text-orange-500 dark:hover:text-orange-400 transition">Halaman Utama</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        
        // Check local storage or system preference on load
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        themeToggle.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>
