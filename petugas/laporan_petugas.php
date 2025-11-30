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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; } 
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; } 
        
        /* Sidebar & Navbar Styles (Asli) */
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; } 
        .sidebar-link:hover { background-color: var(--brand-light-bg); color: var(--brand-orange); border-left-color: var(--brand-orange); transform: translateX(4px); } 
        .sidebar-active { background-color: var(--brand-light-bg); color: var(--brand-orange); font-weight: 700; border-left-color: var(--brand-orange); } 
        #sidebar, #main-content { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); } 
        .sidebar-text, .sidebar-logo-text { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); white-space: nowrap; } 
        body.sidebar-collapsed #sidebar { width: 5.5rem; } 
        body.sidebar-collapsed #main-content { margin-left: 5.5rem; } 
        body.sidebar-collapsed .sidebar-text, body.sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; margin-left: 0; pointer-events: none; } 
        body.sidebar-collapsed .sidebar-link, body.sidebar-collapsed #user-info-sidebar { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; } 
        .profile-picture { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 3px solid #FDBA74; } 
        .profile-picture:hover { transform: scale(1.05); border-color: var(--brand-orange); } 
        .dropdown-menu { transform-origin: top right; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Loading Spinner */
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #F57C00; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        @media print {
            #sidebar, header, .filter-section, .no-print, .action-buttons, #main-header { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            #main-content { margin-left: 0 !important; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #000; padding: 5px; }
            body { background-color: white; }
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
                    <span class="sidebar-text ml-4">Dashboard</span>
                </a>
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'transaksi_keluar.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-arrow-right-from-bracket fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4">Transaksi Keluar</span>
                </a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'kelola_member.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-id-card fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4">Kelola Member</span>
                </a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'laporan_petugas.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-file-invoice-dollar fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4">Laporan Saya</span>
                </a>
            </nav>
            <div class="mt-auto p-4 border-t border-slate-100">
                <div id="user-info-sidebar" class="flex items-center transition-all duration-300">
                    <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover profile-picture">
                    <div class="sidebar-text ml-3 transition-all duration-300">
                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="sidebar-link flex items-center mt-3 py-2 px-2 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg">
                    <i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        
        <header id="main-header" class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50">
                     <i class="fas fa-bars fa-lg"></i>
                 </button>
                 <h1 class="text-xl font-semibold text-slate-700">Laporan Saya</h1>
             </div>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group">
                    <div class="relative">
                        <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture">
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i>
                </button>
                <div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <div class="flex items-center space-x-3">
                            <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                                <p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
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
                            <i class="fas fa-sign-out-alt w-5 text-red-500 group-hover:text-red-700"></i>
                            <span class="ml-3 font-medium">Keluar</span>
                            <i class="fas fa-chevron-right text-xs ml-auto"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-8 bg-slate-50/50">
            
            <div class="print-header hidden mb-6 text-center">
                <h2 class="text-2xl font-bold">Laporan Transaksi ParkirKita</h2>
                <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
                <p>Petugas: <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                <hr style="margin: 10px 0;">
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-slate-100 mb-6 no-print">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-line text-orange-500"></i> Grafik Pendapatan
                </h3>
                <div class="h-64 w-full">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 no-print">
                <div class="bg-white p-5 rounded-xl shadow-md border border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Total Transaksi</p>
                        <h3 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                            <span id="total-transaksi">0</span>
                            <span id="loader-trx" class="spinner hidden"></span>
                        </h3>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-full"><i class="fas fa-receipt text-xl"></i></div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-md border border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Total Pendapatan</p>
                        <h3 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                            <span class="text-sm font-normal">Rp</span> 
                            <span id="total-pendapatan">0</span>
                            <span id="loader-money" class="spinner hidden"></span>
                        </h3>
                    </div>
                    <div class="p-3 bg-green-50 text-green-600 rounded-full"><i class="fas fa-wallet text-xl"></i></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-slate-100 mb-6 filter-section no-print">
                <div class="flex border-b border-slate-100 overflow-x-auto whitespace-nowrap">
                    <button onclick="switchTab('parkir')" id="tab-parkir" class="flex-1 py-4 text-center font-medium text-sm transition-colors border-b-2 border-orange-500 text-orange-600 bg-orange-50/30">
                        <i class="fas fa-car mr-2"></i> Laporan Parkir
                    </button>
                    <button onclick="switchTab('langganan')" id="tab-langganan" class="flex-1 py-4 text-center font-medium text-sm transition-colors border-b-2 border-transparent text-slate-500 hover:text-orange-500">
                        <i class="fas fa-id-card mr-2"></i> Laporan Member
                    </button>
                </div>

                <div class="p-5">
                    <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <input type="hidden" name="laporan" id="input-laporan" value="parkir">
                        <input type="hidden" name="page" id="input-page" value="1">
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Dari Tanggal</label>
                            <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Sampai Tanggal</label>
                            <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                        </div>
                        
                        <div id="filter-tipe-container">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tipe Pelanggan</label>
                            <select name="filter_tipe" id="filter_tipe" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                                <option value="semua">Semua</option>
                                <option value="member">Member Only</option>
                                <option value="umum">Non-Member</option>
                            </select>
                        </div>

                        <div>
                            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 px-4 rounded-lg font-medium transition text-sm h-[38px] flex items-center justify-center shadow-md">
                                <i class="fas fa-filter mr-2"></i> Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="flex justify-end gap-3 mb-4 action-buttons no-print">
                <button onclick="window.print()" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center text-sm">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
                <a href="#" id="btn-export-excel" target="_blank" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center text-sm">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </a>
                <a href="#" id="btn-export-pdf" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center text-sm">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-slate-100 overflow-hidden min-h-[300px]">
                <div class="overflow-x-auto scrollbar-default">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <tr id="table-header">
                                </tr>
                        </thead>
                        <tbody id="table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                    <div id="table-loading" class="hidden py-10 text-center w-full">
                        <div class="spinner w-8 h-8 mx-auto mb-2 border-t-orange-500"></div>
                        <p class="text-slate-400 text-sm">Memuat data...</p>
                    </div>
                </div>
                
                <div id="pagination-container" class="p-4 border-t border-slate-100 bg-slate-50"></div>
            </div>

        </main>
    </div>

    <div id="mobile-backdrop" class="fixed inset-0 bg-black/50 z-10 hidden md:hidden transition-opacity opacity-0"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Logic
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const body = document.body;
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');

        if(localStorage.getItem('sidebarCollapsed') === 'true') body.classList.add('sidebar-collapsed');
        if(sidebarToggle) sidebarToggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
        });
        if(userMenuButton) {
            userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); userMenu.classList.toggle('hidden'); });
            window.addEventListener('click', (e) => { if(!userMenuButton.contains(e.target)) userMenu.classList.add('hidden'); });
        }

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

        const headersParkir = `<th class="px-6 py-3">Tipe</th><th class="px-6 py-3">Kendaraan</th><th class="px-6 py-3">Masuk</th><th class="px-6 py-3">Keluar</th><th class="px-6 py-3 text-right">Biaya</th>`;
        const headersLangganan = `<th class="px-6 py-3">Nama Member</th><th class="px-6 py-3">Periode</th><th class="px-6 py-3">Tgl Bayar</th><th class="px-6 py-3">Petugas</th><th class="px-6 py-3 text-right">Nominal</th>`;

        window.loadReport = function() {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();

            // Update Link Export
            const filterVal = document.getElementById('filter_tipe').value;
            btnPdf.href = `export_laporan_petugas.php?${params}&filter_member=${filterVal}`; // Pastikan file ini ada (sama dengan export_laporan.php super admin tapi disesuaikan querynya)
            btnExcel.href = `export_excel.php?${params}&filter_member=${filterVal}`; // Pastikan file ini ada

            // UI Loading
            tableBody.innerHTML = '';
            tableLoading.classList.remove('hidden');
            document.getElementById('loader-trx').classList.remove('hidden');
            document.getElementById('loader-money').classList.remove('hidden');

            // Fetch API (Gunakan api_laporan.php yang sudah Anda buat sebelumnya)
            fetch(`api_laporan.php?${params}`)
                .then(res => res.json())
                .then(data => {
                    // Stats
                    document.getElementById('total-transaksi').innerText = data.stats.trx;
                    document.getElementById('total-pendapatan').innerText = data.stats.money;
                    
                    // Table & Paging
                    tableBody.innerHTML = data.html;
                    paginationContainer.innerHTML = data.pagination;
                    
                    const currentLaporan = document.getElementById('input-laporan').value;
                    tableHeader.innerHTML = (currentLaporan === 'parkir') ? headersParkir : headersLangganan;

                    // Chart
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
            
            if(type === 'parkir') {
                tabParkir.className = "flex-1 py-4 text-center font-medium text-sm transition-colors border-b-2 border-orange-500 text-orange-600 bg-orange-50/30";
                tabLangganan.className = "flex-1 py-4 text-center font-medium text-sm transition-colors border-b-2 border-transparent text-slate-500 hover:text-orange-500";
                filterContainer.classList.remove('hidden');
            } else {
                tabLangganan.className = "flex-1 py-4 text-center font-medium text-sm transition-colors border-b-2 border-orange-500 text-orange-600 bg-orange-50/30";
                tabParkir.className = "flex-1 py-4 text-center font-medium text-sm transition-colors border-b-2 border-transparent text-slate-500 hover:text-orange-500";
                filterContainer.classList.add('hidden');
            }
            loadReport();
        }

        function renderChart(labels, data) {
            const ctx = document.getElementById('reportChart').getContext('2d');
            if (myChart) myChart.destroy();
            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: data,
                        borderColor: '#F57C00', // Warna Orange ParkirKita
                        backgroundColor: 'rgba(245, 124, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
                }
            });
        }

        form.addEventListener('submit', (e) => { e.preventDefault(); inputPage.value = 1; loadReport(); });
        
        // Load Awal
        switchTab('parkir');
    });
    </script>
</body>
</html>