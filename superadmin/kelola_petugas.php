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
    <title>Kelola Petugas - Parkir Kita</title>
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

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background-color: #475569; }

        /* SIDEBAR */
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 3px solid transparent; }
        .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.08); color: #F57C00; border-left-color: #F57C00; }
        .sidebar-active { background-color: rgba(245, 124, 0, 0.12); color: #F57C00; font-weight: 700; border-left-color: #F57C00; }
        .dark .sidebar-link:hover { background-color: rgba(245, 124, 0, 0.1); }
        .dark .sidebar-active { background-color: rgba(245, 124, 0, 0.2); }

        /* TABLE */
        .custom-table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .custom-table tr:hover td { background-color: #f8fafc; }
        .dark .custom-table tr:hover td { background-color: #334155; }

        /* MODAL */
        .modal { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .modal.hidden .modal-content { transform: scale(0.95); opacity: 0; }
        .modal:not(.hidden) .modal-content { transform: scale(1); opacity: 1; }

        /* HEADER GLASS */
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
    <!-- Sidebar -->
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
                    <i class="fas fa-tachometer-alt fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Dashboard</span>
                </a>
                <a href="kelola_petugas.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'kelola_petugas.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-user-shield fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Kelola Petugas</span>
                </a>
                <a href="laporan.php" class="sidebar-link flex items-center py-3 px-4 rounded-xl <?= ($currentPage == 'laporan.php') ? 'sidebar-active' : 'text-slate-600 dark:text-slate-400 hover:text-brand-orange' ?>">
                    <i class="fas fa-file-invoice-dollar fa-fw text-lg mr-3"></i>
                    <span class="sidebar-text font-medium transition-opacity duration-300">Laporan Pusat</span>
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
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize">Super Admin</p>
                </div>
                <a href="../logout.php" class="text-slate-400 hover:text-red-500 transition-colors p-2 flex-shrink-0" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 relative">

        <!-- HEADER -->
        <header class="h-20 glass-header border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors">
                     <i class="fas fa-bars text-xl"></i>
                 </button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Manajemen Petugas</h1>
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

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Daftar Petugas</h2>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Kelola akses dan data petugas parkir.</p>
                    </div>
                    <button id="add-petugas-btn" class="bg-brand-orange hover:bg-orange-600 text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-orange-500/20 flex items-center gap-2 transition-all hover:-translate-y-1">
                        <i class="fas fa-plus"></i> Tambah Petugas
                    </button>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-50 border-green-500 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-50 border-red-500 text-red-800 dark:bg-red-900/30 dark:text-red-300' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['message']['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6 p-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <form action="" method="GET" class="relative w-full md:w-96">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-brand-orange" placeholder="Cari nama atau email..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                    <div class="text-sm font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-4 py-2 rounded-lg">
                        Total: <span class="text-brand-orange"><?= mysqli_num_rows($result_petugas) ?></span> Petugas
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left custom-table">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                                    <th class="p-5">Petugas</th>
                                    <th class="p-5">Tanggal Dibuat</th>
                                    <th class="p-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <?php if(mysqli_num_rows($result_petugas) > 0): ?>
                                <?php while($petugas = mysqli_fetch_assoc($result_petugas)): ?>
                                <tr class="transition-colors duration-200 group">
                                    <td class="p-5">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-slate-100 dark:border-slate-600">
                                                <?php
                                                $petugas_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($petugas['name']) . '&background=random';
                                                if (!empty($petugas['profile_photo']) && file_exists('../uploads/profile/' . $petugas['profile_photo'])) {
                                                    $petugas_photo_url = '../uploads/profile/' . $petugas['profile_photo'] . '?v=' . time();
                                                }
                                                ?>
                                                <img class="w-full h-full object-cover" src="<?= $petugas_photo_url ?>" alt="Foto profil">
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($petugas['name']) ?></p>
                                                <p class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($petugas['email']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-5 text-sm text-slate-600 dark:text-slate-300">
                                        <i class="far fa-calendar-alt mr-2 text-slate-400"></i>
                                        <?= date('d M Y', strtotime($petugas['created_at'])) ?>
                                    </td>
                                    <td class="p-5 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="editPetugas(<?= htmlspecialchars(json_encode($petugas)) ?>)" class="p-2 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-slate-700 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deletePetugas(<?= $petugas['id'] ?>, '<?= htmlspecialchars(addslashes($petugas['name'])) ?>')" class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-700 transition" title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="3" class="p-10 text-center text-slate-400 italic">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-700 rounded-full flex items-center justify-center text-slate-300">
                                                <i class="fas fa-user-slash text-2xl"></i>
                                            </div>
                                            <span>Belum ada data petugas.</span>
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

<!-- Modal -->
<div id="petugas-modal" class="modal fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="modal-overlay absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="modal-content bg-white dark:bg-slate-800 w-full max-w-md mx-4 rounded-2xl shadow-2xl z-50 overflow-hidden transform scale-95 transition-all duration-300">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-700/30">
            <h2 id="modal-title" class="text-xl font-bold text-slate-800 dark:text-white">Tambah Petugas</h2>
            <button id="close-modal-btn" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form id="petugas-form" action="" method="POST" enctype="multipart/form-data">
            <div class="p-6 space-y-4">
                <input type="hidden" name="id" id="petugas-id">
                <input type="hidden" name="current_photo" id="current-photo">

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-700 border-none focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required placeholder="Contoh: Budi Santoso">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Email</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-700 border-none focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" required placeholder="budi@example.com">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Password</label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-700 border-none focus:ring-2 focus:ring-brand-orange text-sm font-medium dark:text-white" placeholder="******">
                    <p id="password-help" class="text-xs text-slate-400 mt-1 hidden">Biarkan kosong jika tidak ingin mengubah password.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Foto Profil</label>
                    <input type="file" name="profile_photo" id="profile_photo" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-brand-orange hover:file:bg-orange-100 dark:file:bg-slate-700 dark:hover:file:bg-slate-600 transition">
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/30 text-right flex gap-3 justify-end">
                <button type="button" id="cancel-btn" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-600 transition">Batal</button>
                <button type="submit" name="save_petugas" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-brand-orange text-white hover:bg-orange-600 shadow-lg shadow-orange-500/20 transition">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<form id="delete-form" action="" method="POST" class="hidden"><input type="hidden" name="id" id="delete-id"><input type="hidden" name="delete_petugas" value="1"></form>

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

    // Modal Logic
    const modal = document.getElementById('petugas-modal');
    const form = document.getElementById('petugas-form');
    const modalTitle = document.getElementById('modal-title');
    const passwordHelp = document.getElementById('password-help');
    const overlay = modal.querySelector('.modal-overlay');

    function toggleModal(show) {
        if (show) {
            modal.classList.remove('hidden', 'pointer-events-none');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        } else {
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden', 'pointer-events-none'), 300);
        }
    }

    document.getElementById('add-petugas-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('petugas-id').value = '';
        modalTitle.innerText = 'Tambah Petugas Baru';
        passwordHelp.classList.add('hidden');
        document.getElementById('password').required = true;
        toggleModal(true);
    });

    [document.getElementById('close-modal-btn'), document.getElementById('cancel-btn'), overlay].forEach(el => {
        el.addEventListener('click', () => toggleModal(false));
    });

    window.editPetugas = (petugas) => {
        form.reset();
        modalTitle.innerText = 'Edit Data Petugas';
        document.getElementById('petugas-id').value = petugas.id;
        document.getElementById('name').value = petugas.name;
        document.getElementById('email').value = petugas.email;
        document.getElementById('current-photo').value = petugas.profile_photo;
        passwordHelp.classList.remove('hidden');
        document.getElementById('password').required = false;
        toggleModal(true);
    }

    window.deletePetugas = (id, name) => {
        if (confirm(`Apakah Anda yakin ingin menghapus petugas "${name}"?`)) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-form').submit();
        }
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
