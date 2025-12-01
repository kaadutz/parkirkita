<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Ambil data profil petugas yang login untuk header
$user_id_petugas = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($query_user);
$profile_picture_filename = $user_data['profile_photo'] ?? null;
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';
if (!empty($profile_picture_filename) && file_exists('../uploads/profile/' . $profile_picture_filename)) {
    $profile_picture_url = '../uploads/profile/' . $profile_picture_filename . '?v=' . time();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Pilih Tipe Transaksi - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { orange: '#F57C00', pink: '#D81B60', dark: '#0F172A' }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background-color: #475569; }

        /* SIDEBAR */
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 3px solid transparent; }
        .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.08); color: #F57C00; border-left-color: #F57C00; }
        .sidebar-active { background-color: rgba(245, 124, 0, 0.12); color: #F57C00; font-weight: 700; border-left-color: #F57C00; }
        .dark .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.1); }
        .dark .sidebar-active { background-color: rgba(245, 124, 0, 0.2); }

        /* CARDS */
        .trx-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }
        .trx-card:hover {
            transform: translateY(-8px);
            border-color: currentColor;
            box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.1);
        }
        .trx-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at top right, currentColor, transparent 70%);
            opacity: 0.05; pointer-events: none;
        }

        /* ICONS */
        .icon-box {
            transition: transform 0.4s ease;
        }
        .trx-card:hover .icon-box {
            transform: scale(1.1) rotate(5deg);
        }

        /* HEADER GLASS */
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
        }
        .dark .glass-header {
            background: rgba(15, 23, 42, 0.9);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white dark:bg-slate-800 shadow-xl hidden sm:flex flex-col z-20 transition-all duration-300 border-r border-slate-100 dark:border-slate-700">
        <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
             <a href="dashboard.php" class="text-2xl font-extrabold tracking-tight flex items-center gap-2">
                 <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-orange to-pink-600 flex items-center justify-center text-white shadow-lg shadow-orange-500/30">
                     <i class="fas fa-parking text-xl"></i>
                 </div>
                 <span class="text-slate-800 dark:text-white">Parkir<span class="text-brand-orange">Kita</span></span>
             </a>
        </div>

        <div class="p-4">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 px-4">Menu Utama</div>
            <nav class="space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'dashboard.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-home fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'transaksi_keluar.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-sign-out-alt fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Transaksi Keluar</span>
                </a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'kelola_member.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-users fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Kelola Member</span>
                </a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'laporan_petugas.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-chart-pie fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Laporan Saya</span>
                </a>
            </nav>
        </div>

        <div class="mt-auto p-4 border-t border-slate-100 dark:border-slate-700">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 flex items-center gap-3">
                <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-white dark:border-slate-600 shadow-sm">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                </div>
                <a href="../logout.php" class="text-slate-400 hover:text-red-500 transition-colors p-2" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 relative">

        <!-- HEADER -->
        <header class="h-20 glass-header border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors">
                     <i class="fas fa-bars text-xl"></i>
                 </button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Transaksi Keluar</h1>
             </div>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <div class="relative group">
                    <button class="flex items-center gap-2 focus:outline-none">
                        <img src="<?= $profile_picture_url ?>" alt="User" class="w-10 h-10 rounded-full object-cover border-2 border-brand-orange shadow-sm">
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-3 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 hidden group-hover:block transition-all opacity-0 group-hover:opacity-100 transform origin-top-right">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-orange-50 dark:hover:bg-slate-700 hover:text-brand-orange">
                            <i class="fas fa-user-circle mr-2"></i> Profil Saya
                        </a>
                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                        <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto h-full flex flex-col justify-center">

                <div class="text-center mb-12">
                    <h1 class="text-4xl font-extrabold text-slate-800 dark:text-white mb-4 tracking-tight">Pilih Jenis Transaksi</h1>
                    <p class="text-slate-500 dark:text-slate-400 max-w-lg mx-auto text-lg">Silakan pilih kategori pelanggan untuk melanjutkan proses pembayaran parkir kendaraan keluar.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-4">

                    <!-- CARD 1 -->
                    <a href="proses_non_member.php" class="trx-card group bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl text-blue-500 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-700/50 flex flex-col items-center text-center h-full">
                        <div class="icon-box w-24 h-24 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-4xl mb-6 shadow-inner">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-3">Pengunjung Umum</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-8 flex-grow">
                            Pembayaran reguler menggunakan scan tiket parkir (Barcode/QR).
                        </p>
                        <span class="px-6 py-3 rounded-xl bg-blue-500 text-white font-bold text-sm shadow-lg shadow-blue-500/30 group-hover:scale-105 transition-transform w-full">
                            Proses Transaksi <i class="fas fa-arrow-right ml-2"></i>
                        </span>
                    </a>

                    <!-- CARD 2 -->
                    <a href="proses_member_keluar.php" class="trx-card group bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl text-pink-500 dark:text-pink-400 hover:bg-pink-50 dark:hover:bg-slate-700/50 flex flex-col items-center text-center h-full">
                        <div class="icon-box w-24 h-24 rounded-full bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-4xl mb-6 shadow-inner">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-3">Member Parkir</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-8 flex-grow">
                            Verifikasi checkout untuk pelanggan terdaftar (Member).
                        </p>
                        <span class="px-6 py-3 rounded-xl bg-pink-500 text-white font-bold text-sm shadow-lg shadow-pink-500/30 group-hover:scale-105 transition-transform w-full">
                            Proses Transaksi <i class="fas fa-arrow-right ml-2"></i>
                        </span>
                    </a>

                    <!-- CARD 3 -->
                    <a href="tiket_hilang.php" class="trx-card group bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl text-orange-500 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-slate-700/50 flex flex-col items-center text-center h-full">
                        <div class="icon-box w-24 h-24 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-4xl mb-6 shadow-inner">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-3">Tiket Hilang</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-8 flex-grow">
                            Proses denda bagi pengunjung yang kehilangan tiket parkir.
                        </p>
                        <span class="px-6 py-3 rounded-xl bg-orange-500 text-white font-bold text-sm shadow-lg shadow-orange-500/30 group-hover:scale-105 transition-transform w-full">
                            Proses Denda <i class="fas fa-arrow-right ml-2"></i>
                        </span>
                    </a>

                </div>

                <div class="mt-16 text-center text-sm text-slate-400 dark:text-slate-600 font-medium">
                    &copy; <?= date('Y') ?> ParkirKita System. Pastikan data kendaraan sesuai.
                </div>

            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if(sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('fixed');
            sidebar.classList.toggle('inset-0');
            sidebar.classList.toggle('w-full');
        });
    }

    // Theme Logic
    const themeToggle = document.getElementById('theme-toggle');
    if(themeToggle) {
        themeToggle.addEventListener('click', () => {
            if(document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
});
</script>
</body>
</html>
