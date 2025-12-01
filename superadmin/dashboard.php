<?php
//dashboard.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// --- DATA PROFILE USER (STATIC) ---
$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($result_user);
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
    <title>Dashboard Super Admin - Parkir Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { orange: '#F57C00', pink: '#D81B60' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-dark: #1C2E4A; --brand-light-bg: #FFF8F2; }
        body { font-family: 'Inter', sans-serif; overflow-x: hidden; }

        /* DARK MODE OVERRIDES */
        .dark .sidebar-link:hover { background-color: #1e293b; border-left-color: var(--brand-orange); }
        .dark .sidebar-active { background-color: #1e293b; color: var(--brand-orange); }
        .dark body { background-color: #0f172a; }

        /* SIDEBAR & NAVBAR (TIDAK DIUBAH) */
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; }
        .sidebar-link:hover { background-color: var(--brand-light-bg); color: var(--brand-orange); border-left-color: var(--brand-orange); transform: translateX(4px); }
        .sidebar-active { background-color: var(--brand-light-bg); color: var(--brand-orange); font-weight: 700; border-left-color: var(--brand-orange); }
        #sidebar, #main-content { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-text, .sidebar-logo-text { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); white-space: nowrap; }
        body.sidebar-collapsed #sidebar { width: 5.5rem; }
        body.sidebar-collapsed #main-content { margin-left: 5.5rem; }
        body.sidebar-collapsed .sidebar-text, .sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; margin-left: 0; pointer-events: none; }
        .profile-picture { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 3px solid #FDBA74; }
        .profile-picture:hover { transform: scale(1.05); border-color: var(--brand-orange); }
        .dropdown-menu { transform-origin: top right; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        /* --- DASHBOARD STYLES --- */
        .welcome-banner {
            background: linear-gradient(135deg, #1C2E4A 0%, #2C3E50 100%);
            color: white; border-radius: 1.5rem; padding: 2rem; position: relative; overflow: hidden;
            box-shadow: 0 10px 30px -5px rgba(28, 46, 74, 0.3);
        }
        .welcome-banner::after {
            content: ''; position: absolute; top: -50%; right: -5%; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%; pointer-events: none;
        }

        .stats-card {
            background: white; border-radius: 1rem; padding: 1.5rem;
            border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.08); border-color: #e2e8f0; }

        .icon-wrapper {
            width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        }

        .chart-container, .recent-container {
            background: white; border-radius: 1rem; border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); padding: 1.5rem;
        }

        .filter-input {
            border: 1px solid #e2e8f0; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; color: #475569;
            background-color: #f8fafc; transition: all 0.2s;
        }
        .filter-input:focus { outline: none; border-color: var(--brand-orange); background-color: white; box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.1); }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 transition-colors duration-300">

<div class="flex h-screen bg-slate-50 dark:bg-slate-900 overflow-hidden transition-colors duration-300">

    <aside id="sidebar" class="w-64 bg-white dark:bg-slate-800 shadow-2xl hidden sm:block flex-shrink-0 z-10 border-r border-slate-100 dark:border-slate-700 transition-colors duration-300">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100 dark:border-slate-700">
                 <a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center transition-all duration-300 hover:scale-105">
                     <i class="fas fa-parking text-[var(--brand-orange)] text-3xl"></i>
                     <span class="sidebar-logo-text ml-3 text-gray-700 dark:text-white transition-all duration-300">Parkir<span class="text-[var(--brand-pink)]">Kita</span></span>
                 </a>
            </div>
            <nav class="mt-4 text-gray-600 dark:text-slate-400 font-medium flex-grow">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'dashboard.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-tachometer-alt fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Dashboard</span>
                </a>
                <a href="kelola_petugas.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'kelola_petugas.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-users-cog fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Kelola Petugas</span>
                </a>
                <a href="laporan.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'laporan.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-file-invoice-dollar fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Laporan</span>
                </a>
            </nav>
            <div class="mt-auto p-4 border-t border-slate-100 dark:border-slate-700">
                <div id="user-info-sidebar" class="flex items-center transition-all duration-300">
                    <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover profile-picture">
                    <div class="sidebar-text ml-3 transition-all duration-300">
                        <p class="text-sm font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500 dark:text-slate-400 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="sidebar-link flex items-center mt-3 py-2 px-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 rounded-lg">
                    <i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">

        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm z-20 transition-colors duration-300">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 dark:text-slate-300 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50 dark:hover:bg-slate-700"><i class="fas fa-bars fa-lg"></i></button>
                 <h1 class="text-xl font-semibold text-slate-700 dark:text-white">Dashboard Overview</h1>
             </div>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <div class="relative">
                    <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 px-4 py-2 rounded-xl transition-all duration-300 group">
                        <div class="relative">
                            <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture">
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <div class="text-left hidden sm:block">
                            <p class="font-semibold text-gray-700 dark:text-slate-200 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                        </div>
                        <i class="fas fa-chevron-down text-gray-500 dark:text-slate-400 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i>
                    </button>
                    <div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dark:border-slate-700 dropdown-menu scale-95 opacity-0">
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                            <div class="flex items-center space-x-3">
                                <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-800 dark:text-white truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="py-2">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-slate-300 hover:bg-orange-50 dark:hover:bg-slate-700 hover:text-[var(--brand-orange)] transition-colors"><i class="fas fa-user-circle mr-2"></i> Profil Saya</a>
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="container mx-auto max-w-7xl">

                <div class="welcome-banner mb-10">
                    <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                        <div>
                            <h2 class="text-3xl font-bold mb-2">Halo, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>! </h2>
                            <p class="text-blue-100 opacity-90 text-lg">Berikut ringkasan aktivitas sistem parkir.</p>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20 flex flex-wrap gap-3 items-center">
                            <div class="flex items-center gap-2 text-white/80">
                                <i class="far fa-calendar-alt"></i>
                                <span class="text-sm font-semibold">Filter:</span>
                            </div>
                            <input type="date" id="start_date" class="bg-white/90 border-none text-slate-800 text-xs rounded-lg px-3 py-2 font-bold focus:ring-2 focus:ring-orange-400" value="<?= date('Y-m-d', strtotime('-6 days')) ?>">
                            <span class="text-white/60 text-xs">s/d</span>
                            <input type="date" id="end_date" class="bg-white/90 border-none text-slate-800 text-xs rounded-lg px-3 py-2 font-bold focus:ring-2 focus:ring-orange-400" value="<?= date('Y-m-d') ?>">
                            <button id="apply-filter" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-lg">
                                <i class="fas fa-search mr-1"></i> Terapkan
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

                    <!-- Static Cards -->
                    <div class="stats-card flex flex-col justify-between h-full dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-4">
                            <div class="icon-wrapper bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Staff</span>
                        </div>
                        <div>
                            <?php $total_petugas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) as total FROM users WHERE role = 'petugas'"))['total'] ?? 0; ?>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white"><?= number_format($total_petugas) ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Petugas Aktif</p>
                        </div>
                    </div>

                    <div class="stats-card flex flex-col justify-between h-full dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-4">
                            <div class="icon-wrapper bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pelanggan</span>
                        </div>
                        <div>
                            <?php $total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) as total FROM members"))['total'] ?? 0; ?>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white"><?= number_format($total_members) ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Member Terdaftar</p>
                        </div>
                    </div>

                    <!-- Dynamic Cards (Updated via AJAX) -->
                    <div class="stats-card flex flex-col justify-between h-full relative overflow-hidden border-l-4 border-green-500 dark:bg-slate-800 dark:border-slate-700 dark:border-l-green-500">
                        <div class="flex justify-between items-start mb-4">
                            <div class="icon-wrapper bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendapatan</span>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight" id="val-income">Rp 0</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Periode Terpilih</p>
                        </div>
                    </div>

                    <div class="stats-card flex flex-col justify-between h-full border-l-4 border-orange-500 dark:bg-slate-800 dark:border-slate-700 dark:border-l-orange-500">
                        <div class="flex justify-between items-start mb-4">
                            <div class="icon-wrapper bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                                <i class="fas fa-car"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Transaksi</span>
                        </div>
                        <div>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white" id="val-trx">0</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kendaraan Keluar</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="lg:col-span-2 chart-container relative dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Analisis Pendapatan</h3>
                            <div id="chart-loading" class="hidden absolute inset-0 bg-white/80 dark:bg-slate-800/80 z-10 flex items-center justify-center rounded-xl">
                                <i class="fas fa-spinner fa-spin text-orange-500 text-3xl"></i>
                            </div>
                        </div>
                        <div class="h-80 w-full">
                            <canvas id="incomeChart"></canvas>
                        </div>
                    </div>

                    <div class="recent-container flex flex-col h-full dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Aktivitas Terbaru</h3>
                            <a href="laporan.php" class="text-xs font-bold text-orange-500 hover:text-orange-600">Lihat Semua</a>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-2 space-y-4 max-h-[320px]">
                            <?php
                            // Initial Load (Last 5 transactions)
                            $query_recent = "SELECT * FROM parking_transactions ORDER BY check_in_time DESC LIMIT 5";
                            $result_recent = mysqli_query($conn, $query_recent);
                            if(mysqli_num_rows($result_recent) > 0):
                                while($row = mysqli_fetch_assoc($result_recent)):
                            ?>
                                <div class="flex items-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-white dark:hover:bg-slate-700 hover:shadow-sm transition border border-transparent hover:border-slate-100 dark:hover:border-slate-600">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-white dark:bg-slate-600 text-slate-500 dark:text-slate-300 shadow-sm mr-3 shrink-0">
                                        <i class="fas fa-<?= ($row['vehicle_category'] ?? 'car') == 'motor' ? 'motorcycle' : 'car-side' ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($row['license_plate'] ?: 'Tanpa Plat') ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Masuk: <?= date('H:i', strtotime($row['check_in_time'])) ?></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <?php if(strpos($row['parking_token'], 'LOST') === 0): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">TIKET HILANG</span>
                                        <?php elseif($row['check_out_time']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Selesai</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Aktif</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; else: ?>
                                <div class="text-center py-8 text-slate-400">Belum ada aktivitas.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar & Navbar Logic (Tidak Diubah) ---
    const sidebarToggle = document.getElementById('sidebar-toggle'); if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); } if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); } const userMenuButton = document.getElementById('user-menu-button'); const userMenu = document.getElementById('user-menu'); const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down'); if (userMenuButton && userMenu) { userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); }

    // --- CHART LOGIC & FILTERING ---
    let incomeChart = null;

    function initChart(labels, data) {
        const ctx = document.getElementById('incomeChart');
        if (!ctx) return;

        if (incomeChart) {
            incomeChart.destroy();
        }

        incomeChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: data,
                    backgroundColor: 'rgba(245, 124, 0, 0.1)',
                    borderColor: '#F57C00',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#F57C00',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', borderDash: [5, 5] },
                        ticks: {
                            font: { size: 11 },
                            color: '#64748b',
                            callback: function(value) { return value >= 1000 ? (value/1000) + 'k' : value; }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#64748b' }
                    }
                }
            }
        });
    }

    function fetchData() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        const loader = document.getElementById('chart-loading');

        loader.classList.remove('hidden');

        fetch(`api_laporan_super.php?start_date=${start}&end_date=${end}&laporan=parkir`)
            .then(response => response.json())
            .then(data => {
                // Update Cards
                document.getElementById('val-income').textContent = 'Rp ' + (data.stats.money || '0');
                document.getElementById('val-trx').textContent = (data.stats.trx || '0');

                // Update Chart
                if (data.chart_trend) {
                    initChart(data.chart_trend.labels, data.chart_trend.data);
                }
            })
            .catch(err => console.error('Error fetching data:', err))
            .finally(() => {
                loader.classList.add('hidden');
            });
    }

    // Initialize
    document.getElementById('apply-filter').addEventListener('click', fetchData);
    fetchData(); // Load initial data

    // --- DARK MODE LOGIC ---
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
    if(themeToggle) themeToggle.addEventListener('click', toggleTheme);

    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
</script>
</body>
</html>
