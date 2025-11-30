<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$user_id = $_SESSION['user_id'];

// --- LOGIKA FORM SUBMISSION (TIDAK ADA PERUBAHAN, SUDAH BENAR) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $checkEmailQuery = "SELECT id FROM users WHERE email = '$email' AND id != '$user_id'";
        $emailResult = mysqli_query($conn, $checkEmailQuery);
        if (mysqli_num_rows($emailResult) > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Email sudah digunakan oleh akun lain.'];
        } else {
            $profile_photo = $_POST['current_photo'];
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $target_dir = "../uploads/profile/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $filename = "user_" . $user_id . "_" . time() . "." . pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION);
                $target_file = $target_dir . $filename;
                if (!empty($profile_photo) && file_exists($target_dir . $profile_photo)) { unlink($target_dir . $profile_photo); }
                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) { $profile_photo = $filename; }
            }
            $query = "UPDATE users SET name='$name', email='$email', profile_photo='$profile_photo' WHERE id='$user_id'";
            if (mysqli_query($conn, $query)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Profil berhasil diperbarui.'];
                $_SESSION['user_name'] = $name;
            } else { $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal memperbarui profil.']; }
        }
    }
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $user_query = mysqli_query($conn, "SELECT password FROM users WHERE id = '$user_id'");
        $user = mysqli_fetch_assoc($user_query);
        if ($current_password !== $user['password']) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Password saat ini salah.'];
        } elseif (strlen($new_password) < 6) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Password baru minimal 6 karakter.'];
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Konfirmasi password baru tidak cocok.'];
        } else {
            $update_pass_query = "UPDATE users SET password='$new_password' WHERE id='$user_id'";
            if (mysqli_query($conn, $update_pass_query)) { $_SESSION['message'] = ['type' => 'success', 'text' => 'Password berhasil diubah.']; } 
            else { $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal mengubah password.']; }
        }
    }
    header("Location: profile.php");
    exit();
}

// LOGIKA PENGAMBILAN GAMBAR
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($result_user);

$profile_picture_filename = $user_data['profile_photo'] ?? null;
// Gunakan UI Avatars sebagai default yang lebih baik
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';

if (!empty($profile_picture_filename) && file_exists('../uploads/profile/' . $profile_picture_filename)) {
    // Tambahkan timestamp unik di akhir URL untuk memaksa browser memuat ulang gambar
    $profile_picture_url = '../uploads/profile/' . $profile_picture_filename . '?v=' . time();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - ParkirKita</title>
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
        
        /* ======================================= */
        /* PERBAIKAN CSS DI SINI                   */
        /* ======================================= */
        .profile-picture {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            /* Menghapus mask dan menggantinya dengan border solid sederhana */
            border: 3px solid #FDBA74; /* Warna orange muda yang cocok */
        }
        .profile-picture:hover {
            transform: scale(1.05);
            border-color: var(--brand-orange); /* Warna border menjadi lebih gelap saat di-hover */
        }
        
        .dropdown-menu { transform-origin: top right; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="bg-slate-50">

<div class="flex h-screen bg-slate-50 overflow-hidden">
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100"><a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center transition-all duration-300 hover:scale-105"><i class="fas fa-parking text-[var(--brand-orange)] text-3xl"></i><span class="sidebar-logo-text ml-3 text-gray-700 transition-all duration-300">Parkir<span class="text-[var(--brand-pink)]">Kita</span></span></a></div>
            <nav class="mt-4 text-gray-600 font-medium flex-grow">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-tachometer-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Dashboard</span></a>
                <a href="kelola_petugas.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-users-cog fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Kelola Petugas</span></a>
                <a href="laporan.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-file-invoice-dollar fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Laporan</span></a>
            </nav>
            <div class="mt-auto p-4 border-t border-slate-100">
                <div id="user-info-sidebar" class="flex items-center transition-all duration-300"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover profile-picture"><div class="sidebar-text ml-3 transition-all duration-300"><p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div></div>
                <a href="../logout.php" class="sidebar-link flex items-center mt-3 py-2 px-2 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg"><i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Logout</span></a>
            </div>
        </div>
    </aside>
    
    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        <header id="main-header" class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm">
             <div class="flex items-center"><button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-slate-700">Profil Saya</h1></div>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group"><div class="relative"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture"><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div></div><div class="text-left hidden sm:block"><p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div><i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i></button>
                <div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0">
                    <div class="px-4 py-3 border-b border-slate-100"><div class="flex items-center space-x-3"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture"><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p><p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($user_data['email'] ?? 'user@example.com'); ?></p></div></div></div>
                    <div class="py-2"><a href="profile.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-all duration-200 group"><i class="fas fa-user-circle w-5 text-gray-400 group-hover:text-[var(--brand-orange)] transition-colors duration-200"></i><span class="ml-3 font-medium">Profil Saya</span><i class="fas fa-chevron-right text-xs text-gray-400 ml-auto group-hover:text-[var(--brand-orange)] transition-colors duration-200"></i></a><div class="border-t border-slate-100 my-2"></div><a href="../logout.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group"><i class="fas fa-sign-out-alt w-5 text-red-500 group-hover:text-red-700 transition-colors duration-200"></i><span class="ml-3 font-medium">Keluar</span><i class="fas fa-chevron-right text-xs text-red-400 ml-auto group-hover:text-red-700 transition-colors duration-200"></i></a></div>
                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 rounded-b-xl"><p class="text-xs text-gray-500 text-center">ParkirKita v1.0 • Super Admin</p></div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-8 bg-slate-50/50">
            <div class="max-w-4xl mx-auto">
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-container mb-6 p-4 rounded-lg text-white font-medium <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-500' : 'bg-red-500' ?> shadow-lg"><?= htmlspecialchars($_SESSION['message']['text']) ?><button class="float-right font-bold text-xl" onclick="this.parentElement.style.display='none'">&times;</button></div>
                <?php unset($_SESSION['message']); endif; ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1"><div class="bg-white p-6 rounded-lg shadow-md text-center"><img id="profile-preview" src="<?= $profile_picture_url ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 border-4 border-slate-200"><h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($user_data['name']) ?></h2><p class="text-slate-500"><?= htmlspecialchars($user_data['email']) ?></p><p class="mt-2 text-sm capitalize text-white font-semibold inline-block bg-orange-500 px-3 py-1 rounded-full"><?= str_replace('_', ' ', $user_data['role']) ?></p></div></div>
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-lg font-bold text-slate-700 border-b pb-3 mb-4">Informasi Akun</h3>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="current_photo" value="<?= htmlspecialchars($user_data['profile_photo'] ?? '') ?>">
                                <div class="space-y-4">
                                    <div><label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label><input type="text" name="name" id="name" value="<?= htmlspecialchars($user_data['name']) ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                                    <div><label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label><input type="email" name="email" id="email" value="<?= htmlspecialchars($user_data['email']) ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                                    <div><label class="block text-sm font-medium text-gray-700">Ganti Foto Profil</label><div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md"><div class="space-y-1 text-center"><svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg><div class="flex text-sm text-gray-600"><label for="profile_photo" class="relative cursor-pointer bg-white rounded-md font-medium text-orange-600 hover:text-orange-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-orange-500"><span>Unggah file</span><input id="profile_photo" name="profile_photo" type="file" class="sr-only" accept="image/*"></label><p class="pl-1">atau seret dan lepas</p></div><p class="text-xs text-gray-500" id="file-upload-filename">PNG, JPG, GIF hingga 2MB</p></div></div></div>
                                </div>
                                <div class="text-right mt-6"><button type="submit" name="update_profile" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg">Simpan Perubahan</button></div>
                            </form>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-lg font-bold text-slate-700 border-b pb-3 mb-4">Ubah Password</h3>
                            <form action="" method="POST">
                                <div class="space-y-4">
                                    <div><label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label><input type="password" name="current_password" id="current_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                                    <div><label for="new_password" class="block text-sm font-medium text-gray-700">Password Baru</label><input type="password" name="new_password" id="new_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                                    <div><label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label><input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                                </div>
                                <div class="text-right mt-6"><button type="submit" name="change_password" class="bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-6 rounded-lg">Ubah Password</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar & Dropdown ---
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); }
    if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); }
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down');
    if (userMenuButton && userMenu) { userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); }
    
    // --- Logika Khusus Halaman Profil (Drag & Drop, dsb) ---
    const profileInput = document.getElementById('profile_photo');
    const profilePreview = document.getElementById('profile-preview');
    const fileNameDisplay = document.getElementById('file-upload-filename');
    const dropZone = profileInput.closest('.border-dashed');
    function handleFile(file) { if (file && file.type.startsWith('image/')) { const reader = new FileReader(); reader.onload = function(e) { profilePreview.src = e.target.result; }; reader.readAsDataURL(file); fileNameDisplay.textContent = file.name; } else { fileNameDisplay.textContent = "Hanya file gambar yang diizinkan!"; profileInput.value = ""; } }
    if (profileInput) { profileInput.addEventListener('change', function(event) { handleFile(event.target.files[0]); }); }
    if (dropZone) { ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => { dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false); }); ['dragenter', 'dragover'].forEach(eventName => { dropZone.addEventListener(eventName, () => dropZone.classList.add('border-orange-500', 'bg-orange-50'), false); }); ['dragleave', 'drop'].forEach(eventName => { dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-orange-500', 'bg-orange-50'), false); }); dropZone.addEventListener('drop', e => { const dt = e.dataTransfer; const files = dt.files; if (files.length > 0) { profileInput.files = files; handleFile(files[0]); } }, false); }
    const alert = document.querySelector('.alert-container');
    if(alert) { setTimeout(() => { alert.style.transition = 'opacity 0.5s ease'; alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }, 5000); }
});
</script>
</body>
</html>