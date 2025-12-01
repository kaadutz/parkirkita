<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// --- DATA USER ---
$user_id_petugas = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($query_user);
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';
if (!empty($user_data['profile_photo']) && file_exists('../uploads/profile/' . $user_data['profile_photo'])) {
    $profile_picture_url = '../uploads/profile/' . $user_data['profile_photo'] . '?v=' . time();
}
$currentPage = 'transaksi_keluar.php';

// --- LOGIKA PENCARIAN ---
$search_result = null;
$plate_query = '';
$denda = 0;
$fee_parkir = 0;
$total_bayar = 0;
$durasi_teks = '-';
$found_trx = null;

if (isset($_GET['plate'])) {
    $plate_query = strtoupper(trim($_GET['plate']));
    // Cari transaksi aktif (belum checkout) berdasarkan plat
    // Prioritaskan yang paling baru masuk
    $q = "SELECT pt.*, m.name as member_name
          FROM parking_transactions pt
          LEFT JOIN members m ON pt.member_id = m.id
          WHERE pt.license_plate = '$plate_query' AND pt.check_out_time IS NULL
          ORDER BY pt.check_in_time DESC LIMIT 1";
    $res = mysqli_query($conn, $q);
    $found_trx = mysqli_fetch_assoc($res);

    if ($found_trx) {
        $search_result = 'found';

        // Hitung Durasi & Biaya Parkir (Copy logic dari proses_checkout.php)
        $check_in_time = new DateTime($found_trx['check_in_time']);
        $current_time = new DateTime();
        $interval = $check_in_time->diff($current_time);
        $durasi_teks = $interval->h . " jam " . $interval->i . " menit";
        $total_minutes = ($interval->d * 24 * 60) + ($interval->h * 60) + $interval->i;

        if ($found_trx['member_id']) {
            $fee_parkir = 0; // Member Gratis Parkir
        } else {
            // Non-Member
            if ($total_minutes > 10) {
                $fee_parkir = 3000;
                if ($total_minutes > 60) {
                    $remaining = $total_minutes - 60;
                    $add_hours = ceil($remaining / 60);
                    $fee_parkir += $add_hours * 2000;
                }
            } else {
                $fee_parkir = 0;
            }
        }

        // Tentukan Denda berdasarkan kategori di database
        $cat = $found_trx['vehicle_category'];
        $denda = ($cat == 'motor') ? 25000 : 50000;

    } else {
        $search_result = 'not_found';
        // Default denda jika tidak ketemu (user pilih nanti)
        $denda = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Proses Tiket Hilang - ParkirKita</title>
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
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background-color: #475569; }
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 3px solid transparent; }
        .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.08); color: #F57C00; border-left-color: #F57C00; }
        .sidebar-active { background-color: rgba(245, 124, 0, 0.12); color: #F57C00; font-weight: 700; border-left-color: #F57C00; }
        .dark .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.1); }
        .dark .sidebar-active { background-color: rgba(245, 124, 0, 0.2); }
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
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'transaksi_keluar.php' || $currentPage == 'tiket_hilang.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
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
        <header class="h-20 glass-effect border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors"><i class="fas fa-bars text-xl"></i></button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Proses Tiket Hilang</h1>
             </div>
             <div class="flex items-center gap-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"><i class="fas fa-moon dark:hidden"></i><i class="fas fa-sun hidden dark:block"></i></button>

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

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="max-w-4xl mx-auto">
                <div class="mb-6">
                    <a href="transaksi_keluar.php" class="inline-flex items-center text-slate-500 hover:text-brand-orange transition-colors font-medium text-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>

                <!-- ALERTS -->
                <?php if (isset($_SESSION['pesan'])): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['pesan_tipe'] == 'sukses' ? 'bg-green-50 dark:bg-green-900/30 border-green-500 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/30 border-red-500 text-red-800 dark:text-red-300' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['pesan_tipe'] == 'sukses' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['pesan']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['pesan']); unset($_SESSION['pesan_tipe']); endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">

                    <!-- LEFT COLUMN: FORM -->
                    <div class="md:col-span-7 space-y-6">

                        <!-- SEARCH CARD -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-search text-brand-orange"></i> Cari Kendaraan
                            </h2>
                            <form action="" method="GET" class="flex gap-3">
                                <input type="text" name="plate" value="<?= htmlspecialchars($plate_query) ?>" class="flex-1 px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-800 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-brand-orange uppercase font-bold" placeholder="Plat Nomor (Contoh: B 1234 ABC)" required autofocus>
                                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-bold px-6 py-3 rounded-xl transition shadow-lg">
                                    Cari
                                </button>
                            </form>
                        </div>

                        <!-- RESULT & PAYMENT FORM -->
                        <?php if ($search_result): ?>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-100 dark:border-slate-700 relative overflow-hidden">
                            <form action="proses_tiket_hilang.php" method="POST" id="form-bayar">

                                <?php if ($search_result == 'found'): ?>
                                    <input type="hidden" name="transaction_id" value="<?= $found_trx['id'] ?>">
                                    <input type="hidden" name="license_plate" value="<?= $found_trx['license_plate'] ?>">
                                    <input type="hidden" name="vehicle_category" value="<?= $found_trx['vehicle_category'] ?>">

                                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800/30 flex items-start gap-3">
                                        <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                                        <div>
                                            <h3 class="font-bold text-green-800 dark:text-green-400">Kendaraan Ditemukan</h3>
                                            <p class="text-sm text-green-700 dark:text-green-500/80">Sesi parkir aktif ditemukan.</p>
                                        </div>
                                    </div>

                                    <!-- Details -->
                                    <div class="space-y-4 mb-6">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700">
                                            <span class="text-slate-500 dark:text-slate-400">Jenis Kendaraan</span>
                                            <span class="font-bold text-slate-800 dark:text-white capitalize"><?= $found_trx['vehicle_category'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700">
                                            <span class="text-slate-500 dark:text-slate-400">Waktu Masuk</span>
                                            <span class="font-medium text-slate-800 dark:text-white"><?= date('H:i', strtotime($found_trx['check_in_time'])) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700">
                                            <span class="text-slate-500 dark:text-slate-400">Durasi</span>
                                            <span class="font-medium text-slate-800 dark:text-white"><?= $durasi_teks ?></span>
                                        </div>

                                        <div class="flex justify-between items-center py-2">
                                            <span class="text-slate-500 dark:text-slate-400">Status Member</span>
                                            <?php if($found_trx['member_id']): ?>
                                                <span class="px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs font-bold">MEMBER</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-bold">UMUM</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Cost Breakdown -->
                                    <div class="bg-slate-50 dark:bg-slate-700/30 p-4 rounded-xl space-y-2 mb-6">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500 dark:text-slate-400">Biaya Parkir</span>
                                            <span class="font-medium">Rp <?= number_format($fee_parkir) ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500 dark:text-slate-400">Denda Tiket Hilang</span>
                                            <span class="font-medium text-red-500">+ Rp <?= number_format($denda) ?></span>
                                        </div>
                                        <div class="border-t border-slate-200 dark:border-slate-600 pt-2 flex justify-between items-center">
                                            <span class="font-bold text-slate-800 dark:text-white">Total Bayar</span>
                                            <span class="text-2xl font-extrabold text-brand-orange">Rp <?= number_format($fee_parkir + $denda) ?></span>
                                        </div>
                                        <input type="hidden" id="total_tagihan" value="<?= $fee_parkir + $denda ?>">
                                    </div>

                                <?php else: ?>
                                    <!-- NOT FOUND -->
                                    <div class="mb-6 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-800/30 flex items-start gap-3">
                                        <i class="fas fa-exclamation-triangle text-orange-500 text-xl mt-1"></i>
                                        <div>
                                            <h3 class="font-bold text-orange-800 dark:text-orange-400">Kendaraan Tidak Ditemukan</h3>
                                            <p class="text-sm text-orange-700 dark:text-orange-500/80">Tidak ada sesi aktif untuk plat ini. Lanjutkan dengan denda manual.</p>
                                        </div>
                                    </div>

                                    <input type="hidden" name="license_plate" value="<?= htmlspecialchars($plate_query) ?>">

                                    <div class="mb-4">
                                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Pilih Jenis Kendaraan</label>
                                        <select name="vehicle_category" id="vehicle_category_manual" class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-orange" required>
                                            <option value="" disabled selected>-- Pilih --</option>
                                            <option value="motor" data-denda="25000">Motor (Rp 25.000)</option>
                                            <option value="mobil" data-denda="50000">Mobil (Rp 50.000)</option>
                                        </select>
                                    </div>

                                    <div class="bg-slate-50 dark:bg-slate-700/30 p-4 rounded-xl mb-6 flex justify-between items-center">
                                        <span class="font-bold text-slate-800 dark:text-white">Total Denda</span>
                                        <span class="text-2xl font-extrabold text-brand-orange" id="display_denda_manual">Rp 0</span>
                                    </div>
                                    <input type="hidden" id="total_tagihan" value="0">
                                <?php endif; ?>

                                <!-- PAYMENT INPUT -->
                                <div class="mb-6">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Uang Diterima (Tunai)</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                        <input type="number" name="cash_paid" id="cash_paid" class="w-full pl-12 pr-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl text-xl font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-700 focus:ring-2 focus:ring-brand-orange" placeholder="0" required>
                                    </div>
                                    <div class="mt-2 flex justify-between items-center text-sm">
                                        <span class="text-slate-500 dark:text-slate-400">Kembalian:</span>
                                        <span id="change_due_display" class="font-bold text-lg text-slate-400">Rp 0</span>
                                    </div>
                                </div>

                                <button type="submit" id="submit-button" class="w-full bg-brand-orange hover:bg-orange-600 text-white font-bold py-4 px-8 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    <i class="fas fa-print"></i> Proses & Cetak Struk
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>

                    </div>

                    <!-- RIGHT COLUMN: INFO -->
                    <div class="md:col-span-5">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 border border-blue-100 dark:border-blue-800/30">
                            <h3 class="font-bold text-lg text-blue-800 dark:text-blue-300 mb-4 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i> Ketentuan Tiket Hilang
                            </h3>
                            <ul class="space-y-4 text-sm text-blue-700 dark:text-blue-200/80">
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle mt-1 opacity-70"></i>
                                    <span>Jika kendaraan <strong>ditemukan</strong> di sistem, biaya adalah akumulasi tarif parkir normal ditambah denda.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle mt-1 opacity-70"></i>
                                    <span>Jika kendaraan <strong>tidak ditemukan</strong> (data masuk hilang/manual), dikenakan tarif flat denda saja.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-money-bill-wave mt-1 opacity-70"></i>
                                    <span>Tarif Denda:
                                        <ul class="ml-6 mt-1 list-disc opacity-80">
                                            <li>Motor: <strong>Rp 25.000</strong></li>
                                            <li>Mobil: <strong>Rp 50.000</strong></li>
                                        </ul>
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-id-card mt-1 opacity-70"></i>
                                    <span>Untuk <strong>Member</strong>, tetap dikenakan denda tiket hilang, namun biaya parkir per jam gratis.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    window.toggleProfileMenu = function() {
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

    // Theme Logic
    const themeToggle = document.getElementById('theme-toggle');
    if(themeToggle) {
        themeToggle.addEventListener('click', () => {
            if(document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark');
            }
        });
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }

    // Calculator Logic (Preserved)
    const cashInput = document.getElementById('cash_paid');
    const changeDisplay = document.getElementById('change_due_display');
    const submitBtn = document.getElementById('submit-button');
    const totalTagihanInput = document.getElementById('total_tagihan');

    // For Manual Mode
    const vehicleSelectManual = document.getElementById('vehicle_category_manual');
    const displayDendaManual = document.getElementById('display_denda_manual');

    let currentTotal = parseFloat(totalTagihanInput?.value || 0);

    if (vehicleSelectManual) {
        vehicleSelectManual.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const denda = parseFloat(selected.getAttribute('data-denda')) || 0;
            currentTotal = denda;
            displayDendaManual.textContent = 'Rp ' + denda.toLocaleString('id-ID');
            calculateChange();
        });
    }

    if (cashInput) {
        cashInput.addEventListener('input', calculateChange);
    }

    function calculateChange() {
        if (!cashInput) return;
        const cash = parseFloat(cashInput.value) || 0;

        // Prevent submit if total is 0 (manual mode not selected)
        if (currentTotal === 0 && !vehicleSelectManual) {
             // If manual mode but vehicle not selected, handled by totalTagihanInput check above
        }
        if(currentTotal === 0) {
             submitBtn.disabled = true;
             changeDisplay.textContent = 'Rp 0';
             return;
        }

        const change = cash - currentTotal;

        if (cash >= currentTotal) {
            changeDisplay.textContent = 'Rp ' + change.toLocaleString('id-ID');
            changeDisplay.className = 'font-bold text-lg text-blue-600 dark:text-blue-400';
            submitBtn.disabled = false;
        } else {
            const kurang = Math.abs(change);
            changeDisplay.textContent = 'Kurang Rp ' + kurang.toLocaleString('id-ID');
            changeDisplay.className = 'font-bold text-lg text-red-500';
            submitBtn.disabled = true;
        }
    }
});
</script>
</body>
</html>
