<?php
session_start();
// 1. Cek Login
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// 2. Validasi ID Member
$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id == 0) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'ID Member tidak valid.'];
    header("Location: kelola_member.php");
    exit();
}

// 3. Ambil Data Member
$query_member = mysqli_query($conn, "SELECT * FROM members WHERE id = '$member_id'");
$member = mysqli_fetch_assoc($query_member);

if (!$member) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Member tidak ditemukan.'];
    header("Location: kelola_member.php");
    exit();
}

// 4. Ambil Riwayat Tagihan
$query_history = mysqli_query($conn, "SELECT * FROM member_billings WHERE member_id = '$member_id' ORDER BY billing_period DESC");

// 5. Hitung Statistik
$total_pembayaran = 0;
$query_stats = mysqli_query($conn, "SELECT SUM(amount) as total FROM member_billings WHERE member_id = '$member_id' AND status = 'lunas'");
if($query_stats) {
    $stats = mysqli_fetch_assoc($query_stats);
    $total_pembayaran = $stats['total'] ?? 0;
}
$member_sejak = new DateTime($member['join_date']);
$hari_ini = new DateTime();
$lama_bergabung = $member_sejak->diff($hari_ini)->days;

// 6. Data User Login (Header)
$user_id_petugas = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($query_user);
$profile_picture_filename = $user_data['profile_photo'] ?? null;
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';
if (!empty($profile_picture_filename) && file_exists('../uploads/profile/' . $profile_picture_filename)) {
    $profile_picture_url = '../uploads/profile/' . $profile_picture_filename . '?v=' . time();
}
$currentPage = 'kelola_member.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Member: <?= htmlspecialchars($member['name']) ?> - ParkirKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; } 
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; } 
        
        /* SIDEBAR & NAVBAR (TIDAK DIUBAH) */
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
        
        /* CUSTOM CONTENT STYLES */
        .content-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden; }
        .profile-header-bg { background: linear-gradient(135deg, #1C2E4A 0%, #2A4365 100%); height: 100px; }
        .stat-card { transition: transform 0.2s; border: 1px solid #f1f5f9; }
        .stat-card:hover { transform: translateY(-3px); border-color: var(--brand-orange); }
        
        /* Modal Card CSS */
        .modal-card { width: 3.375in; height: 2.125in; box-sizing: border-box; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); position: relative; overflow: hidden; transition: all 0.3s ease; } 
        .modal-card::before { content:''; position:absolute; top:-50%; right:-50%; width:100%; height:100%; background:radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%); border-radius:50%; } 
        .modal-card::after { content:''; position:absolute; bottom:-30%; left:-30%; width:80%; height:80%; background:radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 70%); border-radius:50%; } 
        .modal-card .chip { background: linear-gradient(135deg, #d4af37 0%, #f9e076 100%); width: 40px; height: 30px; border-radius: 5px; position: relative; overflow: hidden; } 
        .modal-card .chip::before { content:''; position:absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(90deg, transparent 40%, rgba(255,255,255,0.3) 50%, transparent 60%); }
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
                    <i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm">
             <div class="flex items-center"><button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-slate-700">Detail Member</h1></div>
            <div class="relative"><button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group"><div class="relative"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture"><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div></div><div class="text-left hidden sm:block"><p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div><i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i></button><div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0"><div class="px-4 py-3 border-b border-slate-100"><div class="flex items-center space-x-3"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture"><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p><p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($user_data['email'] ?? 'user@example.com'); ?></p></div></div></div><div class="py-2"><a href="profile.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-all duration-200 group"><i class="fas fa-user-circle w-5 text-gray-400 group-hover:text-[var(--brand-orange)]"></i><span class="ml-3 font-medium">Profil Saya</span><i class="fas fa-chevron-right text-xs ml-auto"></i></a><div class="border-t border-slate-100 my-2"></div><a href="../logout.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group"><i class="fas fa-sign-out-alt w-5 text-red-500"></i><span class="ml-3 font-medium">Keluar</span><i class="fas fa-chevron-right text-xs ml-auto"></i></a></div></div></div>
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="container mx-auto max-w-6xl">
                
                <div class="mb-6">
                    <a href="kelola_member.php" class="inline-flex items-center text-slate-500 hover:text-orange-600 transition-colors font-medium text-sm bg-white px-4 py-2 rounded-full shadow-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
                    </a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <div class="lg:col-span-4 space-y-6">
                        
                        <div class="content-card">
                            <div class="profile-header-bg relative">
                                <div class="absolute top-4 right-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase shadow-sm text-white <?= $member['status'] == 'aktif' ? 'bg-green-500' : 'bg-red-500' ?>">
                                        <?= str_replace('_', ' ', $member['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="px-6 pb-6 text-center">
                                <div class="relative -mt-12 mb-4 inline-block">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($member['name']) ?>&background=EBF4FF&color=3B82F6&size=128" 
                                         alt="Member Profile" 
                                         class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg bg-white">
                                </div>
                                <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($member['name']) ?></h2>
                                <p class="text-sm text-slate-500 mb-4">
                                    <i class="fas fa-phone-alt text-xs mr-1"></i> 
                                    <?= htmlspecialchars($member['phone_number'] ?: '-') ?>
                                </p>
                                
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4">
                                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-2">ID Member</p>
                                    <p class="font-mono text-sm font-bold text-slate-700 tracking-widest bg-white px-2 py-1 rounded border border-slate-200 inline-block">
                                        <?= htmlspecialchars($member['member_card_id']) ?>
                                    </p>
                                    <div class="flex justify-center mt-2 opacity-60">
                                        <i class="fas fa-qrcode text-2xl text-slate-300"></i>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <button id="preview-card-btn" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-4 rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                                        <i class="fas fa-id-card"></i> Preview Kartu
                                    </button>
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="cetak_kartu.php?id=<?= $member['id'] ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                                            <i class="fas fa-print"></i> Cetak
                                        </a>
                                        <a href="edit_member.php?id=<?= $member['id'] ?>" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                                            <i class="fas fa-pen"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="content-card p-4 text-center stat-card">
                                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-2 text-lg">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <p class="text-xs text-slate-400 font-semibold uppercase">Bergabung</p>
                                <p class="text-lg font-bold text-slate-800"><?= $lama_bergabung ?> Hari</p>
                            </div>
                            <div class="content-card p-4 text-center stat-card">
                                <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-2 text-lg">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <p class="text-xs text-slate-400 font-semibold uppercase">Total Bayar</p>
                                <p class="text-lg font-bold text-slate-800">Rp <?= number_format($total_pembayaran/1000) ?>k</p>
                            </div>
                        </div>

                    </div>

                    <div class="lg:col-span-8 space-y-6">
                        <div class="content-card overflow-hidden">
                            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white">
                                <h3 class="text-lg font-bold text-slate-800 flex items-center">
                                    <i class="fas fa-history text-blue-500 mr-2"></i> Riwayat Tagihan
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-100">
                                            <th class="px-6 py-4">Periode Tagihan</th>
                                            <th class="px-6 py-4">Status</th>
                                            <th class="px-6 py-4">Tanggal Bayar</th>
                                            <th class="px-6 py-4 text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php if(mysqli_num_rows($query_history) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($query_history)): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                    <?= date('F Y', strtotime($row['billing_period'])) ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if($row['status'] == 'lunas'): ?>
                                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 flex items-center w-fit gap-1">
                                                            <i class="fas fa-check"></i> Lunas
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 flex items-center w-fit gap-1">
                                                            <i class="fas fa-times"></i> Belum Lunas
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-slate-500">
                                                    <?= $row['payment_date'] ? date('d M Y, H:i', strtotime($row['payment_date'])) : '-' ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-right font-mono font-bold text-slate-700">
                                                    Rp <?= number_format($row['amount']) ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">
                                                    Belum ada riwayat tagihan yang tercatat.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="card-preview-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 transition-opacity duration-300 opacity-0">
    <div class="relative w-full max-w-md transform transition-all duration-300 scale-95">
        
        <div class="flex justify-center mb-6 relative z-50">
            <div class="bg-white rounded-full p-1 shadow-lg flex items-center cursor-pointer">
                <div id="btn-qr" class="px-6 py-2 rounded-full text-sm font-bold transition-all bg-blue-600 text-white shadow-sm">
                    <i class="fas fa-qrcode mr-1"></i> QR Code
                </div>
                <div id="btn-barcode" class="px-6 py-2 rounded-full text-sm font-bold transition-all text-slate-500 hover:bg-slate-100">
                    <i class="fas fa-barcode mr-1"></i> Barcode
                </div>
            </div>
        </div>

        <div class="flex justify-center relative z-10">
            <div id="modal-card-content" class="shadow-2xl rounded-xl overflow-hidden w-full max-w-[340px]">
                </div>
        </div>

        <button id="close-modal-btn" class="absolute -top-2 right-0 md:-right-10 text-white hover:text-red-400 text-3xl transition-colors z-50 cursor-pointer">
            <i class="fas fa-times-circle"></i>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar & Navbar (Tidak Diubah) ---
    const sidebarToggle = document.getElementById('sidebar-toggle'); 
    if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); } 
    if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); } 
    const userMenuButton = document.getElementById('user-menu-button'); 
    const userMenu = document.getElementById('user-menu'); 
    
    if (userMenuButton && userMenu) { 
        userMenuButton.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            const isHidden = userMenu.classList.contains('hidden'); 
            if (isHidden) { 
                userMenu.classList.remove('hidden'); 
                setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); }, 10); 
            } else { 
                userMenu.classList.add('scale-95', 'opacity-0'); 
                setTimeout(() => { userMenu.classList.add('hidden'); }, 200); 
            } 
        }); 
        window.addEventListener('click', (e) => { 
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { 
                userMenu.classList.add('scale-95', 'opacity-0'); 
                setTimeout(() => { userMenu.classList.add('hidden'); }, 200); 
            } 
        }); 
    }
    
    // --- QR Code Mini (Halaman Utama) ---
    const miniQr = document.getElementById("qrcode-display");
    if(miniQr) {
        new QRCode(miniQr, { text: "<?= htmlspecialchars($member['member_card_id']) ?>", width: 100, height: 100, correctLevel : QRCode.CorrectLevel.M });
    }

    // --- MODAL PREVIEW KARTU (FIXED) ---
    const modal = document.getElementById('card-preview-modal');
    const openBtn = document.getElementById('preview-card-btn');
    const closeBtn = document.getElementById('close-modal-btn');
    const cardContent = document.getElementById('modal-card-content');
    const btnQR = document.getElementById('btn-qr');
    const btnBarcode = document.getElementById('btn-barcode');
    
    // Data Member dari PHP
    const memberID = "<?= htmlspecialchars($member['member_card_id']) ?>";
    const memberName = "<?= htmlspecialchars($member['name']) ?>";
    
    let currentMode = 'qr'; // Default

    function renderCard() {
        // CSS Dinamis untuk layout
        const textWidthStyle = currentMode === 'barcode' ? 'width: 40%;' : 'width: 65%;';
        const codeContainerStyle = currentMode === 'qr' 
            ? 'width: 85px; height: 85px;' 
            : 'width: 58%; height: 55px; padding: 5px; display: flex; align-items: center; justify-content: center; background: white;';

        const html = `
            <div class="modal-card rounded-xl shadow-2xl text-white relative z-10 overflow-hidden bg-blue-900" style="transition: all 0.3s;">
                <div style="position:absolute; top:-50%; right:-50%; width:100%; height:100%; background:radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%); border-radius:50%;"></div>
                <div style="position:absolute; bottom:-30%; left:-30%; width:80%; height:80%; background:radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 70%); border-radius:50%;"></div>

                <div class="h-full p-5 flex flex-col justify-between relative z-20">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-2xl tracking-tight flex items-center gap-2">
                                <i class="fas fa-parking text-orange-400"></i> Parkir<span class="text-yellow-300">Kita</span>
                            </p>
                            <p class="text-[10px] uppercase tracking-widest opacity-70 mt-1">Official Member Card</p>
                        </div>
                        <div class="w-10 h-8 rounded bg-gradient-to-br from-yellow-400 to-yellow-600 shadow border border-white/20"></div>
                    </div>
                    
                    <div class="flex justify-between items-end w-full mt-6">
                        <div class="flex-shrink-0 pr-2" style="${textWidthStyle} transition: width 0.3s;">
                            <div class="mb-3">
                                <p class="text-[10px] opacity-70 uppercase tracking-wider mb-0.5">Nama Member</p>
                                <p class="font-bold text-lg leading-tight tracking-wide shadow-black drop-shadow-sm truncate">${memberName}</p>
                            </div>
                            <div>
                                <p class="text-[10px] opacity-70 uppercase tracking-wider mb-0.5">ID Kartu</p>
                                <p class="font-mono text-sm tracking-widest font-medium text-yellow-300 truncate">${memberID}</p>
                            </div>
                        </div>
                        
                        <div class="rounded-xl bg-white shadow-lg flex items-center justify-center overflow-hidden" style="${codeContainerStyle} transition: all 0.3s;">
                            ${currentMode === 'qr' 
                                ? '<div id="qrcode-modal"></div>' 
                                : '<img id="barcode-modal" style="width: 100%; height: 100%; object-fit: contain;">'} 
                        </div>
                    </div>
                </div>
            </div>`;
        
        cardContent.innerHTML = html;

        // Render Kode (dengan Timeout agar elemen sudah ada di DOM)
        setTimeout(() => {
            if (currentMode === 'qr') {
                document.getElementById("qrcode-modal").innerHTML = "";
                new QRCode(document.getElementById("qrcode-modal"), { 
                    text: memberID, 
                    width: 85, 
                    height: 85, 
                    correctLevel: QRCode.CorrectLevel.M 
                });
            } else {
                try {
                    JsBarcode("#barcode-modal", memberID, {
                        format: "CODE128",
                        lineColor: "#000",
                        width: 1.5,
                        height: 40,
                        displayValue: false,
                        margin: 0,
                        background: "#ffffff"
                    });
                } catch(e) {
                    console.error("Barcode Error:", e);
                }
            }
        }, 50);

        // Update Button Style
        if(currentMode === 'qr') {
            btnQR.className = 'px-6 py-2 rounded-full text-sm font-bold transition-all bg-blue-600 text-white shadow-sm cursor-default';
            btnBarcode.className = 'px-6 py-2 rounded-full text-sm font-bold transition-all text-slate-500 hover:bg-slate-100 cursor-pointer';
        } else {
            btnBarcode.className = 'px-6 py-2 rounded-full text-sm font-bold transition-all bg-blue-600 text-white shadow-sm cursor-default';
            btnQR.className = 'px-6 py-2 rounded-full text-sm font-bold transition-all text-slate-500 hover:bg-slate-100 cursor-pointer';
        }
    }

    // --- Event Listeners ---
    btnQR.addEventListener('click', () => { if(currentMode !== 'qr') { currentMode = 'qr'; renderCard(); } });
    btnBarcode.addEventListener('click', () => { if(currentMode !== 'barcode') { currentMode = 'barcode'; renderCard(); } });

    if (openBtn) {
        openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.remove('hidden'); // Tampilkan dulu
            currentMode = 'qr'; // Reset mode
            renderCard(); // Render saat modal sudah tidak hidden
            setTimeout(() => { 
                modal.classList.remove('opacity-0'); 
                modal.querySelector('.relative').classList.remove('scale-95'); 
            }, 10);
        });
    }

    function closeModal() { 
        modal.classList.add('opacity-0'); 
        modal.querySelector('.relative').classList.add('scale-95'); 
        setTimeout(() => { 
            modal.classList.add('hidden'); 
            cardContent.innerHTML = ''; 
        }, 300); 
    }

    if(closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
});
</script>
</body>
</html>