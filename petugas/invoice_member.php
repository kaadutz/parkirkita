<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include '../koneksi.php';

$billing_id = isset($_GET['billing_id']) ? (int)$_GET['billing_id'] : 0;
if ($billing_id == 0) { header("Location: kelola_member.php"); exit(); }

// Query JOIN untuk mendapatkan semua data yang dibutuhkan untuk invoice
$query = "SELECT mb.*, m.name as member_name, m.phone_number, u.name as petugas_name
          FROM member_billings mb
          JOIN members m ON mb.member_id = m.id
          LEFT JOIN users u ON mb.processed_by_petugas_id = u.id
          WHERE mb.id = '$billing_id'";
$result = mysqli_query($conn, $query);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) { 
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invoice tidak ditemukan.'];
    header("Location: kelola_member.php"); 
    exit(); 
}

// --- LOGIKA UNTUK KIRIM WHATSAPP ---
$nama_member_wa = urlencode($invoice['member_name']);
$bulan_tagihan_wa = urlencode(date('F Y', strtotime($invoice['billing_period'])));
$jumlah_tagihan_wa = urlencode(number_format($invoice['amount']));

// Ganti 'PROYEK_ANDA' dengan nama folder proyek Anda jika berbeda
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/Raka/parkirr"; 
$link_download_wa = urlencode($base_url . "/petugas/export_pdf_invoice.php?billing_id=" . $billing_id);

$pesan_wa = "Halo {$nama_member_wa},\n\nPembayaran tagihan member ParkirKita untuk bulan *{$bulan_tagihan_wa}* sebesar *Rp {$jumlah_tagihan_wa}* telah berhasil.\n\nTerima kasih atas pembayaran Anda.\n\nUnduh invoice Anda di sini:\n{$link_download_wa}";
$link_wa = "https://api.whatsapp.com/send?phone=" . htmlspecialchars($invoice['phone_number']) . "&text=" . $pesan_wa;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice Pembayaran Member - ParkirKita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            body * { visibility: hidden; }
            #invoice-area, #invoice-area * { visibility: visible; }
            #invoice-area { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8">
    <div class="max-w-md mx-auto" id="invoice-area">
        <div class="bg-white p-8 rounded-lg shadow-lg border-t-4 border-orange-500">
            <div class="text-center mb-8">
                <i class="fas fa-parking text-orange-500 text-4xl"></i>
                <h1 class="text-2xl font-bold text-slate-800 mt-2">INVOICE MEMBER</h1>
                <p class="text-slate-500">ID Tagihan: #<?= str_pad($invoice['id'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            
            <div class="space-y-3 border-y py-4">
                <div class="flex justify-between"><span class="text-slate-500">Nama Member:</span><span class="font-semibold text-right"><?= htmlspecialchars($invoice['member_name']) ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Periode Tagihan:</span><span class="font-semibold text-right"><?= date('F Y', strtotime($invoice['billing_period'])) ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Tanggal Bayar:</span><span class="font-semibold text-right"><?= date('d M Y, H:i', strtotime($invoice['payment_date'])) ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Diproses oleh:</span><span class="font-semibold text-right"><?= htmlspecialchars($invoice['petugas_name'] ?? 'Sistem') ?></span></div>
            </div>

            <div class="space-y-3 border-b py-4">
                <div class="flex justify-between text-lg"><span class="text-slate-500">Total Tagihan:</span><span class="font-bold">Rp <?= number_format($invoice['amount']) ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Uang Tunai:</span><span class="font-semibold">Rp <?= number_format($invoice['cash_paid']) ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Kembalian:</span><span class="font-semibold">Rp <?= number_format($invoice['change_due']) ?></span></div>
            </div>
            <p class="text-center mt-6 text-green-600 font-bold text-2xl tracking-wider">LUNAS</p>
        </div>
    </div>
    
    <div class="max-w-md mx-auto text-center mt-6 flex flex-col sm:flex-row justify-center gap-4 no-print">
        <button onclick="window.print()" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded-lg"><i class="fas fa-print mr-2"></i>Print</button>
        <a href="export_pdf_invoice.php?billing_id=<?= $billing_id ?>" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg"><i class="fas fa-file-pdf mr-2"></i>Download PDF</a>
        <?php if (!empty($invoice['phone_number'])): ?>
        <a href="<?= $link_wa ?>" target="_blank" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg"><i class="fab fa-whatsapp mr-2"></i>Kirim WA</a>
        <?php endif; ?>
    </div>
    <div class="max-w-md mx-auto text-center mt-4 no-print">
        <a href="kelola_member.php" class="text-blue-600 hover:text-blue-800">Kembali ke Daftar Member</a>
    </div>
</body>
</html>