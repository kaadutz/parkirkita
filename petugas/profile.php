<?php
session_start();
// Keamanan: Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$user_id = $_SESSION['user_id'];

// --- LOGIKA FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // AKSI: UPDATE PROFIL
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Cek email kembar
        $checkEmailQuery = "SELECT id FROM users WHERE email = '$email' AND id != '$user_id'";
        $emailResult = mysqli_query($conn, $checkEmailQuery);
        
        if (mysqli_num_rows($emailResult) > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Email sudah digunakan oleh akun lain.'];
        } else {
            $profile_photo = $_POST['current_photo'];
            // Upload Foto
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
    
    // AKSI: UBAH PASSWORD
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

// --- AMBIL DATA PROFILE USER ---
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; --primary-navy: #1C2E4A; } 
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; } 
        
        /* --- SIDEBAR & NAVBAR (TIDAK DIUBAH) --- */
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; } 
        .sidebar-link:hover { background-color: var(--brand-light-bg); color: var(--brand-orange); border-left-color: var(--brand-orange); transform: translateX(4px); } 
        .sidebar-active { background-color: var(--brand-light-bg); color: var(--brand-orange); font-weight: 700; border-left-color: var(--brand-orange); } 
        #sidebar, #main-content { transition: all 0.4s ease; } 
        .sidebar-text, .sidebar-logo-text { transition: opacity 0.3s ease; white-space: nowrap; } 
        body.sidebar-collapsed #sidebar { width: 5.5rem; } 
        body.sidebar-collapsed #main-content { margin-left: 5.5rem; } 
        body.sidebar-collapsed .sidebar-text, body.sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; pointer-events: none; } 
        body.sidebar-collapsed .sidebar-link, body.sidebar-collapsed #user-info-sidebar { justify-content: center; padding-left: 0; padding-right: 0; } 
        .profile-picture { border: 2px solid #FDBA74; transition: 0.3s; } 
        .dropdown-menu { transform-origin: top right; transition: 0.2s ease-out; }

        /* --- CSS PERBAIKAN LAYOUT PROFIL --- */
        .content-card { 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            border: 1px solid #f0f0f0; 
            overflow: hidden; 
        }
        
        .profile-header-bg { 
            background: linear-gradient(135deg, #1C2E4A 0%, #2c3e50 100%); 
            height: 140px; /* Tinggi banner */
            width: 100%;
        }
        
        /* Styling Foto Profil Besar */
        .profile-avatar-large {
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            border: 5px solid white; 
            object-fit: cover; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            background: white;
        }
        
        /* Input Modern */
        .input-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem; }
        .input-modern {
            width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; 
            border: 1px solid #e2e8f0; background-color: #f8fafc; 
            transition: all 0.2s; font-size: 0.9rem;
        }
        .input-modern:focus { background-color: white; border-color: var(--brand-orange); outline: none; box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.1); }
        
        /* Upload Zone */
        .upload-zone {
            border: 2px dashed #cbd5e1; border-radius: 0.75rem; padding: 1.5rem; text-align: center;
            cursor: pointer; transition: all 0.2s; background-color: #f8fafc;
        }
        .upload-zone:hover { border-color: var(--brand-orange); background-color: #fff7ed; }
        
        /* Buttons */
        .btn-save {
            background: linear-gradient(to right, #f97316, #ea580c); color: white; 
            padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; 
            font-size: 0.9rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3); }
        
        .btn-secondary {
            background-color: #334155; color: white; padding: 0.75rem 1.5rem; 
            border-radius: 0.5rem; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;
        }
        .btn-secondary:hover { background-color: #1e293b; }
    </style>
</head>
<body class="bg-slate-50">

<div class="flex h-screen bg-slate-50 overflow-hidden">
    
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100">
                <a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center hover:scale-105 transition-transform">
                    <i class="fas fa-parking text-[var(--brand-orange)] text-3xl"></i>
                    <span class="sidebar-logo-text ml-3 text-gray-700 transition-all duration-300">Parkir<span class="text-[var(--brand-pink)]">Kita</span></span>
                </a>
            </div>
            <nav class="mt-4 text-gray-600 font-medium flex-grow">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-tachometer-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Dashboard</span></a>
                <a href="transaksi_keluar.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-arrow-right-from-bracket fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Transaksi Keluar</span></a>
                <a href="kelola_member.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-id-card fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Kelola Member</span></a>
                <a href="laporan_petugas.php" class="sidebar-link flex items-center py-3 px-6"><i class="fas fa-file-invoice-dollar fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4">Laporan Saya</span></a>
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
        
        <header id="main-header" class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b border-slate-200 shadow-sm z-20">
             <div class="flex items-center"><button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button><h1 class="text-xl font-semibold text-slate-700">Profil Saya</h1></div>
            <div class="relative"><button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group"><div class="relative"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture"><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div></div><div class="text-left hidden sm:block"><p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div><i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i></button><div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0"><div class="px-4 py-3 border-b border-slate-100"><div class="flex items-center space-x-3"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture"><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p><p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($user_data['email'] ?? 'user@example.com'); ?></p></div></div></div><div class="py-2"><a href="profile.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-all duration-200 group"><i class="fas fa-user-circle w-5 text-gray-400 group-hover:text-[var(--brand-orange)] transition-colors duration-200"></i><span class="ml-3 font-medium">Profil Saya</span><i class="fas fa-chevron-right text-xs text-gray-400 ml-auto group-hover:text-[var(--brand-orange)] transition-colors duration-200"></i></a><div class="border-t border-slate-100 my-2"></div><a href="../logout.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group"><i class="fas fa-sign-out-alt w-5 text-red-500"></i><span class="ml-3 font-medium">Keluar</span><i class="fas fa-chevron-right text-xs ml-auto"></i></a></div></div></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="max-w-5xl mx-auto">
                
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-container mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['message']['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <div class="lg:col-span-4">
                        <div class="content-card h-full flex flex-col">
                            <div class="profile-header-bg"></div>
                            
                            <div class="px-6 pb-8 text-center relative flex-1">
                                <div class="relative -mt-16 mb-4 flex justify-center">
                                    <img id="profile-preview" src="<?= $profile_picture_url ?>" alt="Profile" class="profile-avatar-large">
                                </div>
                                
                                <div class="mt-2">
                                    <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($user_data['name']) ?></h2>
                                    <p class="text-slate-500 text-sm mt-1"><?= htmlspecialchars($user_data['email']) ?></p>
                                    
                                    <div class="mt-4 inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-orange-50 text-orange-600 border border-orange-100">
                                        <?= str_replace('_', ' ', $user_data['role']) ?>
                                    </div>
                                    
                                    <div class="mt-8 pt-6 border-t border-slate-100">
                                        <p class="text-xs text-slate-400 uppercase tracking-widest mb-2 font-semibold">Bergabung Sejak</p>
                                        <p class="text-sm font-medium text-slate-700 flex items-center justify-center gap-2">
                                            <i class="far fa-calendar-alt text-slate-400"></i>
                                            <?= date('d F Y', strtotime($user_data['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-8 space-y-8">
                        
                        <div class="content-card p-8">
                            <div class="flex items-center mb-6 border-b border-slate-100 pb-4">
                                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 mr-4 shadow-sm">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Informasi Pribadi</h3>
                                    <p class="text-xs text-slate-500">Perbarui data diri dan foto profil Anda.</p>
                                </div>
                            </div>
                            
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="current_photo" value="<?= htmlspecialchars($user_data['profile_photo'] ?? '') ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="input-group">
                                        <label for="name">Nama Lengkap</label>
                                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user_data['name']) ?>" class="input-modern" required>
                                    </div>
                                    <div class="input-group">
                                        <label for="email">Alamat Email</label>
                                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user_data['email']) ?>" class="input-modern" required>
                                    </div>
                                </div>
                                
                                <div class="input-group mb-6">
                                    <label>Ganti Foto Profil</label>
                                    <label for="profile_photo" class="upload-zone block group">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 group-hover:text-orange-400 transition-colors mb-2"></i>
                                            <span class="text-sm font-medium text-slate-600">Klik untuk upload foto baru</span>
                                            <span class="text-xs text-slate-400 mt-1" id="file-upload-filename">Format JPG, PNG (Max 2MB)</span>
                                        </div>
                                        <input id="profile_photo" name="profile_photo" type="file" class="hidden" accept="image/*">
                                    </label>
                                </div>

                                <div class="text-right">
                                    <button type="submit" name="update_profile" class="btn-save">
                                        <i class="fas fa-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="content-card p-8">
                            <div class="flex items-center mb-6 border-b border-slate-100 pb-4">
                                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center text-red-500 mr-4 shadow-sm">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Keamanan Akun</h3>
                                    <p class="text-xs text-slate-500">Ganti password secara berkala untuk keamanan.</p>
                                </div>
                            </div>

                            <form action="" method="POST">
                                <div class="input-group mb-6">
                                    <label for="current_password">Password Saat Ini</label>
                                    <input type="password" name="current_password" id="current_password" class="input-modern" required>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="input-group">
                                        <label for="new_password">Password Baru</label>
                                        <input type="password" name="new_password" id="new_password" class="input-modern" required>
                                    </div>
                                    <div class="input-group">
                                        <label for="confirm_password">Konfirmasi Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="input-modern" required>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" name="change_password" class="btn-secondary">
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
    // --- Sidebar & Dropdown (Tidak Diubah) ---
    const sidebarToggle = document.getElementById('sidebar-toggle'); if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); } if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); } const userMenuButton = document.getElementById('user-menu-button'); const userMenu = document.getElementById('user-menu'); const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down'); if (userMenuButton && userMenu) { userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); window.addEventListener('click', (e) => { if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } }); }
    
    // --- Profile File Handler ---
    const profileInput = document.getElementById('profile_photo');
    const profilePreview = document.getElementById('profile-preview');
    const fileNameDisplay = document.getElementById('file-upload-filename');
    
    profileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
                fileNameDisplay.textContent = file.name;
                fileNameDisplay.classList.add('text-green-600', 'font-bold');
            } else {
                fileNameDisplay.textContent = "File harus berupa gambar!";
                fileNameDisplay.classList.add('text-red-500');
                profileInput.value = ""; // Reset input
            }
        }
    });
    
    // Auto hide alert
    const alert = document.querySelector('.alert-container');
    if(alert) { setTimeout(() => { alert.style.transition = 'opacity 0.5s ease'; alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }, 5000); }
});
</script>
</body>
</html>a