<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$billing_id = isset($_GET['billing_id']) ? (int)$_GET['billing_id'] : 0;
if ($billing_id == 0) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'ID Tagihan tidak valid.'];
    header("Location: kelola_member.php");
    exit();
}

// Ambil data tagihan dan member terkait dari database
$query = "SELECT mb.*, m.name as member_name
          FROM member_billings mb
          JOIN members m ON mb.member_id = m.id 
          WHERE mb.id = '$billing_id' AND mb.status = 'belum_lunas'";
$result = mysqli_query($conn, $query);
$billing = mysqli_fetch_assoc($result);

if (!$billing) { 
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Tagihan tidak valid atau sudah lunas.']; 
    header("Location: kelola_member.php"); 
    exit(); 
}

// Data untuk Header & Sidebar
$user_id_petugas = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($query_user);
$profile_picture_filename = $user_data['profile_photo'] ?? null;
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';
if (!empty($profile_picture_filename) && file_exists('../uploads/profile/' . $user_data['profile_photo'])) {
    $profile_picture_url = '../uploads/profile/' . $profile_picture_filename . '?v=' . time();
}
$currentPage = 'kelola_member.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Bayar Tagihan Member - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; } body { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; } .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; } .sidebar-link:hover { background-color: var(--brand-light-bg); color: var(--brand-orange); border-left-color: var(--brand-orange); transform: translateX(4px); } .sidebar-active { background-color: var(--brand-light-bg); color: var(--brand-orange); font-weight: 700; border-left-color: var(--brand-orange); } #sidebar, #main-content { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); } .sidebar-text, .sidebar-logo-text { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); white-space: nowrap; } body.sidebar-collapsed #sidebar { width: 5.5rem; } body.sidebar-collapsed #main-content { margin-left: 5.5rem; } body.sidebar-collapsed .sidebar-text, body.sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; margin-left: 0; pointer-events: none; } body.sidebar-collapsed .sidebar-link, body.sidebar-collapsed #user-info-sidebar { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; } .profile-picture { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 3px solid #FDBA74; } .profile-picture:hover { transform: scale(1.05); border-color: var(--brand-orange); } .dropdown-menu { transform-origin: top right; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="bg-slate-50">
<div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <!-- Sidebar Content -->
        </div>
    </aside>

    <!-- Main Content -->
    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-slate-700">Pembayaran Tagihan Member</h1></div>
            <!-- Header Kanan (Dropdown Profil) -->
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-8 bg-slate-50/50">
            <div class="container mx-auto">
                <div class="mb-6"><a href="kelola_member.php" class="text-blue-600 hover:text-blue-800 font-medium"><i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar Member</a></div>
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-container mb-4 p-4 rounded-lg text-white bg-red-500 shadow-md"><?= htmlspecialchars($_SESSION['message']['text']) ?><button class="float-right font-bold" onclick="this.parentElement.style.display='none'">&times;</button></div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-slate-800 mb-6 border-b pb-4">Form Pembayaran Tagihan</h2>
                    <form action="proses_kelola_member.php" method="POST">
                        <input type="hidden" name="billing_id" value="<?= $billing_id ?>">
                        <div class="space-y-6">
                            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="text-sm text-slate-600">Nama Member</label>
                                    <p class="font-semibold text-lg text-slate-800"><?= htmlspecialchars($billing['member_name']) ?></p>
                                </div>
                                <div class="flex justify-between items-center">
                                    <label class="text-sm text-slate-600">Tagihan untuk</label>
                                    <p class="font-semibold text-lg text-slate-800">Bulan <?= date('F Y', strtotime($billing['billing_period'])) ?></p>
                                </div>
                            </div>
                            <div class="border-t pt-6 space-y-4">
                                <div class="flex justify-between items-center">
                                    <label class="font-medium text-slate-700">Total Tagihan</label>
                                    <p class="font-bold text-xl text-orange-600">Rp <?= number_format($billing['amount']) ?></p>
                                    <input type="hidden" id="subscription_fee" value="<?= $billing['amount'] ?>">
                                </div>
                                <div class="flex justify-between items-center">
                                    <label for="cash_paid" class="font-medium text-slate-700">Uang Diterima</label>
                                    <div class="relative w-1/2">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">Rp</span>
                                        <input type="number" name="cash_paid" id="cash_paid" class="w-full p-2 pl-8 border border-slate-300 rounded-md" placeholder="0" required>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <label class="font-medium text-slate-700">Kembalian</label>
                                    <p id="change_due" class="font-semibold text-lg text-blue-600">Rp 0</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 text-right space-x-4">
                            <a href="kelola_member.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg">Batal</a>
                            <button type="submit" name="pay_bill" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-transform hover:scale-105"><i class="fas fa-check-circle mr-2"></i>Konfirmasi Pembayaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // JavaScript identik dengan tambah_member.php
    const cashInput = document.getElementById('cash_paid');
    const fee = parseFloat(document.getElementById('subscription_fee').value);
    const changeDisplay = document.getElementById('change_due');
    const submitButton = document.querySelector('button[type="submit"]');

    function validatePayment() {
        const cash = parseFloat(cashInput.value) || 0;
        const change = cash - fee;
        if (cash >= fee) {
            changeDisplay.textContent = `Rp ${change.toLocaleString('id-ID')}`;
            changeDisplay.classList.remove('text-red-600'); changeDisplay.classList.add('text-blue-600');
            submitButton.disabled = false;
            submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed'); submitButton.classList.add('bg-green-600', 'hover:bg-green-700');
        } else {
            changeDisplay.textContent = `Kurang Rp ${Math.abs(change).toLocaleString('id-ID')}`;
            changeDisplay.classList.remove('text-blue-600'); changeDisplay.classList.add('text-red-600');
            submitButton.disabled = true;
            submitButton.classList.add('bg-gray-400', 'cursor-not-allowed'); submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
        }
    }
    cashInput.addEventListener('input', validatePayment);
    validatePayment();
});
</script>
</body>
</html>