<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$user_id_petugas = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($query_user);
$profile_picture_filename = $user_data['profile_photo'] ?? null;
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';
if (!empty($profile_picture_filename) && file_exists('../uploads/profile/' . $profile_picture_filename)) {
    $profile_picture_url = '../uploads/profile/' . $profile_picture_filename . '?v=' . time();
}
$currentPage = 'transaksi_keluar.php';

// --- HARGA DENDA ---
$denda_motor = 25000;
$denda_mobil = 50000;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Proses Tiket Hilang - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        .content-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; overflow: hidden; }
    </style>
</head>
<body class="bg-slate-50">

<div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- SIDEBAR -->
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100">
                <a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center transition-all duration-300 hover:scale-105">
                    <i class="fas fa-parking text-[var(--brand-orange)] text-3xl"></i>
                    <span class="sidebar-logo-text ml-3 text-gray-700">Parkir<span class="text-[var(--brand-pink)]">Kita</span></span>
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
                    <div class="sidebar-text ml-3"><p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div>
                </div>
                <a href="../logout.php" class="sidebar-link flex items-center mt-3 py-2 px-2 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg"><i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Logout</span></a>
            </div>
        </div>
    </aside>

    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        <!-- HEADER -->
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm z-20">
             <div class="flex items-center"><button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-slate-700">Proses Tiket Hilang</h1></div>
            <div class="relative"><button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group"><div class="relative"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture"><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div></div><div class="text-left hidden sm:block"><p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div><i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i></button><div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0"><div class="px-4 py-3 border-b border-slate-100"><div class="flex items-center space-x-3"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture"><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div></div></div><div class="py-2"><a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-colors"><i class="fas fa-user-circle mr-2"></i> Profil Saya</a><a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</a></div></div></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="container mx-auto max-w-4xl">

                <div class="mb-6">
                    <a href="transaksi_keluar.php" class="inline-flex items-center text-slate-500 hover:text-orange-600 transition-colors font-medium text-sm bg-white px-4 py-2 rounded-full shadow-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Pilihan
                    </a>
                </div>

                <!-- ALERTS -->
                <?php if (isset($_SESSION['pesan'])): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['pesan_tipe'] == 'sukses' ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['pesan_tipe'] == 'sukses' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['pesan']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['pesan']); unset($_SESSION['pesan_tipe']); endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">

                    <div class="md:col-span-8">
                        <div class="content-card p-8">
                            <div class="flex items-center gap-4 mb-6 border-b pb-4">
                                <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-slate-800">Form Tiket Hilang</h2>
                                    <p class="text-slate-500 text-sm">Input data kendaraan untuk memproses denda.</p>
                                </div>
                            </div>

                            <form action="proses_tiket_hilang.php" method="POST" id="form-denda">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="license_plate" class="block text-sm font-bold text-slate-700 mb-2">Plat Nomor</label>
                                        <input type="text" name="license_plate" id="license_plate" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-50 uppercase font-bold text-lg focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all" placeholder="B 1234 XYZ" required autofocus>
                                    </div>
                                    <div>
                                        <label for="vehicle_category" class="block text-sm font-bold text-slate-700 mb-2">Jenis Kendaraan</label>
                                        <select name="vehicle_category" id="vehicle_category" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-50 font-medium focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all" required>
                                            <option value="" disabled selected>Pilih Jenis</option>
                                            <option value="motor" data-denda="<?= $denda_motor ?>">Motor (Rp <?= number_format($denda_motor) ?>)</option>
                                            <option value="mobil" data-denda="<?= $denda_mobil ?>">Mobil (Rp <?= number_format($denda_mobil) ?>)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-orange-50 p-6 rounded-xl border border-orange-100 mb-8">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-slate-600 font-medium">Biaya Denda</span>
                                        <span id="display_denda" class="text-2xl font-bold text-orange-600">Rp 0</span>
                                    </div>
                                    <p class="text-xs text-orange-400">* Tarif flat untuk penggantian tiket hilang.</p>
                                </div>

                                <div class="mb-6">
                                    <label for="cash_paid" class="block text-sm font-bold text-slate-700 mb-2">Uang Diterima (Tunai)</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">Rp</span>
                                        <input type="number" name="cash_paid" id="cash_paid" class="w-full pl-12 pr-4 py-3 border border-slate-300 rounded-xl text-xl font-bold text-slate-800 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="0" required>
                                    </div>
                                    <div class="mt-3 flex justify-between items-center px-1">
                                        <span class="text-sm text-slate-500 font-medium">Kembalian:</span>
                                        <span id="change_due_display" class="font-bold text-lg text-slate-400">Rp 0</span>
                                    </div>
                                </div>

                                <button type="submit" id="submit-button" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2 transform hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none" disabled>
                                    <i class="fas fa-print"></i> Bayar Denda & Cetak Struk
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100 text-blue-800">
                            <h3 class="font-bold text-lg mb-3"><i class="fas fa-info-circle mr-2"></i>Ketentuan</h3>
                            <ul class="space-y-3 text-sm opacity-90">
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-circle text-[6px] mt-2"></i>
                                    <span>Pastikan identitas pemilik kendaraan sesuai dengan STNK.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-circle text-[6px] mt-2"></i>
                                    <span>Denda berlaku flat, tidak menghitung durasi parkir.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-circle text-[6px] mt-2"></i>
                                    <span>Transaksi ini akan tercatat sebagai "Lost Ticket".</span>
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
    // --- Sidebar Logic ---
    const sidebarToggle = document.getElementById('sidebar-toggle'); if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); } if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); } const userMenuButton = document.getElementById('user-menu-button'); const userMenu = document.getElementById('user-menu'); const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down'); if (userMenuButton && userMenu) { userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); }

    // --- Calculator Logic ---
    const vehicleSelect = document.getElementById('vehicle_category');
    const displayDenda = document.getElementById('display_denda');
    const cashInput = document.getElementById('cash_paid');
    const changeDisplay = document.getElementById('change_due_display');
    const submitBtn = document.getElementById('submit-button');

    let currentDenda = 0;

    vehicleSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        currentDenda = parseInt(selectedOption.getAttribute('data-denda')) || 0;

        displayDenda.textContent = 'Rp ' + currentDenda.toLocaleString('id-ID');
        cashInput.value = ''; // Reset cash input
        calculateChange();
    });

    cashInput.addEventListener('input', calculateChange);

    function calculateChange() {
        const cash = parseFloat(cashInput.value) || 0;
        const change = cash - currentDenda;

        if (currentDenda > 0 && cash >= currentDenda) {
            changeDisplay.textContent = 'Rp ' + change.toLocaleString('id-ID');
            changeDisplay.className = 'font-bold text-lg text-blue-600';
            submitBtn.disabled = false;
        } else {
            const kurang = Math.abs(change);
            changeDisplay.textContent = currentDenda > 0 ? 'Kurang Rp ' + kurang.toLocaleString('id-ID') : 'Rp 0';
            changeDisplay.className = 'font-bold text-lg text-red-500';
            submitBtn.disabled = true;
        }
    }
});
</script>
</body>
</html>