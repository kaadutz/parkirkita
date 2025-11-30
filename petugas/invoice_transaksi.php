<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include '../koneksi.php';

$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($transaction_id == 0) { header("Location: transaksi_keluar.php"); exit(); }

$query = "SELECT pt.*, u.name as petugas_name FROM parking_transactions pt LEFT JOIN users u ON pt.processed_by_petugas_id = u.id WHERE pt.id = '$transaction_id'";
$result = mysqli_query($conn, $query);
$invoice = mysqli_fetch_assoc($result);
if (!$invoice) { header("Location: transaksi_keluar.php"); exit(); }

// Fungsi untuk menghitung durasi
function hitungDurasi($check_in, $check_out) {
    if (empty($check_out)) return '-';
    $in = new DateTime($check_in);
    $out = new DateTime($check_out);
    $interval = $in->diff($out);
    $durasi = '';
    if ($interval->d > 0) $durasi .= $interval->d . ' hari ';
    if ($interval->h > 0) $durasi .= $interval->h . ' jam ';
    if ($interval->i > 0) $durasi .= $interval->i . ' mnt ';
    return trim($durasi) ?: '< 1 mnt';
}

$durasi = hitungDurasi($invoice['check_in_time'], $invoice['check_out_time']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice #<?= $transaction_id ?> - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'Roboto Mono', monospace; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            body, .container { padding: 0; margin: 0; }
            .shadow-lg { box-shadow: none; }
            .rounded-lg { border-radius: 0; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8">
    <div class="max-w-md mx-auto container" id="invoice-area">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <!-- Header Invoice -->
            <div class="flex justify-between items-center pb-6 border-b-2 border-dashed">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">INVOICE PARKIR</h1>
                    <p class="text-sm text-slate-500">ParkirKita System</p>
                </div>
                <div class="text-orange-500">
                    <i class="fas fa-parking text-4xl"></i>
                </div>
            </div>

            <!-- Detail Transaksi -->
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 py-6 border-b">
                <div>
                    <p class="text-sm text-slate-500">Plat Nomor</p>
                    <p class="font-bold text-lg uppercase"><?= htmlspecialchars($invoice['license_plate']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kategori</p>
                    <p class="font-semibold text-lg capitalize"><?= htmlspecialchars($invoice['vehicle_category']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Waktu Masuk</p>
                    <p class="font-semibold"><?= date('d M Y, H:i', strtotime($invoice['check_in_time'])) ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Waktu Keluar</p>
                    <p class="font-semibold"><?= date('d M Y, H:i', strtotime($invoice['check_out_time'])) ?></p>
                </div>
                <div class="col-span-2">
                    <p class="text-sm text-slate-500">Durasi</p>
                    <p class="font-semibold"><?= $durasi ?></p>
                </div>
            </div>

            <!-- Detail Pembayaran -->
            <div class="py-6 space-y-3">
                <div class="flex justify-between items-center text-xl">
                    <span class="font-medium text-slate-600">Total Biaya:</span>
                    <span class="font-bold text-orange-600">Rp <?= number_format($invoice['total_fee']) ?></span>
                </div>
                <div class="flex justify-between items-center text-slate-500">
                    <span>Uang Tunai:</span>
                    <span>Rp <?= number_format($invoice['cash_paid']) ?></span>
                </div>
                <div class="flex justify-between items-center text-slate-500">
                    <span>Kembalian:</span>
                    <span>Rp <?= number_format($invoice['change_due']) ?></span>
                </div>
            </div>

            <!-- Footer Invoice -->
            <div class="border-t-2 border-dashed pt-6 mt-2 text-center text-slate-500 text-sm">
                <p>ID Transaksi: <span class="font-mono text-xs"><?= htmlspecialchars($invoice['parking_token']) ?></span></p>
                <p>Diproses oleh: <?= htmlspecialchars($invoice['petugas_name'] ?? 'Sistem') ?></p>
                <p class="mt-4 font-semibold">Terima kasih atas kunjungan Anda!</p>
            </div>
        </div>
    </div>
    
    <div class="max-w-md mx-auto text-center mt-6 flex justify-center gap-4 no-print">
        <button onclick="window.print()" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded-lg transition-transform hover:scale-105"><i class="fas fa-print mr-2"></i>Print</button>
        <a href="transaksi_keluar.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-transform hover:scale-105"><i class="fas fa-sync-alt mr-2"></i>Transaksi Baru</a>
    </div>
</body>
</html>