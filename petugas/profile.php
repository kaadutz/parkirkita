<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$user_id = $_SESSION['user_id'];

// --- FORM SUBMISSION LOGIC ---
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
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal memperbarui profil.'];
            }
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
            if (mysqli_query($conn, $update_pass_query)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Password berhasil diubah.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal mengubah password.'];
            }
        }
    }
    header("Location: profile.php");
    exit();
}

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
    <meta charset="UTF-8"><title>Profil Saya - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background-color: #475569; }

        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 3px solid transparent; }
        .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.08); color: #F57C00; border-left-color: #F57C00; }
        .sidebar-active { background-color: rgba(245, 124, 0, 0.12); color: #F57C00; font-weight: 700; border-left-color: #F57C00; }
        .dark .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.1); }
        .dark .sidebar-active { background-color: rgba(245, 124, 0, 0.2); }

        .profile-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            height: 160px; width: 100%;
        }
        .profile-avatar {
            width: 140px; height: 140px; border-radius: 50%;
            border: 6px solid #fff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            object-fit: cover;
        }
        .dark .profile-avatar { border-color: #1e293b; }

        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
        }
        .dark .glass-header {
            background: rgba(15, 23, 42, 0.9);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="w-64 bg-white dark:bg-slate-800 shadow-xl hidden sm:flex flex-col z-20 transition-all duration-300 border-r border-slate-100 dark:border-slate-700 relative">
        <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
             <a href="dashboard.php" class="text-2xl font-extrabold tracking-tight flex items-center gap-2 overflow-hidden">
                 <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-orange to-pink-600 flex items-center justify-center text-white shadow-lg shadow-orange-500/30 flex-shrink-0">
                     <i class="fas fa-parking text-xl"></i>
                 </div>
                 <span id="logo-text" class="text-slate-800 dark:text-white transition-opacity duration-300">Parkir<span class="text-brand-orange">Kita</span></span>
             </a>
        </div>

        <div class="p-4 flex-1 overflow-y-auto custom-scrollbar">
            <div id="menu-label" class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 px-4 transition-opacity duration-300">Menu Utama</div>
            <nav class="space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'dashboard.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-home fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Dashboard</span>
                </a>
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'transaksi_keluar.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-sign-out-alt fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Transaksi Keluar</span>
                </a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'kelola_member.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-users fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Kelola Member</span>
                </a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'laporan_petugas.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-chart-pie fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Laporan Saya</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Collapse Button -->
        <button id="sidebar-collapse-btn" onclick="toggleSidebar()" class="absolute -right-3 top-24 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full p-1 shadow-md text-slate-500 dark:text-slate-300 hover:text-brand-orange transition-colors z-30 hidden sm:flex items-center justify-center w-6 h-6">
            <i class="fas fa-chevron-left text-xs" id="collapse-icon"></i>
        </button>

        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 flex items-center gap-3 overflow-hidden">
                <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-white dark:border-slate-600 shadow-sm flex-shrink-0">
                <div class="flex-1 min-w-0 sidebar-text transition-opacity duration-300">
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($user_data['name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize"><?= str_replace('_', ' ', $user_data['role']); ?></p>
                </div>
                <a href="../logout.php" class="text-slate-400 hover:text-red-500 transition-colors p-2 flex-shrink-0" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 relative">

        <header class="h-20 glass-header border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors">
                     <i class="fas fa-bars text-xl"></i>
                 </button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Profil Saya</h1>
             </div>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <div class="relative">
                    <button id="profile-menu-btn" onclick="toggleProfileMenu()" class="flex items-center gap-2 focus:outline-none">
                        <img src="<?= $profile_picture_url ?>" alt="User" class="w-10 h-10 rounded-full object-cover border-2 border-brand-orange shadow-sm">
                    </button>
                    <!-- Dropdown -->
                    <div id="profile-dropdown" class="absolute right-0 mt-3 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 hidden transition-all transform origin-top-right z-50">
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

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-8 bg-slate-50 dark:bg-slate-900">
            <div class="max-w-5xl mx-auto">

                <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-50 border-green-500 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-50 border-red-500 text-red-800 dark:bg-red-900/30 dark:text-red-300' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['message']['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- LEFT COLUMN: CARD PROFIL -->
                    <div class="lg:col-span-4">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden h-full flex flex-col">
                            <div class="profile-banner"></div>

                            <div class="px-6 pb-8 text-center relative flex-1">
                                <div class="relative -mt-16 mb-4 flex justify-center">
                                    <img id="profile-preview-large" src="<?= $profile_picture_url ?>" alt="Profile" class="profile-avatar bg-white dark:bg-slate-800">
                                </div>

                                <div class="mt-2">
                                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($user_data['name']) ?></h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1"><?= htmlspecialchars($user_data['email']) ?></p>

                                    <div class="mt-4 inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-orange-50 text-brand-orange border border-orange-100 dark:bg-slate-700 dark:border-slate-600 dark:text-orange-400">
                                        <?= str_replace('_', ' ', $user_data['role']) ?>
                                    </div>

                                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                                        <p class="text-xs text-slate-400 uppercase tracking-widest mb-2 font-bold">Bergabung Sejak</p>
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center justify-center gap-2">
                                            <i class="far fa-calendar-alt text-slate-400"></i>
                                            <?= date('d F Y', strtotime($user_data['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: FORMS -->
                    <div class="lg:col-span-8 space-y-8">

                        <!-- UPDATE INFO FORM -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-8">
                            <div class="flex items-center mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 mr-4">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Informasi Pribadi</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Perbarui data diri dan foto profil Anda.</p>
                                </div>
                            </div>

                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="current_photo" value="<?= htmlspecialchars($user_data['profile_photo'] ?? '') ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="name" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Nama Lengkap</label>
                                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user_data['name']) ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required>
                                    </div>
                                    <div>
                                        <label for="email" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Alamat Email</label>
                                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user_data['email']) ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Foto Profil</label>
                                    <label for="profile_photo" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 dark:bg-slate-700/50 hover:bg-orange-50 dark:hover:bg-slate-700 hover:border-brand-orange transition-all group">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 group-hover:text-brand-orange mb-2 transition-colors"></i>
                                            <p class="text-sm text-slate-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200"><span class="font-semibold">Klik untuk upload</span> atau drag and drop</p>
                                            <p class="text-xs text-slate-400 mt-1" id="file-name-display">PNG, JPG (Max. 2MB)</p>
                                        </div>
                                        <input id="profile_photo" name="profile_photo" type="file" class="hidden" accept="image/*" />
                                    </label>
                                </div>

                                <div class="text-right">
                                    <button type="submit" name="update_profile" class="bg-brand-orange hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-orange-500/20 transition-all hover:-translate-y-1">
                                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- CHANGE PASSWORD FORM -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-8">
                            <div class="flex items-center mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                <div class="w-10 h-10 bg-red-50 dark:bg-red-900/30 rounded-xl flex items-center justify-center text-red-500 dark:text-red-400 mr-4">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Keamanan Akun</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Ganti password secara berkala untuk keamanan.</p>
                                </div>
                            </div>

                            <form action="" method="POST">
                                <div class="mb-6">
                                    <label for="current_password" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Password Saat Ini</label>
                                    <input type="password" name="current_password" id="current_password" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="new_password" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Password Baru</label>
                                        <input type="password" name="new_password" id="new_password" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required>
                                    </div>
                                    <div>
                                        <label for="confirm_password" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Konfirmasi Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" name="change_password" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all hover:-translate-y-1">
                                        Update Password
                                    </button>
                                </div>
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
    // --- Mobile Sidebar Toggle ---
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

    // --- Desktop Sidebar Collapse ---
    const logoText = document.getElementById('logo-text');
    const menuLabel = document.getElementById('menu-label');
    const sidebarTexts = document.querySelectorAll('.sidebar-text');
    const collapseIcon = document.getElementById('collapse-icon');

    function toggleSidebar() {
        const isCollapsed = sidebar.classList.contains('w-20');
        if (isCollapsed) {
            // Expand
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            if(logoText) logoText.classList.remove('hidden');
            if(menuLabel) menuLabel.classList.remove('opacity-0', 'invisible');
            sidebarTexts.forEach(text => text.classList.remove('hidden'));
            if(collapseIcon) collapseIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
            localStorage.setItem('sidebar_collapsed', 'false');
        } else {
            // Collapse
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            if(logoText) logoText.classList.add('hidden');
            if(menuLabel) menuLabel.classList.add('opacity-0', 'invisible');
            sidebarTexts.forEach(text => text.classList.add('hidden'));
            if(collapseIcon) collapseIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            localStorage.setItem('sidebar_collapsed', 'true');
        }
    }

    // Init Sidebar State
    if(localStorage.getItem('sidebar_collapsed') === 'true') {
        sidebar.classList.remove('w-64');
        sidebar.classList.add('w-20');
        if(logoText) logoText.classList.add('hidden');
        if(menuLabel) menuLabel.classList.add('opacity-0', 'invisible');
        sidebarTexts.forEach(text => text.classList.add('hidden'));
        if(collapseIcon) collapseIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
    }

    // --- Profile Dropdown Toggle ---
    window.toggleProfileMenu = function() {
        const dropdown = document.getElementById('profile-dropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close Dropdown on Click Outside
    window.addEventListener('click', function(e) {
        const btn = document.getElementById('profile-menu-btn');
        const dropdown = document.getElementById('profile-dropdown');
        if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // File Upload Handler
    const profileInput = document.getElementById('profile_photo');
    const fileNameDisplay = document.getElementById('file-name-display');
    const profilePreview = document.getElementById('profile-preview-large');

    if (profileInput) {
        profileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.type.startsWith('image/')) {
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.className = "text-xs text-green-600 font-bold mt-1";

                    // Preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                } else {
                    fileNameDisplay.textContent = "Error: File harus berupa gambar.";
                    fileNameDisplay.className = "text-xs text-red-500 font-bold mt-1";
                    profileInput.value = "";
                }
            }
        });
    }

    // Theme Logic
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
        });
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
});
</script>
</body>
</html>
