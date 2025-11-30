<?php
session_start();

// 1. Cek Login (Super Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    die("Akses ditolak. Anda bukan Super Admin.");
}

include '../koneksi.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. Ambil Parameter
$laporan_aktif = $_GET['laporan'] ?? 'parkir';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$filter_petugas = $_GET['filter_petugas'] ?? 'semua';
$filter_tipe = $_GET['filter_tipe'] ?? 'semua';

// Escape Variables
$start_date_sql = mysqli_real_escape_string($conn, $start_date);
$end_date_sql = mysqli_real_escape_string($conn, $end_date);
$filter_petugas_sql = mysqli_real_escape_string($conn, $filter_petugas);

// Info Header PDF
$judul_laporan = ($laporan_aktif == 'parkir') ? "Laporan Transaksi Parkir" : "Laporan Langganan Member";
$petugas_label = "Semua Petugas";

// Cek Label Petugas untuk Header
if ($filter_petugas !== 'semua' && !empty($filter_petugas)) {
    $q_p = mysqli_query($conn, "SELECT name FROM users WHERE id = '$filter_petugas_sql'");
    $d_p = mysqli_fetch_assoc($q_p);
    $petugas_label = $d_p['name'] ?? "ID: $filter_petugas";
}

// 3. LOGIKA QUERY (SAMA DENGAN WEB)
$total_pendapatan = 0;
$result = null;

if ($laporan_aktif == 'parkir') {
    // --- Query Parkir ---
    $where_parts = ["pt.check_out_time IS NOT NULL", "DATE(pt.check_out_time) BETWEEN '$start_date_sql' AND '$end_date_sql'"];
    
    if ($filter_petugas !== 'semua') $where_parts[] = "pt.processed_by_petugas_id = '$filter_petugas_sql'";
    if ($filter_tipe === 'member') $where_parts[] = "pt.member_id IS NOT NULL";
    elseif ($filter_tipe === 'umum') $where_parts[] = "pt.member_id IS NULL";

    $where_clause = "WHERE " . implode(' AND ', $where_parts);

    $query = "SELECT pt.*, u.name as petugas_name, m.name as member_name
              FROM parking_transactions pt
              LEFT JOIN users u ON pt.processed_by_petugas_id = u.id
              LEFT JOIN members m ON pt.member_id = m.id
              $where_clause ORDER BY pt.check_out_time DESC";
    $result = mysqli_query($conn, $query);

    $q_sum = mysqli_query($conn, "SELECT SUM(total_fee) as total FROM parking_transactions pt $where_clause");
    $total_pendapatan = mysqli_fetch_assoc($q_sum)['total'] ?? 0;

} else {
    // --- Query Langganan ---
    $where_parts = ["mb.status = 'lunas'", "DATE(mb.payment_date) BETWEEN '$start_date_sql' AND '$end_date_sql'"];

    if ($filter_petugas !== 'semua') $where_parts[] = "mb.processed_by_petugas_id = '$filter_petugas_sql'";

    $where_clause = "WHERE " . implode(' AND ', $where_parts);

    $query = "SELECT mb.*, m.name as member_name, u.name as petugas_name
              FROM member_billings mb
              JOIN members m ON mb.member_id = m.id
              LEFT JOIN users u ON mb.processed_by_petugas_id = u.id
              $where_clause ORDER BY mb.payment_date DESC";
    $result = mysqli_query($conn, $query);

    $q_sum = mysqli_query($conn, "SELECT SUM(amount) as total FROM member_billings mb $where_clause");
    $total_pendapatan = mysqli_fetch_assoc($q_sum)['total'] ?? 0;
}

// 4. GENERATE HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>'.$judul_laporan.'</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #F57C00; margin: 0; font-size: 18px; text-transform: uppercase; }
        .meta { width: 100%; margin-bottom: 15px; font-size: 11px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ccc; padding: 6px; }
        .table th { background-color: #f2f2f2; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 15px; text-align: right; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ParkirKita - '.$judul_laporan.'</h1>
        <p>Laporan Resmi Super Admin</p>
    </div>

    <table class="meta">
        <tr>
            <td width="15%"><strong>Periode</strong></td>
            <td width="35%">: '.date('d/m/Y', strtotime($start_date)).' s/d '.date('d/m/Y', strtotime($end_date)).'</td>
            <td width="15%"><strong>Filter Petugas</strong></td>
            <td width="35%">: '.htmlspecialchars($petugas_label).'</td>
        </tr>
        <tr>
            <td><strong>Dicetak Oleh</strong></td>
            <td>: '.htmlspecialchars($_SESSION['user_name']).'</td>
            <td><strong>Tgl Cetak</strong></td>
            <td>: '.date('d F Y H:i').'</td>
        </tr>
    </table>

    <table class="table">
        <thead>';

if ($laporan_aktif == 'parkir') {
    $html .= '
        <tr>
            <th>No</th>
            <th>Tipe</th>
            <th>Plat / Token</th>
            <th>Masuk</th>
            <th>Keluar</th>
            <th>Petugas</th>
            <th class="text-right">Biaya</th>
        </tr>
    </thead>
    <tbody>';
    
    $no = 1;
    while($row = mysqli_fetch_assoc($result)) {
        $tipe = $row['member_id'] ? 'MEMBER' : 'UMUM';
        $html .= '<tr>
            <td class="text-center">'.$no++.'</td>
            <td class="text-center">'.$tipe.'</td>
            <td>'.htmlspecialchars($row['license_plate'] ?: '-').'<br><small>'.$row['parking_token'].'</small></td>
            <td>'.date('d/m/y H:i', strtotime($row['check_in_time'])).'</td>
            <td>'.date('d/m/y H:i', strtotime($row['check_out_time'])).'</td>
            <td>'.htmlspecialchars($row['petugas_name'] ?? 'System').'</td>
            <td class="text-right">Rp '.number_format($row['total_fee'], 0, ',', '.').'</td>
        </tr>';
    }
} else {
    $html .= '
        <tr>
            <th>No</th>
            <th>Nama Member</th>
            <th>Periode Tagihan</th>
            <th>Tanggal Bayar</th>
            <th>Petugas</th>
            <th class="text-right">Nominal</th>
        </tr>
    </thead>
    <tbody>';
    
    $no = 1;
    while($row = mysqli_fetch_assoc($result)) {
        $html .= '<tr>
            <td class="text-center">'.$no++.'</td>
            <td>'.htmlspecialchars($row['member_name']).'</td>
            <td>'.date('F Y', strtotime($row['billing_period'])).'</td>
            <td>'.date('d/m/Y H:i', strtotime($row['payment_date'])).'</td>
            <td>'.htmlspecialchars($row['petugas_name'] ?? '-').'</td>
            <td class="text-right">Rp '.number_format($row['amount'], 0, ',', '.').'</td>
        </tr>';
    }
}

$html .= '
    </tbody>
    </table>

    <div class="summary">
        Total Pendapatan: Rp '.number_format($total_pendapatan, 0, ',', '.').'
    </div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Laporan_Admin_".date('Ymd').".pdf", ["Attachment" => false]);
?>