<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit('Akses Ditolak'); }
include '../koneksi.php';

// Pastikan library QR Code (endroid/qr-code) sudah di-install via Composer
require '../vendor/autoload.php';

// Import kelas-kelas yang diperlukan untuk endroid/qr-code versi 4+
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Color\Color;

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id == 0) { exit('ID Member tidak valid.'); }

$query_member = mysqli_query($conn, "SELECT * FROM members WHERE id = '$member_id'");
$member = mysqli_fetch_assoc($query_member);
if (!$member) { exit('Member tidak ditemukan.'); }

// Generate QR Code menggunakan sintaks yang benar untuk versi 4+
try {
    $data = (string) $member['member_card_id'];

    // Versi modern endroid/qr-code v4+
    if (class_exists('Endroid\\QrCode\\Writer\\PngWriter') && method_exists('Endroid\\QrCode\\QrCode', 'create')) {
        $writer = new PngWriter();
        $qrCode = \Endroid\QrCode\QrCode::create($data)
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setSize(200)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        $result = $writer->write($qrCode);
        $qr_code_uri = $result->getDataUri();

    // Versi lama (constructor + writeDataUri / writeString)
    } elseif (class_exists('Endroid\\QrCode\\QrCode')) {
        $qr = new QrCode($data);
        if (method_exists($qr, 'setSize')) { $qr->setSize(200); }
        if (method_exists($qr, 'setMargin')) { $qr->setMargin(10); }

        if (method_exists($qr, 'writeDataUri')) {
            $qr_code_uri = $qr->writeDataUri();
        } elseif (method_exists($qr, 'writeString')) {
            $png = $qr->writeString();
            $qr_code_uri = 'data:image/png;base64,' . base64_encode($png);
        } else {
            // library ada tapi API tidak dikenali — fallback ke layanan eksternal
            $qr_code_uri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data);
        }

    // Tidak ada library yang cocok — fallback ke layanan eksternal
    } else {
        $qr_code_uri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data);
    }
} catch (\Throwable $e) {
    exit('Gagal membuat QR Code: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Member - <?= htmlspecialchars($member['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <style>
        .card { 
            width: 3.375in; /* Ukuran standar kartu (85.6mm) */
            height: 2.125in; /* Ukuran standar kartu (53.98mm) */
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        .card::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -30%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        .font-mono {
            font-family: 'Roboto Mono', monospace;
        }
        .logo-icon {
            background: linear-gradient(45deg, #ff7e5f, #feb47b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .qr-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .chip {
            background: linear-gradient(135deg, #d4af37 0%, #f9e076 100%);
            width: 40px;
            height: 30px;
            border-radius: 5px;
            position: relative;
            overflow: hidden;
        }
        .chip::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent 40%, rgba(255,255,255,0.3) 50%, transparent 60%);
        }
        @media print {
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
            .no-print { display: none; }
            @page { 
                margin: 0; 
                size: 85.6mm 53.98mm; /* Ukuran cetak presisi kartu kredit */
            }
            .card-wrapper {
                padding: 0;
                margin: 0;
                width: 100%;
                height: 100%;
            }
            .card {
                width: 100%;
                height: 100%;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="min-h-screen bg-slate-200 flex flex-col justify-center items-center p-4 card-wrapper">
        <!-- Tampilan Kartu dengan Desain & QR Code -->
        <div class="card rounded-xl shadow-2xl text-white relative z-10">
            <div class="h-full p-4 flex flex-col justify-between">
                <!-- Header Kartu -->
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-bold text-xl">
                            <i class="fas fa-parking logo-icon"></i> Parkir<span class="text-yellow-300">Kita</span>
                        </p>
                        <p class="text-xs opacity-80 mt-1">Member Card</p>
                    </div>
                    <div class="chip"></div>
                </div>

                <!-- Konten Utama Kartu -->
                <div class="flex justify-between items-end">
                    <!-- Info Member -->
                    <div class="flex-1">
                        <div class="mb-3">
                            <p class="text-xs opacity-80 uppercase tracking-wider">Nama Member</p>
                            <p class="font-bold text-lg leading-tight truncate max-w-[150px]"><?= htmlspecialchars($member['name']) ?></p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs opacity-80">ID Kartu</p>
                            <p class="font-mono text-xs tracking-wider font-medium"><?= htmlspecialchars($member['member_card_id']) ?></p>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="qr-container rounded-lg p-1.5 flex-shrink-0">
                        <img src="<?= $qr_code_uri ?>" alt="QR Code" class="w-20 h-20">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 no-print text-center">
            <p class="text-slate-600">Dialog print akan muncul otomatis.</p>
            <a href="javascript:window.close()" class="mt-2 text-blue-600 hover:underline">Tutup Halaman</a>
        </div>
    </div>
</body>
</html>