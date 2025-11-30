<?php
session_start();
// 1. Cek Login & Role (Super Admin Only)
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
// Default Tanggal
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* --- VARS SESUAI TEMA PARKIRKITA --- */
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; }
        
        /* SIDEBAR STYLES */
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; }
        .sidebar-link:hover { background-color: var(--brand-light-bg); color: var(--brand-orange); border-left-color: var(--brand-orange); transform: translateX(4px); }
        .sidebar-active { background-color: var(--brand-light-bg); color: var(--brand-orange); font-weight: 700; border-left-color: var(--brand-orange); }
        
        /* TRANSITIONS */
        #sidebar, #main-content { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-text, .sidebar-logo-text { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); white-space: nowrap; }
        
        /* COLLAPSED STATE */
        body.sidebar-collapsed #sidebar { width: 5.5rem; }
        body.sidebar-collapsed #main-content { margin-left: 5.5rem; }
        body.sidebar-collapsed .sidebar-text, body.sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; margin-left: 0; pointer-events: none; }
        body.sidebar-collapsed .sidebar-link, body.sidebar-collapsed #user-info-sidebar { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; }
        
        .profile-picture { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 3px solid #FDBA74; }
        .profile-picture:hover { transform: scale(1.05); border-color: var(--brand-orange); }
        
        /* DROPDOWN ANIMATION */
        .dropdown-menu { transform-origin: top right; transition: all 0.2s ease-out; }
        
        /* CUSTOM UTILS */
        .quick-btn.active { background-color: #F57C00; color: white; border-color: #F57C00; }
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #F57C00; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        @media print { 
            #sidebar, header, .filter-section, .no-print, .nav-tabs { display: none !important; } 
            #main-content { margin-left: 0 !important; } 
            main { padding: 0 !important; } 
            body { background-color: white; } 
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
        }
    </style>
</head>
<body class="bg-slate-50">

<div class="flex h-screen bg-slate-50 overflow-hidden">
    
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100">
                <a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center transition-all duration-300 hover:scale-105">
                    <i class="fas fa-parking text-[var(--brand-orange)] text-3xl"></i>
                    <span class="sidebar-logo-text ml-3 text-gray-700 transition-all duration-300">Parkir<span class="text-[var(--brand-pink)]">Kita</span></span>
                </a>
            </div>
            <nav class="mt-4 text-gray-600 font-medium flex-grow">
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
            <div class="mt-auto p-4 border-t border-slate-100">
                <div id="user-info-sidebar" class="flex items-center transition-all duration-300">
                    <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover profile-picture">
                    <div class="sidebar-text ml-3 transition-all duration-300">
                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($user_data['name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $user_data['role']); ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="sidebar-link flex items-center mt-3 py-2 px-2 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg">
                    <i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm relative z-20">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50">
                     <i class="fas fa-bars fa-lg"></i>
                 </button>
                 <h1 class="text-xl font-semibold text-slate-700">Analisis Laporan</h1>
             </div>
            
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group focus:outline-none">
                    <div class="relative">
                        <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture">
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($user_data['name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $user_data['role']); ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i>
                </button>
                
                <div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-50 hidden border border-slate-200 dropdown-menu origin-top-right">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <div class="flex items-center space-x-3">
                            <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($user_data['name']); ?></p>
                                <p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $user_data['role']); ?></p>
                                <p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($user_data['email'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="py-2">
                        <a href="profile.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-all duration-200 group">
                            <i class="fas fa-user-circle w-5 text-gray-400 group-hover:text-[var(--brand-orange)]"></i>
                            <span class="ml-3 font-medium">Profil Saya</span>
                            <i class="fas fa-chevron-right text-xs ml-auto"></i>
                        </a>
                        <div class="border-t border-slate-100 my-2"></div>
                        <a href="../logout.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group">
                            <i class="fas fa-sign-out-alt w-5 text-red-500"></i>
                            <span class="ml-3 font-medium">Keluar</span>
                            <i class="fas fa-chevron-right text-xs ml-auto"></i>
                        </a>
                    </div>
                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 rounded-b-xl"><p class="text-xs text-gray-500 text-center">ParkirKita v1.0 • Super Admin</p></div>
                </div>
            </div>
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            
            <div class="print-header hidden mb-6 text-center">
                <h1 class="text-2xl font-bold">Laporan ParkirKita</h1>
                <p>Periode: <span id="print-period">-</span></p>
            </div>

            <div class="container mx-auto max-w-7xl">
                
                <div class="flex border-b border-slate-200 mb-6 no-print">
                    <button onclick="switchTab('parkir')" id="tab-parkir" class="py-3 px-6 font-bold text-sm transition-all border-b-2 border-orange-500 text-orange-600 bg-orange-50/20">
                       <i class="fas fa-car mr-2"></i> Laporan Parkir
                    </button>
                    <button onclick="switchTab('langganan')" id="tab-langganan" class="py-3 px-6 font-bold text-sm transition-all border-b-2 border-transparent text-slate-500 hover:text-orange-500">
                       <i class="fas fa-id-card mr-2"></i> Laporan Member
                    </button>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 mb-6 filter-section no-print">
                    <div class="flex flex-wrap gap-2 mb-5 pb-4 border-b border-slate-100">
                        <button onclick="setDate('today')" class="quick-btn px-4 py-1.5 rounded-full text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Hari Ini</button>
                        <button onclick="setDate('yesterday')" class="quick-btn px-4 py-1.5 rounded-full text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Kemarin</button>
                        <button onclick="setDate('this_month')" class="quick-btn active px-4 py-1.5 rounded-full text-xs font-bold border border-slate-200 bg-orange-500 text-white border-orange-500 transition">Bulan Ini</button>
                        <button onclick="setDate('last_month')" class="quick-btn px-4 py-1.5 rounded-full text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Bulan Lalu</button>
                    </div>

                    <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <input type="hidden" name="laporan" id="input-laporan" value="parkir">
                        <input type="hidden" name="page" id="input-page" value="1">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Petugas</label>
                            <select name="filter_petugas" id="filter_petugas" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                                <option value="semua">Semua Petugas</option>
                                <?php while($p = mysqli_fetch_assoc($q_petugas)): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div id="filter-tipe-container">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipe Pelanggan</label>
                            <select name="filter_tipe" id="filter_tipe" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                                <option value="semua">Semua</option>
                                <option value="member">Member</option>
                                <option value="umum">Umum</option>
                            </select>
                        </div>

                        <button type="submit" class="bg-orange-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition text-sm h-[38px] shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i> Analisis
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 no-print">
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Total Pendapatan</p>
                            <h3 class="text-xl font-bold text-slate-800 mt-1 flex items-center gap-2">
                                <span id="val-money">Rp 0</span>
                                <span class="spinner hidden" id="spin-money"></span>
                            </h3>
                        </div>
                        <div class="p-2 bg-green-50 rounded text-green-600"><i class="fas fa-wallet text-lg"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Total Transaksi</p>
                            <h3 class="text-xl font-bold text-slate-800 mt-1" id="val-trx">0</h3>
                        </div>
                        <div class="p-2 bg-blue-50 rounded text-blue-600"><i class="fas fa-exchange-alt text-lg"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-500 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Rata-Rata / Trx</p>
                            <h3 class="text-xl font-bold text-slate-800 mt-1">Rp <span id="val-avg">0</span></h3>
                        </div>
                        <div class="p-2 bg-purple-50 rounded text-purple-600"><i class="fas fa-chart-line text-lg"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-orange-500 flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Jam Tersibuk</p>
                            <h3 class="text-xl font-bold text-slate-800 mt-1"><i class="far fa-clock mr-1 text-sm"></i> <span id="val-busy">-</span></h3>
                        </div>
                        <div class="p-2 bg-orange-50 rounded text-orange-600"><i class="fas fa-hourglass-half text-lg"></i></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 no-print">
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                        <h3 class="text-sm font-bold text-slate-700 mb-4 border-b pb-2">Tren Pendapatan Harian</h3>
                        <div class="h-64 w-full"><canvas id="chartTrend"></canvas></div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col">
                        <h3 class="text-sm font-bold text-slate-700 mb-4 border-b pb-2">Komposisi & Performa</h3>
                        <div class="h-40 w-full mb-6 flex justify-center"><canvas id="chartPie"></canvas></div>
                        
                        <h3 class="text-xs font-bold text-slate-400 uppercase mb-3">Top 3 Petugas Terbaik</h3>
                        <ul class="space-y-3 text-sm flex-1" id="leaderboard-list">
                            </ul>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mb-3 no-print">
                    <button onclick="window.print()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-bold shadow flex items-center gap-2"><i class="fas fa-print"></i> Print</button>
                    <a href="#" id="btn-excel" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow flex items-center gap-2"><i class="fas fa-file-excel"></i> Excel</a>
                    <a href="#" id="btn-pdf" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow flex items-center gap-2"><i class="fas fa-file-pdf"></i> PDF</a>
                </div>

                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-600 uppercase text-xs font-bold border-b border-slate-200">
                                <tr id="table-header"></tr>
                            </thead>
                            <tbody id="table-body" class="divide-y divide-slate-100"></tbody>
                        </table>
                        <div id="table-loading" class="hidden py-8 text-center"><div class="spinner"></div></div>
                    </div>
                    <div id="pagination-container" class="p-4 border-t border-slate-100"></div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar Logic ---
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const body = document.body;
    if (localStorage.getItem('sidebarCollapsed') === 'true') body.classList.add('sidebar-collapsed');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
        });
    }

    // --- Dropdown Menu Logic (FIXED) ---
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden'); // Toggle hidden class
        });
        window.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden'); // Hide when clicking outside
            }
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
        
        // Update Export Link
        const filterTipe = document.getElementById('filter_tipe').value;
        const filterPetugas = document.getElementById('filter_petugas').value;
        document.getElementById('btn-excel').href = `export_excel.php?${params}&filter_tipe=${filterTipe}&filter_petugas=${filterPetugas}`;
        document.getElementById('btn-pdf').href = `export_pdf.php?${params}&filter_tipe=${filterTipe}&filter_petugas=${filterPetugas}`;
        document.getElementById('print-period').innerText = document.getElementById('start_date').value + ' s/d ' + document.getElementById('end_date').value;

        // UI Loading
        document.getElementById('table-body').innerHTML = '';
        document.getElementById('table-loading').classList.remove('hidden');
        document.getElementById('spin-money').classList.remove('hidden');

        fetch(`api_laporan_super.php?${params}`)
            .then(res => res.json())
            .then(data => {
                // Stats
                document.getElementById('val-money').innerText = 'Rp ' + data.stats.money;
                document.getElementById('val-trx').innerText = data.stats.trx;
                document.getElementById('val-avg').innerText = data.stats.avg;
                document.getElementById('val-busy').innerText = data.stats.busiest;

                // Table
                document.getElementById('table-body').innerHTML = data.html;
                document.getElementById('pagination-container').innerHTML = data.pagination;
                
                const type = document.getElementById('input-laporan').value;
                document.getElementById('table-header').innerHTML = type === 'parkir' 
                    ? `<th>Tipe</th><th>Kendaraan</th><th>Masuk</th><th>Petugas</th><th class="text-right">Biaya</th>`
                    : `<th>Nama</th><th>Periode</th><th>Tgl Bayar</th><th>Petugas</th><th class="text-right">Nominal</th>`;

                // Leaderboard
                const list = document.getElementById('leaderboard-list');
                list.innerHTML = '';
                if(data.top_officers.length > 0) {
                    data.top_officers.forEach((u, i) => {
                        list.innerHTML += `<li class="flex justify-between items-center bg-slate-50 p-2 rounded border border-slate-100">
                            <div class="flex items-center gap-2"><span class="font-bold text-orange-500">#${i+1}</span> <span class="text-slate-700">${u.name}</span></div>
                            <span class="text-xs font-bold bg-white px-2 py-1 rounded shadow-sm border">${u.total_trx} Trx</span>
                        </li>`;
                    });
                } else { list.innerHTML = '<li class="text-center text-gray-400 italic py-2">Belum ada data.</li>'; }

                // Charts
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
        chartTrendInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: data,
                    borderColor: '#F57C00', // Orange ParkirKita
                    backgroundColor: 'rgba(245, 124, 0, 0.1)',
                    tension: 0.4, fill: true, pointRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: {display: false} }, scales: { y: { beginAtZero: true } } }
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
                    backgroundColor: ['#D81B60', '#CBD5E1'] // Pink ParkirKita & Slate
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: {position: 'right'} }, cutout: '70%' }
        });
    }

    window.changePage = function(p) { document.getElementById('input-page').value = p; loadReport(); }
    
    window.switchTab = function(t) {
        document.getElementById('input-laporan').value = t;
        document.getElementById('input-page').value = 1;
        
        const tabParkir = document.getElementById('tab-parkir');
        const tabLangganan = document.getElementById('tab-langganan');
        const filterType = document.getElementById('filter-tipe-container');

        if(t==='parkir') {
            tabParkir.className = 'py-2 px-6 font-semibold border-b-2 border-orange-500 text-orange-600 transition-colors';
            tabLangganan.className = 'py-2 px-6 font-semibold border-b-2 border-transparent text-slate-500 hover:text-orange-500 transition-colors';
            if(filterType) filterType.classList.remove('hidden');
        } else {
            tabLangganan.className = 'py-2 px-6 font-semibold border-b-2 border-orange-500 text-orange-600 transition-colors';
            tabParkir.className = 'py-2 px-6 font-semibold border-b-2 border-transparent text-slate-500 hover:text-orange-500 transition-colors';
            if(filterType) filterType.classList.add('hidden');
        }
        loadReport();
    }

    window.setDate = function(period) {
        const startEl = document.getElementById('start_date');
        const endEl = document.getElementById('end_date');
        const d = new Date();
        
        document.querySelectorAll('.quick-btn').forEach(b => { b.classList.remove('active', 'bg-orange-500', 'text-white', 'border-orange-500'); b.classList.add('text-slate-600', 'border-slate-200'); });
        event.target.classList.add('active', 'bg-orange-500', 'text-white', 'border-orange-500');
        event.target.classList.remove('text-slate-600', 'border-slate-200');

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
    
    // Load Data Awal
    loadReport();
});
</script>
</body>
</html>