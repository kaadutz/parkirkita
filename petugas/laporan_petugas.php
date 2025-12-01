<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// ID Petugas yang Login
$user_id_petugas = $_SESSION['user_id'];
$nama_petugas = $_SESSION['user_name'] ?? 'Petugas';

// Ambil Foto Profil
$q_user = mysqli_query($conn, "SELECT profile_photo FROM users WHERE id = '$user_id_petugas'");
$d_user = mysqli_fetch_assoc($q_user);
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($nama_petugas) . '&background=F57C00&color=fff';
if (!empty($d_user['profile_photo']) && file_exists('../uploads/profile/' . $d_user['profile_photo'])) {
    $profile_picture_url = '../uploads/profile/' . $d_user['profile_photo'];
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Default Filter Values
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Petugas - ParkirKita</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        /* HEADER GLASS */
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
        }
        .dark .glass-header {
            background: rgba(15, 23, 42, 0.9);
        }

        /* PRINT */
        @media print {
            #sidebar, header, .filter-section, .no-print, .action-buttons, #main-header { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            #main-content { margin-left: 0 !important; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #000; padding: 5px; }
            body { background-color: white; color: black; }
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
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

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 relative">

        <!-- HEADER -->
        <header id="main-header" class="h-20 glass-header border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors">
                     <i class="fas fa-bars text-xl"></i>
                 </button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Laporan Saya</h1>
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

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="max-w-7xl mx-auto">

                <div class="print-header hidden mb-6 text-center">
                    <h2 class="text-2xl font-bold">Laporan Transaksi ParkirKita</h2>
                    <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
                    <p>Petugas: <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                    <hr style="margin: 10px 0;">
                </div>

                <!-- CHART SECTION -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6 no-print">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-line text-brand-orange"></i> Grafik Pendapatan
                    </h3>
                    <div class="h-64 w-full">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>

                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6 no-print">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between group hover:shadow-lg hover:border-blue-500 transition-all">
                        <div>
                            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1 uppercase tracking-wide">Total Transaksi</p>
                            <h3 class="text-3xl font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <span id="total-transaksi">0</span>
                                <span id="loader-trx" class="hidden animate-spin h-5 w-5 border-2 border-brand-orange border-t-transparent rounded-full"></span>
                            </h3>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between group hover:shadow-lg hover:border-green-500 transition-all">
                        <div>
                            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1 uppercase tracking-wide">Total Pendapatan</p>
                            <h3 class="text-3xl font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <span class="text-lg font-medium text-slate-400">Rp</span>
                                <span id="total-pendapatan">0</span>
                                <span id="loader-money" class="hidden animate-spin h-5 w-5 border-2 border-brand-orange border-t-transparent rounded-full"></span>
                            </h3>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- FILTER & ACTIONS -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6 filter-section no-print overflow-hidden">
                    <div class="flex border-b border-slate-100 dark:border-slate-700 overflow-x-auto whitespace-nowrap">
                        <button onclick="switchTab('parkir')" id="tab-parkir" class="flex-1 py-4 text-center font-bold text-sm transition-all border-b-2 border-brand-orange text-brand-orange bg-orange-50 dark:bg-slate-700/50">
                            <i class="fas fa-car mr-2"></i> Laporan Parkir
                        </button>
                        <button onclick="switchTab('langganan')" id="tab-langganan" class="flex-1 py-4 text-center font-bold text-sm transition-all border-b-2 border-transparent text-slate-500 hover:text-brand-orange hover:bg-slate-50 dark:hover:bg-slate-700">
                            <i class="fas fa-id-card mr-2"></i> Laporan Member
                        </button>
                    </div>

                    <div class="p-6">
                        <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
                            <input type="hidden" name="laporan" id="input-laporan" value="parkir">
                            <input type="hidden" name="page" id="input-page" value="1">

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Dari Tanggal</label>
                                <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Sampai Tanggal</label>
                                <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                            </div>

                            <div id="filter-tipe-container">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Tipe Pelanggan</label>
                                <select name="filter_tipe" id="filter_tipe" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                                    <option value="semua">Semua</option>
                                    <option value="member">Member Only</option>
                                    <option value="umum">Non-Member</option>
                                </select>
                            </div>

                            <div>
                                <button type="submit" class="w-full bg-brand-orange hover:bg-orange-600 text-white py-3 px-4 rounded-xl font-bold transition shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2">
                                    <i class="fas fa-filter"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mb-6 action-buttons no-print">
                    <button onclick="window.print()" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center text-sm gap-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="#" id="btn-export-excel" target="_blank" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center text-sm gap-2">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" id="btn-export-pdf" target="_blank" class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center text-sm gap-2">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>

                <!-- TABLE -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden min-h-[300px]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-700">
                                <tr id="table-header" class="text-xs uppercase font-bold tracking-wider"></tr>
                            </thead>
                            <tbody id="table-body" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm"></tbody>
                        </table>
                        <div id="table-loading" class="hidden py-12 text-center w-full">
                            <div class="animate-spin h-8 w-8 mx-auto mb-3 border-4 border-brand-orange border-t-transparent rounded-full"></div>
                            <p class="text-slate-400 text-sm font-medium">Sedang memuat data...</p>
                        </div>
                    </div>

                    <div id="pagination-container" class="p-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/30"></div>
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

    // --- AJAX Logic ---
    const form = document.getElementById('filterForm');
    const inputPage = document.getElementById('input-page');
    const tableBody = document.getElementById('table-body');
    const tableHeader = document.getElementById('table-header');
    const tableLoading = document.getElementById('table-loading');
    const paginationContainer = document.getElementById('pagination-container');
    const btnPdf = document.getElementById('btn-export-pdf');
    const btnExcel = document.getElementById('btn-export-excel');

    let myChart = null;

    const headersParkir = `<th class="p-5">Tipe</th><th class="p-5">Kendaraan</th><th class="p-5">Masuk</th><th class="p-5">Keluar</th><th class="p-5 text-right">Biaya</th>`;
    const headersLangganan = `<th class="p-5">Nama Member</th><th class="p-5">Periode</th><th class="p-5">Tgl Bayar</th><th class="p-5">Petugas</th><th class="p-5 text-right">Nominal</th>`;

    window.loadReport = function() {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();

        const filterVal = document.getElementById('filter_tipe').value;
        btnPdf.href = `export_laporan_petugas.php?${params}&filter_member=${filterVal}`;
        btnExcel.href = `export_excel.php?${params}&filter_member=${filterVal}`;

        tableBody.innerHTML = '';
        tableLoading.classList.remove('hidden');
        document.getElementById('loader-trx').classList.remove('hidden');
        document.getElementById('loader-money').classList.remove('hidden');

        fetch(`api_laporan.php?${params}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('total-transaksi').innerText = data.stats.trx;
                document.getElementById('total-pendapatan').innerText = data.stats.money;

                tableBody.innerHTML = data.html;
                paginationContainer.innerHTML = data.pagination;

                const currentLaporan = document.getElementById('input-laporan').value;
                tableHeader.innerHTML = (currentLaporan === 'parkir') ? headersParkir : headersLangganan;

                renderChart(data.chart.labels, data.chart.data);
            })
            .catch(err => console.error(err))
            .finally(() => {
                tableLoading.classList.add('hidden');
                document.getElementById('loader-trx').classList.add('hidden');
                document.getElementById('loader-money').classList.add('hidden');
            });
    }

    window.changePage = function(pageNum) {
        inputPage.value = pageNum;
        loadReport();
    }

    window.switchTab = function(type) {
        document.getElementById('input-laporan').value = type;
        inputPage.value = 1;

        const tabParkir = document.getElementById('tab-parkir');
        const tabLangganan = document.getElementById('tab-langganan');
        const filterContainer = document.getElementById('filter-tipe-container');

        const activeClass = "flex-1 py-4 text-center font-bold text-sm transition-all border-b-2 border-brand-orange text-brand-orange bg-orange-50 dark:bg-slate-700/50";
        const inactiveClass = "flex-1 py-4 text-center font-bold text-sm transition-all border-b-2 border-transparent text-slate-500 hover:text-brand-orange hover:bg-slate-50 dark:hover:bg-slate-700";

        if(type === 'parkir') {
            tabParkir.className = activeClass;
            tabLangganan.className = inactiveClass;
            filterContainer.classList.remove('hidden');
        } else {
            tabLangganan.className = activeClass;
            tabParkir.className = inactiveClass;
            filterContainer.classList.add('hidden');
        }
        loadReport();
    }

    function renderChart(labels, data) {
        const ctx = document.getElementById('reportChart').getContext('2d');
        if (myChart) myChart.destroy();

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? '#334155' : '#f1f5f9';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: data,
                    borderColor: '#F57C00',
                    backgroundColor: 'rgba(245, 124, 0, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#F57C00'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, borderDash: [5, 5] },
                        ticks: { color: textColor, font: { family: "'Plus Jakarta Sans', sans-serif" } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { family: "'Plus Jakarta Sans', sans-serif" } }
                    }
                }
            }
        });
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); inputPage.value = 1; loadReport(); });

    switchTab('parkir');

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
            // Re-render chart for colors
            loadReport();
        });
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
});
</script>
</body>
</html>
