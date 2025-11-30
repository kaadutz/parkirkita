<?php
session_start();
// Keamanan: Pastikan user yang login berhak mengakses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    http_response_code(403); // Forbidden
    exit('Akses Ditolak');
}
// Path disesuaikan untuk keluar dari folder 'petugas'
include '../koneksi.php';

// Pastikan library dompdf sudah di-install via Composer
// Path disesuaikan untuk keluar dari folder 'petugas'
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$billing_id = isset($_GET['billing_id']) ? (int)$_GET['billing_id'] : 0;
if ($billing_id == 0) {
    http_response_code(400); // Bad Request
    exit('ID Tagihan tidak valid.');
}

// Query JOIN untuk mendapatkan semua data yang dibutuhkan untuk invoice
$query = "SELECT mb.*, m.name as member_name, u.name as petugas_name
          FROM member_billings mb
          JOIN members m ON mb.member_id = m.id
          LEFT JOIN users u ON mb.processed_by_petugas_id = u.id
          WHERE mb.id = '$billing_id'";
$result = mysqli_query($conn, $query);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    http_response_code(404); // Not Found
    exit('Invoice tidak ditemukan.');
}

// --- MEMBUAT HTML UNTUK DI-RENDER MENJADI PDF ---
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice Member #' . $billing_id . '</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 12px; color: #333; }
        .container { border: 1px solid #eee; padding: 30px; border-radius: 5px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #F57C00; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #555; }
        .details-table { width: 100%; margin-bottom: 30px; }
        .details-table td { padding: 8px 0; vertical-align: top; }
        .details-table .label { color: #888; width: 150px; }
        .summary-table { width: 100%; border-top: 2px solid #333; margin-top: 20px; }
        .summary-table td { padding: 10px 0; }
        .summary-table .total td { font-size: 16px; font-weight: bold; border-top: 1px solid #eee; }
        .status { text-align: center; margin-top: 40px; font-size: 28px; font-weight: bold; color: #28a745; letter-spacing: 2px; }
        .footer { text-align: center; margin-top: 40px; font-size: 10px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>INVOICE PEMBAYARAN MEMBER</h1>
            <p>ParkirKita System</p>
        </div>
        <table class="details-table">
            <tr><td class="label">ID Tagihan:</td><td><b>#' . str_pad($invoice['id'], 5, '0', STR_PAD_LEFT) . '</b></td></tr>
            <tr><td class="label">Nama Member:</td><td>' . htmlspecialchars($invoice['member_name']) . '</td></tr>
            <tr><td class="label">Periode Tagihan:</td><td>' . date('F Y', strtotime($invoice['billing_period'])) . '</td></tr>
            <tr><td class="label">Tanggal Bayar:</td><td>' . date('d M Y, H:i:s', strtotime($invoice['payment_date'])) . '</td></tr>
            <tr><td class="label">Diproses oleh:</td><td>' . htmlspecialchars($invoice['petugas_name'] ?? 'Sistem') . '</td></tr>
        </table>
        <table class="summary-table">
            <tr><td class="label">Total Tagihan:</td><td style="text-align:right;">Rp ' . number_format($invoice['amount'], 0, ',', '.') . '</td></tr>
            <tr><td class="label">Uang Tunai:</td><td style="text-align:right;">Rp ' . number_format($invoice['cash_paid'], 0, ',', '.') . '</td></tr>
            <tr class="total"><td class="label">Kembalian:</td><td style="text-align:right;">Rp ' . number_format($invoice['change_due'], 0, ',', '.') . '</td></tr>
        </table>
        <div class="status">LUNAS</div>
    </div>
    <div class="footer">
        <p>Terima kasih atas pembayaran Anda. Invoice ini dibuat secara otomatis oleh sistem.</p>
    </div>
</body>
</html>';

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Penting jika Anda menggunakan gambar dari URL eksternal
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

// Load HTML ke Dompdf
$dompdf->loadHtml($html);

// (Opsional) Mengatur ukuran kertas dan orientasi
$dompdf->setPaper('A5', 'portrait');

// Merender HTML menjadi PDF
$dompdf->render();

// Menyiapkan nama file untuk diunduh
$filename = "invoice_member_" . str_replace(' ', '_', $invoice['member_name']) . "_" . $invoice['id'] . ".pdf";

// Mengirimkan PDF ke browser untuk diunduh (bukan pratinjau)
$dompdf->stream($filename, ["Attachment" => true]);
exit(); // Pastikan tidak ada output lain setelah ini