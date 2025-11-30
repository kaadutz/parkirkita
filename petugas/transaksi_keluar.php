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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; } 
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; } 
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

        /* --- CUSTOM CARD DESIGN --- */
        .trx-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #eaeaea;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        .trx-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: var(--brand-orange);
        }
        .icon-circle {
            width: 100px; height: 100px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 25px;
            font-size: 40px;
            transition: 0.3s;
        }
        .card-title { font-size: 22px; font-weight: 700; color: #333; margin-bottom: 10px; }
        .card-desc { font-size: 14px; color: #777; margin-bottom: 30px; line-height: 1.6; }
        .btn-action {
            display: inline-block;
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: 0.3s;
        }
        
        /* Type Specific */
        .type-non-member .icon-circle { background: #E3F2FD; color: #2196F3; }
        .type-non-member:hover .icon-circle { background: #2196F3; color: white; }
        .type-non-member .btn-action { background: #2196F3; color: white; box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3); }
        .type-non-member .btn-action:hover { background: #1976D2; transform: translateY(-2px); }

        .type-member .icon-circle { background: #FCE4EC; color: #E91E63; }
        .type-member:hover .icon-circle { background: #E91E63; color: white; }
        .type-member .btn-action { background: #E91E63; color: white; box-shadow: 0 4px 15px rgba(233, 30, 99, 0.3); }
        .type-member .btn-action:hover { background: #C2185B; transform: translateY(-2px); }

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
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'dashboard.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-tachometer-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Dashboard</span></a>
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'transaksi_keluar.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-arrow-right-from-bracket fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Transaksi Keluar</span></a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'kelola_member.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-id-card fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Kelola Member</span></a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'laporan_petugas.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-file-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Laporan Saya</span></a>
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
        
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button>
                 <h1 class="text-xl font-semibold text-slate-700">Transaksi Keluar</h1>
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
                                <p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($user_data['email'] ?? 'user@example.com'); ?></p>
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
                </div>
            </div>
        </header>
        
        <main class="flex-1 p-8 overflow-y-auto bg-slate-50">
            <div class="container mx-auto max-w-5xl h-full flex flex-col justify-center">
                
                <div class="text-center mb-12">
                    <h1 class="text-3xl font-extrabold text-slate-800 mb-3 tracking-tight">Pilih Jenis Transaksi</h1>
                    <p class="text-slate-500 max-w-lg mx-auto">Silakan pilih kategori pelanggan untuk melanjutkan proses pembayaran parkir kendaraan keluar.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 px-4">
                    
                    <div class="trx-card type-non-member">
                        <div class="icon-circle">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3 class="card-title">Pengunjung Umum</h3>
                        <p class="card-desc">
                            Untuk kendaraan tanpa keanggotaan. Proses pembayaran menggunakan scan tiket parkir (barcode/QR).
                        </p>
                        <a href="proses_non_member.php" class="btn-action">
                            <i class="fas fa-arrow-right mr-2"></i> Proses Transaksi
                        </a>
                    </div>

                    <div class="trx-card type-member">
                        <div class="icon-circle">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3 class="card-title">Member Parkir</h3>
                        <p class="card-desc">
                            Khusus untuk pelanggan terdaftar. Proses verifikasi menggunakan kartu member atau scan QR Member.
                        </p>
                        <a href="proses_member_keluar.php" class="btn-action">
                            <i class="fas fa-arrow-right mr-2"></i> Proses Transaksi
                        </a>
                    </div>

                </div>

                <div class="mt-12 text-center text-xs text-slate-400">
                    &copy; <?= date('Y') ?> ParkirKita System. Pastikan data kendaraan sesuai sebelum memproses.
                </div>

            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- JavaScript Sidebar & Dropdown (Tidak Diubah) ---
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); }
    if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); }
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down');
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } });
        window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } });
    }
});
</script>
</body>
</html>