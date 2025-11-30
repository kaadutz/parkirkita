<?php
session_start();

// 1. Cek Login & Role (Keamanan)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// 2. Include Koneksi & Library Dompdf
include '../koneksi.php';
require '../vendor/autoload.php'; // Pastikan path vendor benar

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. Ambil Data & Filter dari URL
$user_id_petugas = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$laporan_aktif = $_GET['laporan'] ?? 'parkir';
$filter_member = $_GET['filter_member'] ?? 'semua';

// Escape input untuk keamanan Database
$start_date_sql = mysqli_real_escape_string($conn, $start_date);
$end_date_sql = mysqli_real_escape_string($conn, $end_date);

// Ambil Nama Petugas untuk Header Laporan
$query_petugas = mysqli_query($conn, "SELECT name FROM users WHERE id = '$user_id_petugas'");
$petugas_data = mysqli_fetch_assoc($query_petugas);
$nama_petugas = $petugas_data['name'] ?? 'Petugas';

// 4. Siapkan Query Data (Logika Sama Persis dengan Halaman View)
$judul_laporan = "";
$data_transaksi = [];
$total_pendapatan = 0;

if ($laporan_aktif == 'parkir') {
    $judul_laporan = "Laporan Transaksi Parkir Harian";
    
    // Filter Member/Non-Member
    $filter_sql = "";
    if ($filter_member === 'member') {
        $filter_sql = " AND pt.member_id IS NOT NULL ";
    } elseif ($filter_member === 'non_member') {
        $filter_sql = " AND pt.member_id IS NULL ";
    }

    // Query Data
    $query = "SELECT pt.*, u.name as petugas_name, m.name as member_name 
              FROM parking_transactions pt
              LEFT JOIN users u ON pt.processed_by_petugas_id = u.id
              LEFT JOIN members m ON pt.member_id = m.id
              WHERE DATE(pt.check_in_time) BETWEEN '$start_date_sql' AND '$end_date_sql'
                AND pt.processed_by_petugas_id = '$user_id_petugas'
                $filter_sql
              ORDER BY pt.check_in_time DESC";
    
    $result = mysqli_query($conn, $query);
    
    // Query Total Pendapatan
    $query_sum = "SELECT SUM(total_fee) as total 
                  FROM parking_transactions pt 
                  WHERE DATE(check_in_time) BETWEEN '$start_date_sql' AND '$end_date_sql'
                    AND processed_by_petugas_id = '$user_id_petugas'
                    $filter_sql";
    $row_sum = mysqli_fetch_assoc(mysqli_query($conn, $query_sum));
    $total_pendapatan = $row_sum['total'] ?? 0;

} else {
    $judul_laporan = "Laporan Pembayaran Langganan Member";
    
    // Query Data Langganan
    $query = "SELECT mb.*, m.name as member_name, u.name as petugas_name 
              FROM member_billings mb 
              JOIN members m ON mb.member_id = m.id 
              LEFT JOIN users u ON mb.processed_by_petugas_id = u.id 
              WHERE mb.status = 'lunas' 
                AND DATE(mb.payment_date) BETWEEN '$start_date_sql' AND '$end_date_sql' 
                AND mb.processed_by_petugas_id = '$user_id_petugas'
              ORDER BY mb.payment_date DESC";
    
    $result = mysqli_query($conn, $query);

    // Query Total Pendapatan
    $query_sum = "SELECT SUM(amount) as total FROM member_billings mb 
                  WHERE status = 'lunas' 
                  AND DATE(payment_date) BETWEEN '$start_date_sql' AND '$end_date_sql' 
                  AND processed_by_petugas_id = '$user_id_petugas'";
    $row_sum = mysqli_fetch_assoc(mysqli_query($conn, $query_sum));
    $total_pendapatan = $row_sum['total'] ?? 0;
}

// 5. Susun HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>'.$judul_laporan.'</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 12px; color: #555; }
        .meta-info { margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .meta-info table { width: 100%; border: none; }
        .meta-info td { padding: 2px; border: none; }
        
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #999; padding: 6px; text-align: left; }
        table.data th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        table.data td { font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 10px; color: white; }
        .bg-member { background-color: #9333ea; color: white; } /* Purple */
        .bg-umum { background-color: #475569; color: white; } /* Slate */
        
        .summary { margin-top: 20px; text-align: right; font-size: 14px; font-weight: bold; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 10px; text-align: center; color: #888; }
    </style>
</head>
<body>

    <div class="header">
        <h1>ParkirKita System</h1>
        <p>'.$judul_laporan.'</p>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td width="15%"><strong>Periode</strong></td>
                <td width="35%">: '.date('d M Y', strtotime($start_date)).' s/d '.date('d M Y', strtotime($end_date)).'</td>
                <td width="15%"><strong>Dicetak Oleh</strong></td>
                <td width="35%">: '.htmlspecialchars($nama_petugas).'</td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: '.date('d F Y, H:i').'</td>
                <td><strong>Filter</strong></td>
                <td>: '.ucwords(str_replace('_', ' ', $filter_member)).'</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>';

if ($laporan_aktif == 'parkir') {
    $html .= '
            <tr>
                <th width="5%">No</th>
                <th width="10%">Tipe</th>
                <th width="20%">Plat Nomor / Token</th>
                <th width="20%">Waktu Masuk</th>
                <th width="20%">Waktu Keluar</th>
                <th width="25%" class="text-right">Biaya</th>
            </tr>
        </thead>
        <tbody>';
    
    $no = 1;
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tipe_label = $row['member_id'] ? '<span class="badge bg-member">Member</span>' : '<span class="badge bg-umum">Umum</span>';
            $check_out = $row['check_out_time'] ? date('d/m/Y H:i', strtotime($row['check_out_time'])) : '-';
            
            $html .= '<tr>
                <td class="text-center">'.$no++.'</td>
                <td class="text-center">'.$tipe_label.'</td>
                <td>
                    <strong>'.htmlspecialchars($row['license_plate']).'</strong><br>
                    <span style="font-size:9px; color:#666;">'.htmlspecialchars($row['parking_token']).'</span>
                </td>
                <td>'.date('d/m/Y H:i', strtotime($row['check_in_time'])).'</td>
                <td>'.$check_out.'</td>
                <td class="text-right">Rp '.number_format($row['total_fee'], 0, ',', '.').'</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" class="text-center">Tidak ada data transaksi pada periode ini.</td></tr>';
    }

} else {
    // HTML Table untuk Langganan
    $html .= '
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Member</th>
                <th width="20%">Periode Tagihan</th>
                <th width="25%">Tanggal Pembayaran</th>
                <th width="25%" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>';
    
    $no = 1;
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $html .= '<tr>
                <td class="text-center">'.$no++.'</td>
                <td>'.htmlspecialchars($row['member_name']).'</td>
                <td>'.date('F Y', strtotime($row['billing_period'])).'</td>
                <td>'.date('d/m/Y H:i', strtotime($row['payment_date'])).'</td>
                <td class="text-right">Rp '.number_format($row['amount'], 0, ',', '.').'</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="text-center">Tidak ada data pembayaran pada periode ini.</td></tr>';
    }
}

$html .= '
        </tbody>
    </table>

    <div class="summary">
        Total Pendapatan: Rp '.number_format($total_pendapatan, 0, ',', '.').'
    </div>

    <div class="footer">
        Dicetak dari Sistem ParkirKita | Halaman ini digenerate secara otomatis.
    </div>

</body>
</html>';

// 6. Proses Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Izinkan gambar dari URL jika ada

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Set ukuran kertas (A4, Portrait)
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Nama file saat didownload
$filename = "Laporan_" . ucfirst($laporan_aktif) . "_" . date('Ymd') . ".pdf";

// Output ke browser (Attachment: true = download, false = view in browser)
$dompdf->stream($filename, array("Attachment" => false));
?>