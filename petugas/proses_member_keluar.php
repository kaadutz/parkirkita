<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Data User Login
$user_id_petugas = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($query_user);
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';
if (!empty($user_data['profile_photo']) && file_exists('../uploads/profile/' . $user_data['profile_photo'])) {
    $profile_picture_url = '../uploads/profile/' . $user_data['profile_photo'] . '?v=' . time();
}
$currentPage = 'transaksi_keluar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Checkout Member - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
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
        
        /* CUSTOM STYLES */
        .content-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; overflow: hidden; }
        #qr-reader { width: 100%; border-radius: 12px; overflow: hidden; margin-bottom: 1rem; }
        #qr-reader video { object-fit: cover; border-radius: 12px; }
    </style>
</head>
<body class="bg-slate-50">

<div class="flex h-screen bg-slate-50 overflow-hidden">
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
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm z-20">
             <div class="flex items-center"><button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-slate-700">Checkout Member</h1></div>
            <div class="relative"><button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group"><div class="relative"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture"><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div></div><div class="text-left hidden sm:block"><p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div><i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i></button><div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0"><div class="px-4 py-3 border-b border-slate-100"><div class="flex items-center space-x-3"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture"><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div></div></div><div class="py-2"><a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-colors"><i class="fas fa-user-circle mr-2"></i> Profil Saya</a><a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</a></div></div></div>
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="container mx-auto max-w-6xl">
                
                <div class="mb-6">
                    <a href="transaksi_keluar.php" class="inline-flex items-center text-slate-500 hover:text-orange-600 transition-colors font-medium text-sm bg-white px-4 py-2 rounded-full shadow-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Pilihan
                    </a>
                </div>

                <div id="alert-container" class="hidden mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4">
                    <div class="flex items-center gap-3">
                        <i id="alert-icon" class="fas text-xl"></i>
                        <span id="alert-message" class="font-medium"></span>
                    </div>
                    <button onclick="this.parentElement.classList.add('hidden')" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>

                <?php if (isset($_SESSION['checkout_message'])): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['checkout_message']['type'] == 'success' ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['checkout_message']['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['checkout_message']['text']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['checkout_message']); endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <div class="lg:col-span-5 space-y-6">
                        <div class="content-card p-6">
                            <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center">
                                <i class="fas fa-qrcode text-orange-500 mr-2"></i> Scan Kartu
                            </h2>
                            <p class="text-slate-500 text-sm mb-4">Gunakan alat scanner atau kamera untuk memindai kartu member.</p>
                            
                            <div id="qr-reader" class="bg-slate-100"></div>
                            
                            <div class="mt-6">
                                <label for="member_card_id" class="block text-sm font-bold text-slate-700 mb-2">Input Manual / Hasil Scan</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-barcode text-slate-400"></i>
                                    </div>
                                    <input type="text" id="member_card_id" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-mono text-lg placeholder-slate-300" placeholder="Scan atau ketik ID..." autofocus>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <span id="loading-indicator" class="hidden text-orange-500"><i class="fas fa-spinner fa-spin"></i></span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-400 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i> Tekan Enter setelah memasukkan kode.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-7">
                        <div id="empty-state" class="content-card p-10 text-center">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-id-card text-4xl text-slate-300"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-400">Menunggu Data Member</h3>
                            <p class="text-sm text-slate-400 mt-1">Silakan scan kartu member untuk menampilkan detail transaksi.</p>
                        </div>

                        <div id="transaction-details" class="hidden space-y-6">
                            
                            <div class="content-card p-6 border-l-4 border-green-500">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Member Terdeteksi</p>
                                        <h2 class="text-2xl font-bold text-slate-800 mt-1" id="display_name">Nama Member</h2>
                                        <p class="text-sm text-slate-500 font-mono mt-1" id="display_id">ID: -</p>
                                    </div>
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">
                                        <i class="fas fa-check-circle mr-1"></i> AKTIF
                                    </span>
                                </div>
                            </div>

                            <div class="content-card p-6">
                                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Rincian Parkir</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Waktu Masuk</span>
                                        <span class="font-semibold text-slate-700" id="display_checkin">-</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Waktu Keluar (Sekarang)</span>
                                        <span class="font-semibold text-slate-700" id="display_checkout">-</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Durasi</span>
                                        <span class="font-semibold text-slate-700" id="display_duration">-</span>
                                    </div>
                                    <div class="border-t border-dashed border-slate-200 my-2"></div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-600 font-bold">Total Biaya</span>
                                        <span class="text-2xl font-bold text-green-600">GRATIS</span>
                                    </div>
                                    <p class="text-xs text-right text-slate-400">*Benefit Member ParkirKita</p>
                                </div>
                            </div>

                            <div class="content-card p-6">
                                <form id="checkout-form" action="proses_checkout.php" method="POST">
                                    <input type="hidden" name="transaction_id" id="transaction_id">
                                    <input type="hidden" name="petugas_id" value="<?= $user_id_petugas ?>">
                                    <input type="hidden" name="cash_paid" value="0"> <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <label for="license_plate" class="block text-sm font-bold text-slate-700 mb-1">Konfirmasi Plat Nomor</label>
                                            <input type="text" name="license_plate" id="license_plate" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 uppercase font-bold focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all" required>
                                        </div>
                                        <div>
                                            <label for="vehicle_category" class="block text-sm font-bold text-slate-700 mb-1">Kategori Kendaraan</label>
                                            <select name="vehicle_category" id="vehicle_category" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="mobil">Mobil</option>
                                                <option value="motor">Motor</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center gap-2 transform hover:-translate-y-1">
                                            <i class="fas fa-door-open"></i> Proses Keluar
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar Logic (Tidak Diubah) ---
    const sidebarToggle = document.getElementById('sidebar-toggle'); if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); } if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); } const userMenuButton = document.getElementById('user-menu-button'); const userMenu = document.getElementById('user-menu'); const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down'); if (userMenuButton && userMenu) { userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); }

    // --- LOGIKA UTAMA HALAMAN INI ---
    const cardIdInput = document.getElementById('member_card_id');
    const transactionDetails = document.getElementById('transaction-details');
    const emptyState = document.getElementById('empty-state');
    const loadingIndicator = document.getElementById('loading-indicator');
    const alertContainer = document.getElementById('alert-container');
    
    let html5QrcodeScanner;

    function showAlert(message, type = 'error') {
        const icon = document.getElementById('alert-icon');
        const text = document.getElementById('alert-message');
        
        alertContainer.classList.remove('hidden', 'bg-green-50', 'border-green-500', 'text-green-800', 'bg-red-50', 'border-red-500', 'text-red-800');
        
        if (type === 'success') {
            alertContainer.classList.add('bg-green-50', 'border-green-500', 'text-green-800');
            icon.className = 'fas fa-check-circle text-xl';
        } else {
            alertContainer.classList.add('bg-red-50', 'border-red-500', 'text-red-800');
            icon.className = 'fas fa-exclamation-circle text-xl';
        }
        
        text.textContent = message;
        // Auto scroll to alert
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function resetView() {
        transactionDetails.classList.add('hidden');
        emptyState.classList.remove('hidden');
        cardIdInput.value = '';
        cardIdInput.focus();
    }

    async function processCardId(cardId) {
        if (!cardId) return;
        
        loadingIndicator.classList.remove('hidden');
        cardIdInput.disabled = true;

        try {
            const response = await fetch(`api_get_transaksi.php?card_id=${encodeURIComponent(cardId)}`);
            const data = await response.json();

            if (data.success) {
                const tx = data.data;
                
                // Isi Data ke UI
                document.getElementById('transaction_id').value = tx.transaction_id;
                document.getElementById('display_name').textContent = tx.member_name;
                document.getElementById('display_id').textContent = `ID: ${cardId}`;
                document.getElementById('display_checkin').textContent = tx.check_in_formatted;
                document.getElementById('display_checkout').textContent = new Date().toLocaleString('id-ID'); // Waktu sekarang
                document.getElementById('display_duration').textContent = tx.duration_formatted;
                
                // Auto-fill form jika data ada di DB (opsional)
                if(tx.license_plate) document.getElementById('license_plate').value = tx.license_plate;

                // Tampilkan UI Transaksi
                emptyState.classList.add('hidden');
                transactionDetails.classList.remove('hidden');
                
                // Fokus ke input plat nomor untuk konfirmasi
                setTimeout(() => document.getElementById('license_plate').focus(), 100);
                
            } else {
                showAlert(data.message, 'error');
                resetView();
            }
        } catch (error) {
            console.error(error);
            showAlert('Terjadi kesalahan koneksi server.', 'error');
            resetView();
        } finally {
            loadingIndicator.classList.add('hidden');
            cardIdInput.disabled = false;
            // Jangan clear value input agar user lihat apa yang discan, kecuali error
        }
    }

    // 1. Event Listener untuk Alat Scanner (Keyboard Emulation)
    // Alat scanner biasanya mengetik cepat lalu menekan ENTER
    cardIdInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Mencegah submit form default
            processCardId(this.value.trim());
        }
    });

    // 2. Event Listener untuk Kamera (HTML5-QRCode)
    function onScanSuccess(decodedText, decodedResult) {
        cardIdInput.value = decodedText;
        
        // Hentikan kamera setelah scan berhasil
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                document.getElementById('qr-reader').innerHTML = '<div class="p-4 text-center text-green-600 bg-green-50 rounded-lg"><i class="fas fa-check-circle mb-2"></i> Scan Berhasil</div>';
            }).catch(err => console.error(err));
        }
        
        processCardId(decodedText);
    }

    function onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    }

    // Inisialisasi Kamera
    try {
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", 
            { fps: 10, qrbox: { width: 250, height: 250 } }, 
            false
        );
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    } catch(e) {
        console.error("Kamera error:", e);
    }
});
</script>
</body>
</html>