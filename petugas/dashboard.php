<?php
session_start();
// Keamanan: Hanya petugas dan super_admin yang bisa akses
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

// --- DATA UNTUK TABEL KENDARAAN DI DALAM ---
$query_kendaraan_didalam = "SELECT * FROM parking_transactions WHERE check_out_time IS NULL ORDER BY check_in_time DESC LIMIT 10";
$result_kendaraan_didalam = mysqli_query($conn, $query_kendaraan_didalam);

// --- AMBIL DATA PROFILE PETUGAS YANG LOGIN ---
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; } 
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; } 
        
        /* SIDEBAR STYLES (TIDAK DIUBAH) */
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
        .dropdown-item { transition: all 0.2s ease; } .dropdown-item:hover { transform: translateX(4px); }

        /* NEW CONTENT STYLES */
        .welcome-card {
            background: linear-gradient(135deg, #1C2E4A 0%, #2c3e50 100%);
            color: white; border-radius: 16px; padding: 2rem; position: relative; overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(28, 46, 74, 0.4);
        }
        .welcome-card::after {
            content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%; pointer-events: none;
        }
        
        .stat-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            border: 1px solid #f0f0f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        
        .icon-box {
            width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        }
        
        .modern-table th {
            background-color: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; padding: 1rem 1.5rem; text-align: left;
        }
        .modern-table td {
            padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.875rem;
        }
        .modern-table tr:last-child td { border-bottom: none; }
        .modern-table tr:hover td { background-color: #f8fafc; }
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
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'transaksi_keluar.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-arrow-right-from-bracket fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Transaksi Keluar</span>
                </a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'kelola_member.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-id-card fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Kelola Member</span>
                </a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'laporan_petugas.php') ? 'sidebar-active' : '' ?>">
                    <i class="fas fa-file-alt fa-fw text-xl w-8 text-center"></i>
                    <span class="sidebar-text ml-4 transition-all duration-300">Laporan Saya</span>
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
                    <i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4 transition-all duration-300">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b border-slate-200 shadow-sm z-20">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button>
                 <h1 class="text-xl font-semibold text-slate-700">Dashboard Petugas</h1>
             </div>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-50 hover:bg-slate-100 px-4 py-2 rounded-xl transition-all duration-200 group">
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
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-colors"><i class="fas fa-user-circle mr-2"></i> Profil Saya</a>
                        <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</a>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="container mx-auto max-w-7xl">
                
                <div class="welcome-card mb-8">
                    <h2 class="text-2xl font-bold mb-2">Halo, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>! 👋</h2>
                    <p class="text-slate-200 opacity-90">Selamat bertugas kembali. Berikut adalah ringkasan aktivitas parkir hari ini.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    
                    <div class="stat-card flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Transaksi Hari Ini</p>
                            <h3 class="text-3xl font-bold text-slate-800"><?= number_format($transaksi_hari_ini) ?></h3>
                            <p class="text-xs text-slate-400 mt-1">Kendaraan keluar</p>
                        </div>
                        <div class="icon-box bg-blue-50 text-blue-600">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>

                    <div class="stat-card flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pendapatan Hari Ini</p>
                            <h3 class="text-3xl font-bold text-slate-800">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></h3>
                            <p class="text-xs text-slate-400 mt-1">Total tunai diterima</p>
                        </div>
                        <div class="icon-box bg-green-50 text-green-600">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>

                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Kendaraan di Dalam</h3>
                            <p class="text-sm text-slate-500">10 kendaraan terbaru yang belum checkout</p>
                        </div>
                        <div class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-bold">
                            Live Data
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full modern-table">
                            <thead>
                                <tr>
                                    <th>Token / Plat</th>
                                    <th>Tipe Pelanggan</th>
                                    <th>Waktu Masuk</th>
                                    <th>Durasi Sementara</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result_kendaraan_didalam) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result_kendaraan_didalam)): 
                                        $masuk = new DateTime($row['check_in_time']);
                                        $sekarang = new DateTime();
                                        $diff = $masuk->diff($sekarang);
                                        $durasi = $diff->h . " jam " . $diff->i . " menit";
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold text-slate-700"><?= htmlspecialchars($row['license_plate'] ?: '-') ?></div>
                                            <div class="text-xs text-slate-400 font-mono mt-0.5"><?= htmlspecialchars($row['parking_token']) ?></div>
                                        </td>
                                        <td>
                                            <?php if($row['member_id']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                                                    Member
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                                    Umum
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-slate-600">
                                            <?= date('d M Y, H:i', strtotime($row['check_in_time'])) ?>
                                        </td>
                                        <td class="text-slate-500 font-medium">
                                            <i class="far fa-clock mr-1 text-xs"></i> <?= $durasi ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-400 italic">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-car-side text-4xl mb-3 opacity-30"></i>
                                                <span>Tidak ada kendaraan yang sedang parkir.</span>
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
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar & Navbar Logic (Tidak Diubah) ---
    const sidebarToggle = document.getElementById('sidebar-toggle'); if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); } if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); } const userMenuButton = document.getElementById('user-menu-button'); const userMenu = document.getElementById('user-menu'); const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down'); if (userMenuButton && userMenu) { userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); }
});
</script>
</body>
</html>