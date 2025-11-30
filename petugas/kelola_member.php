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
// LOGIKA OTOMATIS: TAGIHAN BARU & SUSPEND MEMBER
// ====================================================================
$query_members = "SELECT id, status FROM members";
$result_members = mysqli_query($conn, $query_members);

while ($member = mysqli_fetch_assoc($result_members)) {
    $member_id = $member['id'];
    
    // 1. Cek apakah tagihan bulan ini sudah ada
    $q_cek = "SELECT id, status FROM member_billings WHERE member_id = '$member_id' AND billing_period = '$bulan_ini_awal'";
    $r_cek = mysqli_query($conn, $q_cek);
    $billing = mysqli_fetch_assoc($r_cek);

    // 2. Jika belum ada tagihan (Bulan Baru)
    if (!$billing) {
        // Buat Tagihan 'belum_lunas'
        $q_ins = "INSERT INTO member_billings (member_id, billing_period, amount, status) VALUES ('$member_id', '$bulan_ini_awal', '$biaya_member_bulanan', 'belum_lunas')";
        mysqli_query($conn, $q_ins);
        
        // OTOMATIS NON-AKTIFKAN MEMBER
        $q_suspend = "UPDATE members SET status = 'tidak_aktif' WHERE id = '$member_id'";
        mysqli_query($conn, $q_suspend);
    } 
    // 3. Sinkronisasi Status (Safety Check)
    else {
        // Jika tagihan bulan ini BELUM LUNAS tapi member masih AKTIF -> SUSPEND
        if ($billing['status'] == 'belum_lunas' && $member['status'] == 'aktif') {
            mysqli_query($conn, "UPDATE members SET status = 'tidak_aktif' WHERE id = '$member_id'");
        }
        // Jika tagihan bulan ini SUDAH LUNAS tapi member masih TIDAK AKTIF -> AKTIFKAN
        elseif ($billing['status'] == 'lunas' && $member['status'] == 'tidak_aktif') {
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
$profile_pic = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=F57C00&color=fff';
if (!empty($user_data['profile_photo']) && file_exists('../uploads/profile/' . $user_data['profile_photo'])) {
    $profile_pic = '../uploads/profile/' . $user_data['profile_photo'] . '?v=' . time();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Kelola Member - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --brand-orange: #F57C00; --brand-pink: #D81B60; --brand-light-bg: #FFF8F2; --primary-navy: #1C2E4A; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
        
        /* --- SIDEBAR STYLES (TIDAK DIUBAH) --- */
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

        /* --- CONTENT DESIGN --- */
        .content-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; }
        
        /* Search Bar Modern */
        .search-box { position: relative; width: 100%; max-width: 400px; }
        .search-input {
            width: 100%; padding: 12px 20px 12px 45px;
            border-radius: 12px; border: 1px solid #e5e7eb;
            background-color: #fff; font-size: 14px; transition: 0.3s;
        }
        .search-input:focus { border-color: var(--brand-orange); box-shadow: 0 0 0 4px rgba(245, 124, 0, 0.1); outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        /* Table Modern */
        .custom-table th { background-color: #f9fafb; color: #4b5563; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; padding: 16px 24px; }
        .custom-table td { padding: 16px 24px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 14px; vertical-align: middle; }
        .custom-table tr:hover td { background-color: #f9fafb; }
        .custom-table tr:last-child td { border-bottom: none; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; px: 3; py: 1; border-radius: 9999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-neutral { background-color: #f3f4f6; color: #4b5563; }

        /* Action Buttons */
        .btn-icon {
            width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; transition: 0.2s; color: #6b7280;
        }
        .btn-icon:hover { background-color: #f3f4f6; color: var(--brand-orange); }
        .btn-icon.delete:hover { background-color: #fee2e2; color: #ef4444; }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #F57C00 0%, #EF6C00 100%);
            color: white; padding: 10px 20px; border-radius: 10px;
            font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 10px rgba(245, 124, 0, 0.2); transition: 0.3s;
        }
        .btn-primary-modern:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(245, 124, 0, 0.3); }

        .btn-pay {
            background-color: #3b82f6; color: white; font-size: 11px; font-weight: 700;
            padding: 6px 12px; border-radius: 6px; text-transform: uppercase; transition: 0.2s;
        }
        .btn-pay:hover { background-color: #2563eb; box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body>

<div class="flex h-screen bg-slate-50 overflow-hidden">
    
    <aside id="sidebar" class="w-64 bg-white shadow-2xl hidden sm:block flex-shrink-0 z-10">
        <div class="flex flex-col h-full">
            <div class="h-20 flex items-center justify-center flex-shrink-0 border-b border-slate-100">
                <a href="dashboard.php" class="text-2xl font-bold tracking-wider flex items-center hover:scale-105 transition-transform">
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
                    <img src="<?= $profile_pic ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover profile-picture">
                    <div class="sidebar-text ml-3">
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
        
        <header class="flex-shrink-0 flex justify-between items-center p-4 bg-white border-b border-slate-200 shadow-sm z-20">
             <div class="flex items-center">
                 <button id="sidebar-toggle" class="text-gray-600 hover:text-[var(--brand-orange)] focus:outline-none mr-4 p-2 rounded-lg hover:bg-orange-50"><i class="fas fa-bars fa-lg"></i></button>
                 <h1 class="text-xl font-semibold text-slate-700">Manajemen Member</h1>
             </div>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 bg-slate-50 hover:bg-slate-100 px-4 py-2 rounded-xl transition-all duration-200 group">
                    <div class="relative">
                        <img src="<?= $profile_pic ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow-sm profile-picture">
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-300 group-hover:text-[var(--brand-orange)]"></i>
                </button>
                <div id="user-menu" class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 z-20 hidden border border-slate-200 dropdown-menu scale-95 opacity-0">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <div class="flex items-center space-x-3">
                            <img src="<?= $profile_pic ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover profile-picture">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user_name']); ?></p>
                                <p class="text-xs text-gray-500 capitalize truncate"><?= str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="py-2">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-[var(--brand-orange)] transition-colors"><i class="fas fa-user-circle mr-2"></i> Profil Saya</a>
                        <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</a>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
            <div class="container mx-auto max-w-7xl">
                
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Daftar Anggota</h1>
                        <p class="text-sm text-slate-500 mt-1">Kelola data member dan status pembayaran.</p>
                    </div>
                    <a href="tambah_member.php" class="btn-primary-modern">
                        <i class="fas fa-plus"></i> Tambah Member
                    </a>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm border-l-4 <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $_SESSION['message']['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
                </div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="content-card mb-6 p-5 flex justify-between items-center">
                    <form action="" method="GET" class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Cari nama member..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                    <div class="text-sm text-slate-400 hidden md:block">
                        Total Member: <?= mysqli_num_rows($result_daftar_member) ?>
                    </div>
                </div>

                <div class="content-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left custom-table">
                            <thead>
                                <tr>
                                    <th>Nama Member</th>
                                    <th>Status Keanggotaan</th>
                                    <th>Tagihan Bulan Ini</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if(mysqli_num_rows($result_daftar_member) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result_daftar_member)): ?>
                                    <tr class="transition-colors duration-200">
                                        <td class="font-semibold text-slate-700">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </td>
                                        <td>
                                            <?php if($row['member_status'] == 'aktif'): ?>
                                                <span class="badge badge-success px-3 py-1 rounded-full">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger px-3 py-1 rounded-full">Tidak Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['billing_status'] == 'lunas'): ?>
                                                <span class="badge badge-success px-3 py-1 rounded-full">Lunas</span>
                                            <?php elseif ($row['billing_status'] == 'belum_lunas'): ?>
                                                <span class="badge badge-danger px-3 py-1 rounded-full">Belum Bayar</span>
                                            <?php else: ?>
                                                <span class="badge badge-neutral px-3 py-1 rounded-full">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right flex items-center justify-end gap-2">
                                            <?php if ($row['billing_status'] == 'belum_lunas'): ?>
                                                <a href="bayar_member.php?billing_id=<?= $row['billing_id'] ?>" class="btn-pay">
                                                    <i class="fas fa-wallet mr-1"></i> Bayar
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="detail_member.php?id=<?= $row['id'] ?>" class="btn-icon" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_member.php?id=<?= $row['id'] ?>" class="btn-icon" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button onclick="deleteMember(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" class="btn-icon delete" title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-400 italic">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-user-slash text-4xl mb-3 opacity-30"></i>
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
    // Sidebar Logic (Sama seperti sebelumnya)
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) { sidebarToggle.addEventListener('click', () => { document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed')); }); }
    if (localStorage.getItem('sidebarCollapsed') === 'true') { document.body.classList.add('sidebar-collapsed'); }
    
    // Dropdown Logic
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    const userMenuIcon = userMenuButton?.querySelector('i.fa-chevron-down');
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', (e) => { e.stopPropagation(); 
            const isHidden = userMenu.classList.contains('hidden');
            if (isHidden) {
                userMenu.classList.remove('hidden');
                setTimeout(() => { userMenu.classList.remove('scale-95', 'opacity-0'); userMenu.classList.add('scale-100', 'opacity-100'); if(userMenuIcon) userMenuIcon.style.transform = 'rotate(180deg)'; }, 10);
            } else {
                userMenu.classList.add('scale-95', 'opacity-0');
                setTimeout(() => { userMenu.classList.add('hidden'); if(userMenuIcon) userMenuIcon.style.transform = 'rotate(0deg)'; }, 200);
            }
        });
        window.addEventListener('click', (e) => { if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('scale-95', 'opacity-0'); setTimeout(() => { userMenu.classList.add('hidden'); if(userMenuIcon) userMenuIcon.style.transform = 'rotate(0deg)'; }, 200); } });
    }

    // Delete Logic
    window.deleteMember = (id, name) => {
        if (confirm(`Apakah Anda yakin ingin menghapus member "${name}"?`)) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-form').submit();
        }
    };
});
</script>
</body>
</html>