<?php
session_start();

// 1. Cek Keamanan (Hanya Super Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    die("Akses Ditolak. Anda bukan Super Admin.");
}

include '../koneksi.php';

// 2. Ambil Parameter Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$filter_petugas = $_GET['filter_petugas'] ?? 'semua';
$filter_tipe = $_GET['filter_tipe'] ?? 'semua';
$laporan_aktif = $_GET['laporan'] ?? 'parkir';

// Escape String untuk Keamanan Database
$start_date_sql = mysqli_real_escape_string($conn, $start_date);
$end_date_sql = mysqli_real_escape_string($conn, $end_date);
$filter_petugas_sql = mysqli_real_escape_string($conn, $filter_petugas);

// 3. Header untuk Download Excel
$filename = "Laporan_" . ucfirst($laporan_aktif) . "_ParkirKita_" . date('Ymd') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Style CSS Sederhana untuk Excel
echo '<style>
    .header { font-weight: bold; font-size: 14px; text-align: center; }
    .table-header { background-color: #F57C00; color: white; font-weight: bold; }
    td { vertical-align: middle; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
</style>';

// Judul Laporan di dalam Excel
echo '<div class="header">Laporan ' . ($laporan_aktif == 'parkir' ? 'Transaksi Parkir' : 'Langganan Member') . ' - ParkirKita</div>';
echo '<div class="header">Periode: ' . $start_date . ' s/d ' . $end_date . '</div><br>';

// ============================================================
// LOGIKA 1: LAPORAN PARKIR
// ============================================================
if ($laporan_aktif == 'parkir') {
    // Build Query
    $where_parts = [];
    $where_parts[] = "pt.check_out_time IS NOT NULL";
    $where_parts[] = "DATE(pt.check_out_time) BETWEEN '$start_date_sql' AND '$end_date_sql'";

    if ($filter_petugas !== 'semua' && !empty($filter_petugas)) {
        $where_parts[] = "pt.processed_by_petugas_id = '$filter_petugas_sql'";
    }

    if ($filter_tipe === 'member') {
        $where_parts[] = "pt.member_id IS NOT NULL";
    } elseif ($filter_tipe === 'umum') {
        $where_parts[] = "pt.member_id IS NULL";
    }

    $where_clause = "WHERE " . implode(' AND ', $where_parts);

    $query = "SELECT pt.*, u.name as petugas_name, m.name as member_name 
              FROM parking_transactions pt
              LEFT JOIN users u ON pt.processed_by_petugas_id = u.id
              LEFT JOIN members m ON pt.member_id = m.id
              $where_clause
              ORDER BY pt.check_out_time DESC";
    
    $result = mysqli_query($conn, $query);

    // Output Tabel Parkir
    echo '<table border="1">';
    echo '<thead>
            <tr class="table-header">
                <th>No</th>
                <th>Tipe Pelanggan</th>
                <th>Plat Nomor</th>
                <th>Token Parkir</th>
                <th>Waktu Masuk</th>
                <th>Waktu Keluar</th>
                <th>Durasi (Menit)</th>
                <th>Petugas</th>
                <th>Biaya (Rp)</th>
            </tr>
          </thead>';
    echo '<tbody>';

    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $tipe = $row['member_id'] ? 'Member' : 'Umum';
        
        // Hitung Durasi dalam menit untuk data mentah
        $in = new DateTime($row['check_in_time']);
        $out = new DateTime($row['check_out_time']);
        $diff = $in->diff($out);
        $total_menit = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

        echo '<tr>';
        echo '<td class="text-center">' . $no++ . '</td>';
        echo '<td>' . $tipe . '</td>';
        echo '<td>' . htmlspecialchars($row['license_plate']) . '</td>';
        echo '<td style="mso-number-format:\'\@\'">' . $row['parking_token'] . '</td>'; // Format Text agar tidak jadi E+
        echo '<td>' . $row['check_in_time'] . '</td>';
        echo '<td>' . $row['check_out_time'] . '</td>';
        echo '<td class="text-center">' . $total_menit . '</td>';
        echo '<td>' . htmlspecialchars($row['petugas_name'] ?? 'System') . '</td>';
        echo '<td class="text-right">' . $row['total_fee'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

} 
// ============================================================
// LOGIKA 2: LAPORAN LANGGANAN
// ============================================================
else {
    // Build Query
    $where_parts = [];
    $where_parts[] = "mb.status = 'lunas'";
    $where_parts[] = "DATE(mb.payment_date) BETWEEN '$start_date_sql' AND '$end_date_sql'";

    if ($filter_petugas !== 'semua' && !empty($filter_petugas)) {
        $where_parts[] = "mb.processed_by_petugas_id = '$filter_petugas_sql'";
    }

    $where_clause = "WHERE " . implode(' AND ', $where_parts);

    $query = "SELECT mb.*, m.name as member_name, u.name as petugas_name
              FROM member_billings mb
              JOIN members m ON mb.member_id = m.id
              LEFT JOIN users u ON mb.processed_by_petugas_id = u.id
              $where_clause
              ORDER BY mb.payment_date DESC";
    
    $result = mysqli_query($conn, $query);

    // Output Tabel Langganan
    echo '<table border="1">';
    echo '<thead>
            <tr class="table-header">
                <th>No</th>
                <th>Nama Member</th>
                <th>Periode Tagihan</th>
                <th>Tanggal Bayar</th>
                <th>Petugas Penerima</th>
                <th>Nominal (Rp)</th>
            </tr>
          </thead>';
    echo '<tbody>';

    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td class="text-center">' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($row['member_name']) . '</td>';
        echo '<td>' . date('F Y', strtotime($row['billing_period'])) . '</td>';
        echo '<td>' . $row['payment_date'] . '</td>';
        echo '<td>' . htmlspecialchars($row['petugas_name'] ?? 'System') . '</td>';
        echo '<td class="text-right">' . $row['amount'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
?>