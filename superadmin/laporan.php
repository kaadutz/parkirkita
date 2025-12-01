<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    header("Location: ../login.php");
    exit();
}

include '../koneksi.php';

$user_id = $_SESSION['user_id'];

// Ambil Data User untuk Header
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($query_user);

// Profile Picture Logic
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff';
if (!empty($user_data['profile_photo']) && file_exists('../uploads/profile/' . $user_data['profile_photo'])) {
    $profile_picture_url = '../uploads/profile/' . $user_data['profile_photo'] . '?v=' . time();
}

// Data untuk Dropdown Filter Petugas
$q_petugas = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'petugas' ORDER BY name ASC");

$currentPage = basename($_SERVER['PHP_SELF']);
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Super Admin - ParkirKita</title>
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

        /* GLASS EFFECT */
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
        }
        .dark .glass-header {
            background: rgba(15, 23, 42, 0.9);
        }

        /* PRINT */
        @media print {
            #sidebar, header, .filter-section, .no-print, .nav-tabs { display: none !important; }
            #main-content { margin-left: 0 !important; }
            main { padding: 0 !important; }
            body { background-color: white; color: black; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
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
                    <i class="fas fa-tachometer-alt fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="kelola_petugas.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'kelola_petugas.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-user-shield fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Kelola Petugas</span>
                </a>
                <a href="laporan.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'laporan.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-file-invoice-dollar fa-fw text-lg mr-3"></i>
                    <span class="font-medium">Laporan Pusat</span>
                </a>
            </nav>
        </div>

        <div class="mt-auto p-4 border-t border-slate-100 dark:border-slate-700">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 flex items-center gap-3">
                <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-white dark:border-slate-600 shadow-sm">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($user_data['name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize"><?= str_replace('_', ' ', $user_data['role']); ?></p>
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
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Analisis Laporan</h1>
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

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="max-w-7xl mx-auto">

                <div class="print-header hidden mb-6 text-center">
                    <h1 class="text-2xl font-bold">Laporan ParkirKita</h1>
                    <p>Periode: <span id="print-period">-</span></p>
                </div>

                <div class="flex border-b border-slate-200 dark:border-slate-700 mb-6 no-print">
                    <button onclick="switchTab('parkir')" id="tab-parkir" class="py-3 px-6 font-bold text-sm transition-all border-b-2 border-brand-orange text-brand-orange bg-orange-50 dark:bg-slate-800/50">
                       <i class="fas fa-car mr-2"></i> Laporan Parkir
                    </button>
                    <button onclick="switchTab('langganan')" id="tab-langganan" class="py-3 px-6 font-bold text-sm transition-all border-b-2 border-transparent text-slate-500 hover:text-brand-orange dark:text-slate-400 dark:hover:text-brand-orange">
                       <i class="fas fa-id-card mr-2"></i> Laporan Member
                    </button>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6 filter-section no-print">
                    <div class="flex flex-wrap gap-2 mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                        <button onclick="setDate('today')" class="quick-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Hari Ini</button>
                        <button onclick="setDate('yesterday')" class="quick-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Kemarin</button>
                        <button onclick="setDate('this_month')" class="quick-btn active px-4 py-2 rounded-lg text-xs font-bold bg-brand-orange text-white border-brand-orange shadow-lg shadow-orange-500/20 transition">Bulan Ini</button>
                        <button onclick="setDate('last_month')" class="quick-btn px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Bulan Lalu</button>
                    </div>

                    <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 items-end">
                        <input type="hidden" name="laporan" id="input-laporan" value="parkir">
                        <input type="hidden" name="page" id="input-page" value="1">

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Dari Tanggal</label>
                            <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Sampai Tanggal</label>
                            <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Petugas</label>
                            <select name="filter_petugas" id="filter_petugas" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                                <option value="semua">Semua Petugas</option>
                                <?php while($p = mysqli_fetch_assoc($q_petugas)): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div id="filter-tipe-container">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Tipe Pelanggan</label>
                            <select name="filter_tipe" id="filter_tipe" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white">
                                <option value="semua">Semua</option>
                                <option value="member">Member</option>
                                <option value="umum">Umum</option>
                            </select>
                        </div>

                        <button type="submit" class="bg-brand-orange text-white font-bold py-3 px-4 rounded-xl hover:bg-orange-600 transition text-sm shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i> Analisis Data
                        </button>
                    </form>
                </div>

                <!-- SUMMARY CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 no-print">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-l-4 border-green-500 dark:border-slate-700 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide">Total Pendapatan</p>
                            <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mt-1 flex items-center gap-2">
                                <span id="val-money">Rp 0</span>
                                <span class="hidden animate-spin h-4 w-4 border-2 border-brand-orange border-t-transparent rounded-full" id="spin-money"></span>
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center text-lg"><i class="fas fa-wallet"></i></div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-l-4 border-blue-500 dark:border-slate-700 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide">Total Transaksi</p>
                            <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mt-1" id="val-trx">0</h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-l-4 border-pink-500 dark:border-slate-700 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide">Rata-Rata / Trx</p>
                            <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mt-1">Rp <span id="val-avg">0</span></h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 flex items-center justify-center text-lg"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-l-4 border-orange-500 dark:border-slate-700 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide">Jam Tersibuk</p>
                            <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mt-1"><i class="far fa-clock mr-1 text-sm text-slate-400"></i> <span id="val-busy">-</span></h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 flex items-center justify-center text-lg"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>

                <!-- CHARTS -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 no-print">
                    <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-700 dark:text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-chart-area text-brand-orange"></i> Tren Pendapatan Harian
                        </h3>
                        <div class="h-64 w-full"><canvas id="chartTrend"></canvas></div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col">
                        <h3 class="text-sm font-bold text-slate-700 dark:text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-chart-pie text-brand-orange"></i> Komposisi & Performa
                        </h3>
                        <div class="h-40 w-full mb-8 flex justify-center"><canvas id="chartPie"></canvas></div>

                        <h3 class="text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Top 3 Petugas Terbaik</h3>
                        <ul class="space-y-3 text-sm flex-1" id="leaderboard-list"></ul>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mb-4 no-print">
                    <button onclick="window.print()" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center text-sm gap-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="#" id="btn-excel" target="_blank" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center text-sm gap-2">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" id="btn-pdf" target="_blank" class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center text-sm gap-2">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>

                <!-- TABLE -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 min-h-[300px]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left whitespace-nowrap">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 uppercase text-xs font-bold border-b border-slate-200 dark:border-slate-700">
                                <tr id="table-header"></tr>
                            </thead>
                            <tbody id="table-body" class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-600 dark:text-slate-300"></tbody>
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

    // --- LOGIKA UTAMA (AJAX) ---
    let chartTrendInstance = null;
    let chartPieInstance = null;

    // Setup Date Defaults
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('start_date').valueAsDate = firstDay;
    document.getElementById('end_date').valueAsDate = today;

    window.loadReport = function() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData).toString();

        const filterTipe = document.getElementById('filter_tipe').value;
        const filterPetugas = document.getElementById('filter_petugas').value;
        document.getElementById('btn-excel').href = `export_excel.php?${params}&filter_tipe=${filterTipe}&filter_petugas=${filterPetugas}`;
        document.getElementById('btn-pdf').href = `export_pdf.php?${params}&filter_tipe=${filterTipe}&filter_petugas=${filterPetugas}`;
        document.getElementById('print-period').innerText = document.getElementById('start_date').value + ' s/d ' + document.getElementById('end_date').value;

        document.getElementById('table-body').innerHTML = '';
        document.getElementById('table-loading').classList.remove('hidden');
        document.getElementById('spin-money').classList.remove('hidden');

        fetch(`api_laporan_super.php?${params}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('val-money').innerText = 'Rp ' + data.stats.money;
                document.getElementById('val-trx').innerText = data.stats.trx;
                document.getElementById('val-avg').innerText = data.stats.avg;
                document.getElementById('val-busy').innerText = data.stats.busiest;

                document.getElementById('table-body').innerHTML = data.html;
                document.getElementById('pagination-container').innerHTML = data.pagination;

                const type = document.getElementById('input-laporan').value;
                document.getElementById('table-header').innerHTML = type === 'parkir'
                    ? `<th class="p-4">Tipe</th><th class="p-4">Kendaraan</th><th class="p-4">Masuk</th><th class="p-4">Petugas</th><th class="p-4 text-right">Biaya</th>`
                    : `<th class="p-4">Nama</th><th class="p-4">Periode</th><th class="p-4">Tgl Bayar</th><th class="p-4">Petugas</th><th class="p-4 text-right">Nominal</th>`;

                const list = document.getElementById('leaderboard-list');
                list.innerHTML = '';
                if(data.top_officers.length > 0) {
                    data.top_officers.forEach((u, i) => {
                        list.innerHTML += `<li class="flex justify-between items-center bg-slate-50 dark:bg-slate-700/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-brand-orange text-white text-xs font-bold">#${i+1}</span>
                                <span class="font-bold text-slate-700 dark:text-white">${u.name}</span>
                            </div>
                            <span class="text-xs font-bold bg-white dark:bg-slate-600 dark:text-white px-2 py-1 rounded-lg shadow-sm border dark:border-slate-500">${u.total_trx} Trx</span>
                        </li>`;
                    });
                } else { list.innerHTML = '<li class="text-center text-slate-400 italic py-4">Belum ada data.</li>'; }

                renderTrendChart(data.chart_trend.labels, data.chart_trend.data);
                renderPieChart(data.chart_pie);

            })
            .finally(() => {
                document.getElementById('table-loading').classList.add('hidden');
                document.getElementById('spin-money').classList.add('hidden');
            });
    }

    function renderTrendChart(labels, data) {
        const ctx = document.getElementById('chartTrend').getContext('2d');
        if (chartTrendInstance) chartTrendInstance.destroy();

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? '#334155' : '#f1f5f9';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        chartTrendInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: data,
                    borderColor: '#F57C00',
                    backgroundColor: 'rgba(245, 124, 0, 0.15)',
                    tension: 0.4, fill: true, pointRadius: 4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: {display: false} },
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

    function renderPieChart(dataValues) {
        const ctx = document.getElementById('chartPie').getContext('2d');
        if (chartPieInstance) chartPieInstance.destroy();
        chartPieInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Member', 'Umum'],
                datasets: [{
                    data: dataValues,
                    backgroundColor: ['#D81B60', '#CBD5E1'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: {position: 'right', labels: { font: { family: "'Plus Jakarta Sans', sans-serif" } }} }, cutout: '75%' }
        });
    }

    window.changePage = function(p) { document.getElementById('input-page').value = p; loadReport(); }

    window.switchTab = function(t) {
        document.getElementById('input-laporan').value = t;
        document.getElementById('input-page').value = 1;

        const tabParkir = document.getElementById('tab-parkir');
        const tabLangganan = document.getElementById('tab-langganan');
        const filterType = document.getElementById('filter-tipe-container');

        const activeClass = 'py-3 px-6 font-bold text-sm transition-all border-b-2 border-brand-orange text-brand-orange bg-orange-50 dark:bg-slate-800/50';
        const inactiveClass = 'py-3 px-6 font-bold text-sm transition-all border-b-2 border-transparent text-slate-500 hover:text-brand-orange dark:text-slate-400 dark:hover:text-brand-orange';

        if(t==='parkir') {
            tabParkir.className = activeClass;
            tabLangganan.className = inactiveClass;
            if(filterType) filterType.classList.remove('hidden');
        } else {
            tabLangganan.className = activeClass;
            tabParkir.className = inactiveClass;
            if(filterType) filterType.classList.add('hidden');
        }
        loadReport();
    }

    window.setDate = function(period) {
        const startEl = document.getElementById('start_date');
        const endEl = document.getElementById('end_date');
        const d = new Date();

        document.querySelectorAll('.quick-btn').forEach(b => {
            b.classList.remove('active', 'bg-brand-orange', 'text-white', 'border-brand-orange', 'shadow-lg');
            b.classList.add('text-slate-600', 'dark:text-slate-400', 'border-slate-200', 'dark:border-slate-600');
        });

        event.target.classList.add('active', 'bg-brand-orange', 'text-white', 'border-brand-orange', 'shadow-lg');
        event.target.classList.remove('text-slate-600', 'dark:text-slate-400', 'border-slate-200', 'dark:border-slate-600');

        if (period === 'today') {
            startEl.valueAsDate = d; endEl.valueAsDate = d;
        } else if (period === 'yesterday') {
            d.setDate(d.getDate() - 1);
            startEl.valueAsDate = d; endEl.valueAsDate = d;
        } else if (period === 'this_month') {
            startEl.valueAsDate = new Date(d.getFullYear(), d.getMonth(), 1);
            endEl.valueAsDate = new Date();
        } else if (period === 'last_month') {
            startEl.valueAsDate = new Date(d.getFullYear(), d.getMonth() - 1, 1);
            endEl.valueAsDate = new Date(d.getFullYear(), d.getMonth(), 0);
        }
        loadReport();
    }

    document.getElementById('filterForm').addEventListener('submit', (e)=>{e.preventDefault(); loadReport();});

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
            loadReport(); // Redraw chart
        });
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }

    loadReport();
});
</script>
</body>
</html>
