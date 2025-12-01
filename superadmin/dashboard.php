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

        /* WELCOME BANNER */
        .welcome-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            position: relative; overflow: hidden;
        }
        .welcome-banner::before {
            content: ''; position: absolute; top: -50%; right: -10%; width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(245,124,0,0.1) 0%, rgba(245,124,0,0) 70%);
            border-radius: 50%; pointer-events: none;
        }
        .welcome-banner::after {
            content: ''; position: absolute; bottom: -50%; left: -10%; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 60%);
            border-radius: 50%; pointer-events: none;
        }

        /* CARDS */
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.08);
        }

        /* GLASS EFFECT */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .dark .glass-effect {
            background: rgba(15, 23, 42, 0.95);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
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
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize">Super Admin</p>
                </div>
                <a href="../logout.php" class="text-slate-400 hover:text-red-500 transition-colors p-2" title="Logout">
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
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- WELCOME BANNER & FILTER -->
                <div class="welcome-banner rounded-2xl p-8 text-white shadow-xl shadow-slate-900/10">
                    <div class="flex flex-col md:flex-row justify-between items-end gap-6 relative z-10">
                        <div>
                            <h2 class="text-3xl font-bold mb-2">Halo, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>!</h2>
                            <p class="text-slate-300 text-lg">Berikut ringkasan performa bisnis parkir Anda.</p>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md p-3 rounded-xl border border-white/20 flex flex-wrap gap-3 items-center">
                            <div class="flex items-center gap-2 text-white/80 px-2">
                                <i class="far fa-calendar-alt"></i>
                                <span class="text-sm font-semibold">Filter:</span>
                            </div>
                            <input type="date" id="start_date" class="bg-slate-800/50 border border-white/10 text-white text-xs rounded-lg px-3 py-2 font-bold focus:ring-2 focus:ring-brand-orange outline-none" value="<?= date('Y-m-d', strtotime('-6 days')) ?>">
                            <span class="text-white/60 text-xs">s/d</span>
                            <input type="date" id="end_date" class="bg-slate-800/50 border border-white/10 text-white text-xs rounded-lg px-3 py-2 font-bold focus:ring-2 focus:ring-brand-orange outline-none" value="<?= date('Y-m-d') ?>">
                            <button id="apply-filter" class="bg-brand-orange hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-lg shadow-orange-500/20">
                                <i class="fas fa-sync-alt mr-1"></i> Update
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STATS GRID -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <!-- Static Stats -->
                    <div class="stats-card bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col justify-between h-full">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
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

                    <div class="stats-card bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col justify-between h-full">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 rounded-xl bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 flex items-center justify-center text-xl">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Member</span>
                        </div>
                        <div>
                            <?php $total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) as total FROM members"))['total'] ?? 0; ?>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white"><?= number_format($total_members) ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Total Terdaftar</p>
                        </div>
                    </div>

                    <!-- Dynamic Stats -->
                    <div class="stats-card bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-l-4 border-green-500 dark:border-slate-700 flex flex-col justify-between h-full relative overflow-hidden">
                         <div class="absolute right-0 top-0 p-4 opacity-5 pointer-events-none">
                            <i class="fas fa-wallet text-8xl text-green-500"></i>
                        </div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center text-xl">
                                <i class="fas fa-coins"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendapatan</span>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight" id="val-income">Rp 0</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Periode Terpilih</p>
                        </div>
                    </div>

                    <div class="stats-card bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-l-4 border-orange-500 dark:border-slate-700 flex flex-col justify-between h-full relative overflow-hidden">
                        <div class="absolute right-0 top-0 p-4 opacity-5 pointer-events-none">
                            <i class="fas fa-car text-8xl text-orange-500"></i>
                        </div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="w-12 h-12 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 flex items-center justify-center text-xl">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Transaksi</span>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white" id="val-trx">0</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kendaraan Keluar</p>
                        </div>
                    </div>
                </div>

                <!-- CHARTS & RECENT -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Chart -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 relative">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Analisis Pendapatan</h3>
                            <div id="chart-loading" class="hidden absolute inset-0 bg-white/80 dark:bg-slate-800/80 z-20 flex items-center justify-center rounded-2xl backdrop-blur-sm">
                                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-orange"></div>
                            </div>
                        </div>
                        <div class="h-80 w-full">
                            <canvas id="incomeChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col h-full">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Aktivitas Terbaru</h3>
                            <a href="laporan.php" class="text-xs font-bold text-brand-orange hover:underline">Lihat Semua</a>
                        </div>

                        <div class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar max-h-[320px]">
                            <?php
                            $query_recent = "SELECT * FROM parking_transactions ORDER BY check_in_time DESC LIMIT 5";
                            $result_recent = mysqli_query($conn, $query_recent);
                            if(mysqli_num_rows($result_recent) > 0):
                                while($row = mysqli_fetch_assoc($result_recent)):
                            ?>
                                <div class="flex items-center p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition border border-transparent hover:border-slate-200 dark:hover:border-slate-600">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-white dark:bg-slate-600 text-slate-500 dark:text-slate-300 shadow-sm mr-3 shrink-0">
                                        <i class="fas fa-<?= ($row['vehicle_category'] ?? 'car') == 'motor' ? 'motorcycle' : 'car-side' ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($row['license_plate'] ?: 'Tanpa Plat') ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Masuk: <?= date('H:i', strtotime($row['check_in_time'])) ?></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <?php if(strpos($row['parking_token'], 'LOST') === 0): ?>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 uppercase">Lost</span>
                                        <?php elseif($row['check_out_time']): ?>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 uppercase">Done</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700 uppercase">Active</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; else: ?>
                                <div class="text-center py-10 text-slate-400 flex flex-col items-center">
                                    <i class="far fa-clipboard text-3xl mb-2 opacity-50"></i>
                                    <span>Belum ada aktivitas.</span>
                                </div>
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
    // --- Sidebar & Mobile Menu ---
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

    // --- Chart Logic ---
    let incomeChart = null;

    function initChart(labels, data) {
        const ctx = document.getElementById('incomeChart');
        if (!ctx) return;

        if (incomeChart) {
            incomeChart.destroy();
        }

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? '#334155' : '#f1f5f9';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        incomeChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: data,
                    backgroundColor: 'rgba(245, 124, 0, 0.15)',
                    borderColor: '#F57C00',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#F57C00',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
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
                        backgroundColor: isDark ? '#1e293b' : '#fff',
                        titleColor: isDark ? '#fff' : '#1e293b',
                        bodyColor: isDark ? '#cbd5e1' : '#64748b',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 13, family: "'Plus Jakarta Sans', sans-serif" },
                        bodyFont: { size: 13, family: "'Plus Jakarta Sans', sans-serif" },
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
                        grid: { color: gridColor, borderDash: [5, 5] },
                        ticks: {
                            font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                            color: textColor,
                            callback: function(value) { return value >= 1000 ? (value/1000) + 'k' : value; }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, color: textColor }
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

    document.getElementById('apply-filter').addEventListener('click', fetchData);
    fetchData();

    // --- Dark Mode ---
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
            fetchData(); // Redraw chart for color update
        });
    }

    // Check initial theme
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
});
</script>
</body>
</html>
