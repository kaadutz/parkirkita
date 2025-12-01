<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

$user_id_petugas = $_SESSION['user_id'];
$bulan_ini_awal = date('Y-m-01');
$biaya_member_bulanan = 150000;

// ====================================================================
// LOGIKA OTOMATIS: TAGIHAN BARU & SUSPEND MEMBER (SAMA)
// ====================================================================
$query_members = "SELECT id, status FROM members";
$result_members = mysqli_query($conn, $query_members);

while ($member = mysqli_fetch_assoc($result_members)) {
    $member_id = $member['id'];
    $q_cek = "SELECT id, status FROM member_billings WHERE member_id = '$member_id' AND billing_period = '$bulan_ini_awal'";
    $r_cek = mysqli_query($conn, $q_cek);
    $billing = mysqli_fetch_assoc($r_cek);

    if (!$billing) {
        $q_ins = "INSERT INTO member_billings (member_id, billing_period, amount, status) VALUES ('$member_id', '$bulan_ini_awal', '$biaya_member_bulanan', 'belum_lunas')";
        mysqli_query($conn, $q_ins);
        mysqli_query($conn, "UPDATE members SET status = 'tidak_aktif' WHERE id = '$member_id'");
    } else {
        if ($billing['status'] == 'belum_lunas' && $member['status'] == 'aktif') {
            mysqli_query($conn, "UPDATE members SET status = 'tidak_aktif' WHERE id = '$member_id'");
        } elseif ($billing['status'] == 'lunas' && $member['status'] == 'tidak_aktif') {
            mysqli_query($conn, "UPDATE members SET status = 'aktif' WHERE id = '$member_id'");
        }
    }
}

// --- PENCARIAN & DATA ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query_daftar_member = "
    SELECT m.id, m.name, m.status as member_status,
           mb.id as billing_id, mb.status as billing_status
    FROM members m
    LEFT JOIN member_billings mb ON m.id = mb.member_id AND mb.billing_period = '$bulan_ini_awal'
    " . (!empty($search) ? " WHERE (m.name LIKE '%$search%')" : "") . "
    ORDER BY m.name ASC";
$result_daftar_member = mysqli_query($conn, $query_daftar_member);

// --- DATA PROFIL HEADER ---
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id_petugas'");
$user_data = mysqli_fetch_assoc($q_user);
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
    <meta charset="UTF-8"><title>Kelola Member - ParkirKita</title>
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

        /* CONTENT DESIGN */
        .content-card {
            background: white; border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }
        .dark .content-card { background: #1e293b; border-color: #334155; }

        /* SEARCH */
        .search-input {
            transition: all 0.3s ease;
        }
        .search-input:focus {
            box-shadow: 0 0 0 4px rgba(245, 124, 0, 0.1);
        }

        /* TABLE */
        .custom-table th {
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;
        }
        .custom-table tr:hover td { background-color: #f8fafc; }
        .dark .custom-table tr:hover td { background-color: #334155; }

        /* BUTTONS */
        .btn-modern {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(245, 124, 0, 0.2);
        }

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
                    <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                </div>
                <a href="../logout.php" class="text-slate-400 hover:text-red-500 transition-colors p-2 flex-shrink-0" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 relative">

        <!-- HEADER -->
        <header class="h-20 glass-header border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-6 z-10 sticky top-0">
             <div class="flex items-center gap-4">
                 <button id="sidebar-toggle" class="sm:hidden text-slate-500 hover:text-brand-orange transition-colors">
                     <i class="fas fa-bars text-xl"></i>
                 </button>
                 <h1 class="text-xl font-bold text-slate-800 dark:text-white hidden md:block">Manajemen Member</h1>
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
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Daftar Anggota</h2>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Kelola data member dan status pembayaran langganan.</p>
                    </div>
                    <a href="tambah_member.php" class="btn-modern bg-brand-orange text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-orange-500/20 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Tambah Member
                    </a>
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

                <div class="content-card mb-6 p-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <form action="" method="GET" class="relative w-full md:w-96">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" class="search-input w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-brand-orange" placeholder="Cari nama member..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                    <div class="text-sm font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-4 py-2 rounded-lg">
                        Total: <span class="text-brand-orange"><?= mysqli_num_rows($result_daftar_member) ?></span> Member
                    </div>
                </div>

                <div class="content-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left custom-table">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                                    <th class="p-5">Nama Member</th>
                                    <th class="p-5">Status Keanggotaan</th>
                                    <th class="p-5">Tagihan Bulan Ini</th>
                                    <th class="p-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <?php if(mysqli_num_rows($result_daftar_member) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result_daftar_member)): ?>
                                    <tr class="transition-colors duration-200 group">
                                        <td class="p-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 font-bold text-lg">
                                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                                </div>
                                                <span class="font-bold text-slate-700 dark:text-white"><?= htmlspecialchars($row['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="p-5">
                                            <?php if($row['member_status'] == 'aktif'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2"></span> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-2"></span> Tidak Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-5">
                                            <?php if ($row['billing_status'] == 'lunas'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                    <i class="fas fa-check mr-1.5"></i> Lunas
                                                </span>
                                            <?php elseif ($row['billing_status'] == 'belum_lunas'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                                    <i class="fas fa-clock mr-1.5"></i> Belum Bayar
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-xs italic">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-5 text-right">
                                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <?php if ($row['billing_status'] == 'belum_lunas'): ?>
                                                    <a href="bayar_member.php?billing_id=<?= $row['billing_id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all hover:-translate-y-0.5">
                                                        <i class="fas fa-wallet mr-1"></i> Bayar
                                                    </a>
                                                <?php endif; ?>

                                                <a href="detail_member.php?id=<?= $row['id'] ?>" class="p-2 rounded-lg text-slate-400 hover:text-brand-orange hover:bg-orange-50 dark:hover:bg-slate-700 transition" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_member.php?id=<?= $row['id'] ?>" class="p-2 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-slate-700 transition" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button onclick="deleteMember(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-700 transition" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-400 italic">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-300">
                                                    <i class="fas fa-user-slash text-2xl"></i>
                                                </div>
                                                <span>Belum ada data member yang ditemukan.</span>
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

<form id="delete-form" action="proses_kelola_member.php" method="POST" class="hidden">
    <input type="hidden" name="id" id="delete-id">
    <input type="hidden" name="delete_member" value="1">
</form>

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

    // Delete Logic
    window.deleteMember = (id, name) => {
        if (confirm(`Apakah Anda yakin ingin menghapus member "${name}"?`)) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-form').submit();
        }
    };

    // Dark Mode (Standardized)
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    if(localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
    if(themeToggle) {
        themeToggle.addEventListener('click', () => {
            if(html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }
});
</script>
</body>
</html>
