<?php
session_start();
include '../koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) exit();

$petugas_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$laporan_aktif = $_GET['laporan'] ?? 'parkir';
$filter_tipe = $_GET['filter_tipe'] ?? 'semua';

// Header untuk Download Excel
$filename = "Laporan_" . ucfirst($laporan_aktif) . "_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- QUERY DATA (Sama dengan API tapi tanpa LIMIT) ---

if ($laporan_aktif == 'parkir') {
    $where = "WHERE pt.processed_by_petugas_id = '$petugas_id' AND DATE(pt.check_out_time) BETWEEN '$start_date' AND '$end_date'";
    if ($filter_tipe === 'member') $where .= " AND pt.member_id IS NOT NULL";
    elseif ($filter_tipe === 'umum') $where .= " AND pt.member_id IS NULL";
    
    $query = "SELECT pt.* FROM parking_transactions pt $where ORDER BY pt.check_out_time DESC";
    $result = mysqli_query($conn, $query);
    
    echo "<table border='1'>";
    echo "<thead>
            <tr style='background-color: #FFD700;'>
                <th>No</th>
                <th>Waktu Masuk</th>
                <th>Waktu Keluar</th>
                <th>Plat Nomor</th>
                <th>Tipe</th>
                <th>Biaya</th>
            </tr>
          </thead>";
    echo "<tbody>";
    
    $no = 1;
    while($row = mysqli_fetch_assoc($result)) {
        $tipe = $row['member_id'] ? 'Member' : 'Umum';
        echo "<tr>
            <td>{$no}</td>
            <td>{$row['check_in_time']}</td>
            <td>{$row['check_out_time']}</td>
            <td>{$row['license_plate']}</td>
            <td>{$tipe}</td>
            <td>{$row['total_fee']}</td>
        </tr>";
        $no++;
    }
    echo "</tbody></table>";

} else {
    // Laporan Langganan
    $where = "WHERE mb.processed_by_petugas_id = '$petugas_id' AND mb.status = 'lunas' AND DATE(mb.payment_date) BETWEEN '$start_date' AND '$end_date'";
    $query = "SELECT mb.*, m.name as m_name FROM member_billings mb JOIN members m ON mb.member_id = m.id $where ORDER BY mb.payment_date DESC";
    $result = mysqli_query($conn, $query);

    echo "<table border='1'>";
    echo "<thead>
            <tr style='background-color: #FFD700;'>
                <th>No</th>
                <th>Nama Member</th>
                <th>Periode Tagihan</th>
                <th>Tanggal Bayar</th>
                <th>Nominal</th>
            </tr>
          </thead>";
    echo "<tbody>";
    
    $no = 1;
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
            <td>{$no}</td>
            <td>{$row['m_name']}</td>
            <td>{$row['billing_period']}</td>
            <td>{$row['payment_date']}</td>
            <td>{$row['amount']}</td>
        </tr>";
        $no++;
    }
    echo "</tbody></table>";
}
?>