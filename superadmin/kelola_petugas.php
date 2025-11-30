<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// --- LOGIKA AKSI (TAMBAH, EDIT, HAPUS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // AKSI: TAMBAH ATAU EDIT PETUGAS
    if (isset($_POST['save_petugas'])) {
        $id = $_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        $checkEmailQuery = "SELECT id FROM users WHERE email = '$email'" . (!empty($id) ? " AND id != '$id'" : "");
        $emailResult = mysqli_query($conn, $checkEmailQuery);
        if (mysqli_num_rows($emailResult) > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Email sudah terdaftar.'];
        } else {
            $profile_photo = $_POST['current_photo'];
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $target_dir = "../uploads/profile/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $extension = pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION);
                $new_id = empty($id) ? mysqli_insert_id($conn) : $id; // Perlu id untuk nama file
                $filename = "user_" . $new_id . "_" . time() . "." . $extension;
                $target_file = $target_dir . $filename;
                
                if (!empty($profile_photo) && file_exists($target_dir . $profile_photo)) { unlink($target_dir . $profile_photo); }
                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) { $profile_photo = $filename; }
            }

            if (empty($id)) {
                $query = "INSERT INTO users (name, email, password, role, profile_photo) VALUES ('$name', '$email', '$password', 'petugas', '$profile_photo')";
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Petugas baru berhasil ditambahkan.'];
            } else {
                if (!empty($password)) {
                    $query = "UPDATE users SET name='$name', email='$email', password='$password', profile_photo='$profile_photo' WHERE id='$id'";
                } else {
                    $query = "UPDATE users SET name='$name', email='$email', profile_photo='$profile_photo' WHERE id='$id'";
                }
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Data petugas berhasil diperbarui.'];
            }
            mysqli_query($conn, $query);
        }
    }
    // AKSI: HAPUS PETUGAS
    if (isset($_POST['delete_petugas'])) {
        $id = $_POST['id'];
        $pic_query = mysqli_query($conn, "SELECT profile_photo FROM users WHERE id='$id'");
        $pic_data = mysqli_fetch_assoc($pic_query);
        if ($pic_data && !empty($pic_data['profile_photo'])) {
            $file_path = "../uploads/profile/" . $pic_data['profile_photo'];
            if (file_exists($file_path)) { unlink($file_path); }
        }
        mysqli_query($conn, "DELETE FROM users WHERE id='$id' AND role='petugas'");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Petugas berhasil dihapus.'];
    }
    header("Location: kelola_petugas.php");
    exit();
}

// --- AMBIL DATA UNTUK DITAMPILKAN ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query_petugas = "SELECT * FROM users WHERE role = 'petugas'";
if (!empty($search)) { $query_petugas .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')"; }
$query_petugas .= " ORDER BY name ASC";
$result_petugas = mysqli_query($conn, $query_petugas);

// --- AMBIL DATA PROFILE USER YANG LOGIN (UNTUK HEADER & SIDEBAR) ---
$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($result_user);

// Gunakan logika cerdas untuk URL gambar
$profile_picture_filename = $user_data['profile_photo'] ?? null;
$profile_picture_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff&size=128';

if (!empty($profile_picture_filename) && file_exists('../uploads/profile/' . $profile_picture_filename)) {
    // Tambahkan timestamp untuk mencegah masalah cache browser
    $profile_picture_url = '../uploads/profile/' . $profile_picture_filename . '?v=' . time();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Petugas - Parkir Kita</title>
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
        
        /* CSS UNTUK FOTO PROFIL (SUDAH DIPERBAIKI) */
        .profile-picture {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid #FDBA74; /* Warna orange muda */
        }
        .profile-picture:hover {
            transform: scale(1.05);
            border-color: var(--brand-orange);
        }

        .dropdown-menu { transform-origin: top right; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .dropdown-item { transition: all 0.2s ease; }
        .dropdown-item:hover { transform: translateX(4px); }
        .modal { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s ease; }
    </style>
</head>
<body class="bg-slate-50">

<div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100">
                 <a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center transition-all duration-300 hover:scale-105">
                     <i class="fas fa-parking text-[var(--brand-orange)] text-3xl"></i>
                     <span class="sidebar-logo-text ml-3 text-gray-700 transition-all duration-300">Parkir<span class="text-[var(--brand-pink)]">Kita</span></span>
                 </a>
            </div>
            <nav class="mt-4 text-gray-600 font-medium flex-grow">
                <a href="dashboard.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'dashboard.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-tachometer-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4 transition-all duration-300">Dashboard</span></a>
                <a href="kelola_petugas.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'kelola_petugas.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-users-cog fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4 transition-all duration-300">Kelola Petugas</span></a>
                <a href="laporan.php" class="sidebar-link flex items-center py-3 px-6 <?= ($currentPage == 'laporan.php') ? 'sidebar-active' : '' ?>"><i class="fas fa-file-invoice-dollar fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4 transition-all duration-300">Laporan</span></a>
            </nav>
            <div class="mt-auto p-4 border-t border-slate-100">
                <div id="user-info-sidebar" class="flex items-center transition-all duration-300">
                    <img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover profile-picture">
                    <div class="sidebar-text ml-3 transition-all duration-300"><p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div>
                </div>
                <a href="../logout.php" class="sidebar-link flex items-center mt-3 py-2 px-2 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg"><i class="fas fa-sign-out-alt fa-fw text-xl w-8 text-center"></i><span class="sidebar-text ml-4 transition-all duration-300">Logout</span></a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <div id="main-content" class="flex-1 flex flex-col overflow-hidden transition-all duration-400">
        <!-- Header / Navbar Atas -->
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b-2 border-slate-200 shadow-sm">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 transition-all duration-300 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button>
                 <h1 class="text-xl font-semibold text-slate-700">Manajemen Petugas</h1>
             </div>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all duration-300 group">
                    <div class="relative"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture"><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div></div>
                    <div class="text-left hidden sm:block"><p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p></div>
                    <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i>
                </button>
                <div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0">
                    <div class="px-4 py-3 border-b border-slate-100"><div class="flex items-center space-x-3"><img src="<?= $profile_picture_url ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture"><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p><p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p><p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($user_data['email'] ?? 'user@example.com'); ?></p></div></div></div>
                    <div class="py-2">
                        <a href="profile.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-all duration-200 group"><i class="fas fa-user-circle w-5 text-gray-400 group-hover:text-[var(--brand-orange)] transition-colors duration-200"></i><span class="ml-3 font-medium">Profil Saya</span><i class="fas fa-chevron-right text-xs text-gray-400 ml-auto group-hover:text-[var(--brand-orange)] transition-colors duration-200"></i></a>
                        <div class="border-t border-slate-100 my-2"></div>
                        <a href="../logout.php" class="dropdown-item flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group"><i class="fas fa-sign-out-alt w-5 text-red-500 group-hover:text-red-700 transition-colors duration-200"></i><span class="ml-3 font-medium">Keluar</span><i class="fas fa-chevron-right text-xs text-red-400 ml-auto group-hover:text-red-700 transition-colors duration-200"></i></a>
                    </div>
                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 rounded-b-xl"><p class="text-xs text-gray-500 text-center">ParkirKita v1.0 • Super Admin</p></div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-8 bg-slate-50/50">
            <div class="container mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Daftar Petugas</h1>
                        <p class="text-slate-500 mt-1">Tambah, edit, atau hapus data petugas parkir.</p>
                    </div>
                    <button id="add-petugas-btn" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg flex items-center shadow-md hover:shadow-lg transition-all duration-300"><i class="fas fa-plus mr-2"></i> Tambah Petugas</button>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-container mb-4 p-4 rounded-lg text-white font-medium <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-500' : 'bg-red-500' ?> shadow-lg"><?= htmlspecialchars($_SESSION['message']['text']) ?><button class="float-right font-bold text-xl" onclick="this.parentElement.style.display='none'">&times;</button></div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="mb-4"><form action="" method="GET"><div class="relative"><input type="text" name="search" placeholder="Cari nama atau email petugas..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500" value="<?= htmlspecialchars($search) ?>"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i></div></form></div>

                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full leading-normal">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-slate-200 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Petugas</th>
                                <th class="px-5 py-3 border-b-2 border-slate-200 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tgl Dibuat</th>
                                <th class="px-5 py-3 border-b-2 border-slate-200 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result_petugas) > 0): ?>
                            <?php while($petugas = mysqli_fetch_assoc($result_petugas)): ?>
                            <tr>
                                <td class="px-5 py-4 border-b border-slate-200 bg-white text-sm">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10">
                                            <?php 
                                            $petugas_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($petugas['name']) . '&background=random';
                                            if (!empty($petugas['profile_photo']) && file_exists('../uploads/profile/' . $petugas['profile_photo'])) {
                                                $petugas_photo_url = '../uploads/profile/' . $petugas['profile_photo'] . '?v=' . time();
                                            }
                                            ?>
                                            <img class="w-full h-full rounded-full object-cover" src="<?= $petugas_photo_url ?>" alt="Foto profil">
                                        </div>
                                        <div class="ml-3"><p class="text-slate-900 font-semibold whitespace-no-wrap"><?= htmlspecialchars($petugas['name']) ?></p><p class="text-slate-600 whitespace-no-wrap"><?= htmlspecialchars($petugas['email']) ?></p></div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 border-b border-slate-200 bg-white text-sm"><p class="text-slate-900 whitespace-no-wrap"><?= date('d M Y', strtotime($petugas['created_at'])) ?></p></td>
                                <td class="px-5 py-4 border-b border-slate-200 bg-white text-sm text-right">
                                    <button onclick="editPetugas(<?= htmlspecialchars(json_encode($petugas)) ?>)" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button onclick="deletePetugas(<?= $petugas['id'] ?>, '<?= htmlspecialchars(addslashes($petugas['name'])) ?>')" class="text-red-600 hover:text-red-900" title="Hapus"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr><td colspan="3" class="text-center py-10 text-slate-500"><?= empty($search) ? "Belum ada data petugas." : "Tidak ada petugas yang cocok dengan pencarian." ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal -->
<div id="petugas-modal" class="modal fixed w-full h-full top-0 left-0 flex items-center justify-center hidden opacity-0 z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    <div class="modal-content bg-white w-11/12 md:max-w-md mx-auto rounded-lg shadow-lg z-50 overflow-y-auto transform -translate-y-10">
        <div class="modal-header p-5 border-b flex justify-between items-center"><h2 id="modal-title" class="text-2xl font-bold">Tambah Petugas</h2><button id="close-modal-btn" class="text-2xl font-light">&times;</button></div>
        <form id="petugas-form" action="" method="POST" enctype="multipart/form-data">
            <div class="modal-body p-5 space-y-4">
                <input type="hidden" name="id" id="petugas-id"><input type="hidden" name="current_photo" id="current-photo">
                <div><label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label><input type="text" name="name" id="name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                <div><label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label><input type="email" name="email" id="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500" required></div>
                <div><label for="password" class="block text-sm font-medium text-gray-700">Password</label><input type="password" name="password" id="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500"><p id="password-help" class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah.</p></div>
                <div><label for="profile_photo" class="block text-sm font-medium text-gray-700">Foto Profil</label><input type="file" name="profile_photo" id="profile_photo" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100"></div>
            </div>
            <div class="modal-footer p-5 border-t bg-gray-50 text-right"><button type="button" id="cancel-btn" class="bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg mr-2">Batal</button><button type="submit" name="save_petugas" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg">Simpan</button></div>
        </form>
    </div>
</div>

<form id="delete-form" action="" method="POST" style="display: none;"><input type="hidden" name="id" id="delete-id"><input type="hidden" name="delete_petugas" value="1"></form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar & Dropdown (Sama seperti dashboard.php) ---
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); });
    }
    if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); }
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down');
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); const isHidden = userMenu.classList.contains('hidden'); if (isHidden) { userMenu.classList.remove('hidden'); setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(180deg)'; } }, 10); } else { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } });
        window.addEventListener('click', (e) => { if (userMenuButton && userMenu && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if (userMenuIcon) { userMenuIcon.style.transform = 'rotate(0deg)'; } }, 200); } });
    }
    
    // --- Logika Khusus Halaman Ini (Modal, Edit, Hapus) ---
    const modal = document.getElementById('petugas-modal');
    const form = document.getElementById('petugas-form');
    const modalTitle = document.getElementById('modal-title');
    const passwordHelp = document.getElementById('password-help');
    function toggleModal(show) { if (show) { modal.classList.remove('hidden'); setTimeout(() => { modal.classList.remove('opacity-0'); modal.querySelector('.modal-content').classList.remove('-translate-y-10'); }, 10); } else { modal.classList.add('opacity-0'); modal.querySelector('.modal-content').classList.add('-translate-y-10'); setTimeout(() => modal.classList.add('hidden'), 300); } }
    document.getElementById('add-petugas-btn').addEventListener('click', function() { form.reset(); document.getElementById('petugas-id').value = ''; modalTitle.innerText = 'Tambah Petugas Baru'; passwordHelp.classList.add('hidden'); document.getElementById('password').required = true; toggleModal(true); });
    document.getElementById('close-modal-btn').addEventListener('click', () => toggleModal(false));
    document.getElementById('cancel-btn').addEventListener('click', () => toggleModal(false));
    modal.querySelector('.modal-overlay').addEventListener('click', () => toggleModal(false));
    window.editPetugas = function(petugas) { form.reset(); modalTitle.innerText = 'Edit Data Petugas'; document.getElementById('petugas-id').value = petugas.id; document.getElementById('name').value = petugas.name; document.getElementById('email').value = petugas.email; document.getElementById('current-photo').value = petugas.profile_photo; passwordHelp.classList.remove('hidden'); document.getElementById('password').required = false; toggleModal(true); }
    window.deletePetugas = function(id, name) { if (confirm(`Apakah Anda yakin ingin menghapus petugas bernama "${name}"?`)) { document.getElementById('delete-id').value = id; document.getElementById('delete-form').submit(); } }
    const alert = document.querySelector('.alert-container');
    if(alert) { setTimeout(() => { alert.style.transition = 'opacity 0.5s ease'; alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }, 5000); }
});
</script>

</body>
</html>