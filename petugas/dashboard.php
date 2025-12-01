<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$user_id_petugas = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// --- AMBIL DATA UNTUK STATISTIK PETUGAS ---
$today_date = date('Y-m-d');
$query_transaksi_hari_ini = "SELECT COUNT(id) as total FROM parking_transactions WHERE processed_by_petugas_id = '$user_id_petugas' AND DATE(check_out_time) = '$today_date'";
$transaksi_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, $query_transaksi_hari_ini))['total'] ?? 0;
$query_pendapatan_hari_ini = "SELECT SUM(total_fee) as total FROM parking_transactions WHERE processed_by_petugas_id = '$user_id_petugas' AND DATE(check_out_time) = '$today_date'";
$pendapatan_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, $query_pendapatan_hari_ini))['total'] ?? 0;

$query_kendaraan_didalam = "SELECT * FROM parking_transactions WHERE check_out_time IS NULL ORDER BY check_in_time DESC LIMIT 10";
$result_kendaraan_didalam = mysqli_query($conn, $query_kendaraan_didalam);

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Parkir Kita</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; }
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

        /* Welcome Card */
        .welcome-card { background: linear-gradient(135deg, #F57C00 0%, #E65100 100%); position: relative; overflow: hidden; }
        .welcome-card::before { content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%); border-radius: 50%; pointer-events: none; }
        .welcome-card::after { content: ''; position: absolute; bottom: -50%; left: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%); border-radius: 50%; pointer-events: none; }

        .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1); }
        .table-row-hover:hover td { background-color: #f8fafc; }
        .dark .table-row-hover:hover td { background-color: #1e293b; }
        .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        .dark .glass-effect { background: rgba(15, 23, 42, 0.95); }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="w-64 bg-white dark:bg-slate-800 shadow-xl hidden sm:flex flex-col z-20 transition-all duration-300 border-r border-slate-100 dark:border-slate-700 relative">
        <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
             <a href="dashboard.php" class="text-2xl font-extrabold tracking-tight flex items-center gap-2 overflow-hidden">
                 <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-orange to-pink-600 flex items-center justify-center text-white shadow-lg shadow-orange-500/30 flex-shrink-0">
                     <i class="fas fa-parking text-xl"></i>
                 </div>
                 <span id="logo-text" class="text-slate-800 dark:text-white transition-opacity duration-300">Parkir<span class="text-brand-orange">Kita</span></span>
             </a>
        </div>

        <div class="p-4 flex-1 overflow-y-auto custom-scrollbar">
            <div id="menu-label" class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 px-4 transition-opacity duration-300">Menu Utama</div>
            <nav class="space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'dashboard.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-home fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Dashboard</span>
                </a>
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'transaksi_keluar.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-sign-out-alt fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Transaksi Keluar</span>
                </a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'kelola_member.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-users fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Kelola Member</span>
                </a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'laporan_petugas.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-chart-pie fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Laporan Saya</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Collapse Button -->
        <button id="sidebar-collapse-btn" onclick="toggleSidebar()" class="absolute -right-3 top-24 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full p-1 shadow-md text-slate-500 dark:text-slate-300 hover:text-brand-orange transition-colors z-30 hidden sm:flex items-center justify-center w-6 h-6">
            <i class="fas fa-chevron-left text-xs" id="collapse-icon"></i>
        </button>

        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 flex items-center gap-3 overflow-hidden">
                <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-white dark:border-slate-600 shadow-sm flex-shrink-0">
                <div class="flex-1 min-w-0 sidebar-text transition-opacity duration-300">
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                </div>
                <a href="../logout.php" class="text-slate-400 hover:text-red-500 transition-colors p-2 flex-shrink-0" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 relative">

        <!-- HEADER -->
        <header class="h-20 glass-effect border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors">
                     <i class="fas fa-bars text-xl"></i>
                 </button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Dashboard Overview</h1>
             </div>

             <div class="flex items-center gap-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>

                <div class="relative">
                    <button id="profile-menu-btn" onclick="toggleProfileMenu()" class="flex items-center gap-2 focus:outline-none">
                        <img src="<?= $profile_picture_url ?>" alt="User" class="w-10 h-10 rounded-full object-cover border-2 border-brand-orange shadow-sm">
                    </button>
                    <!-- Dropdown -->
                    <div id="profile-dropdown" class="absolute right-0 mt-3 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 hidden transition-all transform origin-top-right z-50">
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

        <!-- CONTENT SCROLLABLE -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- Welcome Card -->
                <div class="welcome-card rounded-2xl p-8 text-white shadow-lg shadow-orange-500/20">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h2 class="text-3xl font-bold mb-2">Halo, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>! 👋</h2>
                            <p class="text-orange-100 text-lg opacity-90 max-w-xl">Selamat bertugas! Hari ini adalah kesempatan bagus untuk memberikan pelayanan terbaik.</p>
                        </div>
                        <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm p-4 rounded-xl border border-white/20">
                            <div class="text-center px-4 border-r border-white/20">
                                <span class="block text-2xl font-bold"><?= date('H:i') ?></span>
                                <span class="text-xs uppercase opacity-80">Waktu</span>
                            </div>
                            <div class="text-center px-4">
                                <span class="block text-2xl font-bold"><?= date('d M') ?></span>
                                <span class="text-xs uppercase opacity-80">Tanggal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stat 1 -->
                    <div class="stat-card bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-car-side text-9xl transform translate-x-10 -translate-y-10 text-brand-orange"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Transaksi Hari Ini</span>
                            </div>
                            <h3 class="text-4xl font-bold text-slate-800 dark:text-white mb-1"><?= number_format($transaksi_hari_ini) ?></h3>
                            <p class="text-sm text-green-500 font-medium flex items-center gap-1">
                                <i class="fas fa-arrow-up"></i> Kendaraan Keluar
                            </p>
                        </div>
                    </div>

                    <!-- Stat 2 -->
                    <div class="stat-card bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-wallet text-9xl transform translate-x-10 -translate-y-10 text-green-500"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center text-xl">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Pendapatan Hari Ini</span>
                            </div>
                            <h3 class="text-4xl font-bold text-slate-800 dark:text-white mb-1">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></h3>
                            <p class="text-sm text-green-500 font-medium flex items-center gap-1">
                                <i class="fas fa-check-circle"></i> Tunai Diterima
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <i class="fas fa-clock text-brand-orange"></i> Kendaraan Parkir Saat Ini
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Menampilkan 10 kendaraan terbaru yang belum checkout.</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 animate-pulse">
                            ● Live Update
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider">
                                    <th class="p-5 font-bold">Token / Plat</th>
                                    <th class="p-5 font-bold">Kategori</th>
                                    <th class="p-5 font-bold">Waktu Masuk</th>
                                    <th class="p-5 font-bold">Durasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <?php if(mysqli_num_rows($result_kendaraan_didalam) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result_kendaraan_didalam)):
                                        $masuk = new DateTime($row['check_in_time']);
                                        $sekarang = new DateTime();
                                        $diff = $masuk->diff($sekarang);
                                        $durasi_jam = $diff->h + ($diff->days * 24);
                                        $durasi = $durasi_jam . " jam " . $diff->i . " menit";
                                    ?>
                                    <tr class="table-row-hover transition-colors">
                                        <td class="p-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                                    <i class="fas fa-<?= ($row['vehicle_category'] ?? 'car') == 'motor' ? 'motorcycle' : 'car' ?>"></i>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($row['license_plate'] ?: '-') ?></div>
                                                    <div class="text-xs font-mono text-slate-400"><?= htmlspecialchars($row['parking_token']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-5">
                                            <?php if($row['member_id']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                                    <i class="fas fa-crown mr-1"></i> Member
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                    Umum
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-5 text-slate-600 dark:text-slate-400 text-sm">
                                            <?= date('d M Y, H:i', strtotime($row['check_in_time'])) ?>
                                        </td>
                                        <td class="p-5">
                                            <span class="text-sm font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 px-2 py-1 rounded">
                                                <?= $durasi ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-400">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-3 text-slate-300">
                                                    <i class="fas fa-parking text-2xl"></i>
                                                </div>
                                                <span class="font-medium">Tidak ada kendaraan parkir saat ini.</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    // --- Mobile Sidebar Toggle ---
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

    // --- Desktop Sidebar Collapse ---
    const logoText = document.getElementById('logo-text');
    const menuLabel = document.getElementById('menu-label');
    const sidebarTexts = document.querySelectorAll('.sidebar-text');
    const collapseIcon = document.getElementById('collapse-icon');

    function toggleSidebar() {
        const isCollapsed = sidebar.classList.contains('w-20');
        if (isCollapsed) {
            // Expand
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            if(logoText) logoText.classList.remove('hidden');
            if(menuLabel) menuLabel.classList.remove('opacity-0', 'invisible');
            sidebarTexts.forEach(text => text.classList.remove('hidden'));
            if(collapseIcon) collapseIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
            localStorage.setItem('sidebar_collapsed', 'false');
        } else {
            // Collapse
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            if(logoText) logoText.classList.add('hidden');
            if(menuLabel) menuLabel.classList.add('opacity-0', 'invisible');
            sidebarTexts.forEach(text => text.classList.add('hidden'));
            if(collapseIcon) collapseIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            localStorage.setItem('sidebar_collapsed', 'true');
        }
    }

    // Init Sidebar State
    if(localStorage.getItem('sidebar_collapsed') === 'true') {
        sidebar.classList.remove('w-64');
        sidebar.classList.add('w-20');
        if(logoText) logoText.classList.add('hidden');
        if(menuLabel) menuLabel.classList.add('opacity-0', 'invisible');
        sidebarTexts.forEach(text => text.classList.add('hidden'));
        if(collapseIcon) collapseIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
    }

    // --- Profile Dropdown Toggle ---
    function toggleProfileMenu() {
        const dropdown = document.getElementById('profile-dropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close Dropdown on Click Outside
    window.addEventListener('click', function(e) {
        const btn = document.getElementById('profile-menu-btn');
        const dropdown = document.getElementById('profile-dropdown');
        if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // --- Theme Toggle ---
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;

    if(localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }

    if(themeToggle) {
        themeToggle.addEventListener('click', () => {
            if(html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }
</script>
</body>
</html>
