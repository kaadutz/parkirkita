<?php
session_start();

// Security: Make sure ticket data exists
if (!isset($_SESSION['tiket_data'])) {
    header('Location: index.php');
    exit();
}

$tiket = $_SESSION['tiket_data'];

// IMPORTANT: DO NOT UNSET THE SESSION HERE
// unset($_SESSION['tiket_data']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Tiket Parkir - <?= htmlspecialchars($tiket['token']); ?></title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap');
        body { font-family: 'Roboto Mono', monospace; background-color: #e5e7eb; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .ticket-container { font-family: 'Roboto Mono', monospace; background-color: #fff; padding: 15px; width: 300px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; font-size: 0.9rem; }
        .content table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .content td { padding: 4px 0; }
        .content .label { font-weight: 700; }
        .barcode-section { text-align: center; margin: 15px 0; }
        .barcode-section img { max-width: 100%; height: auto; }
        .footer { border-top: 2px dashed #000; padding-top: 10px; margin-top: 10px; text-align: center; font-size: 0.8rem; }
        .footer p { margin: 3px 0; }
        
        .controls { margin-top: 20px; text-align: center; display: flex; gap: 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; background-color: #4b5563; color: white; padding: 12px 20px; border: none; border-radius: 6px; font-weight: bold; text-decoration: none; cursor: pointer; font-size: 1rem; transition: background-color 0.2s; }
        .btn:hover { background-color: #374151; }
        .btn i { margin-right: 8px; }
        
        .btn-primary { background-color: #F57C00; }
        .btn-primary:hover { background-color: #e65100; }

        @media print {
            body > *:not(.print-area) { display: none; }
            .print-area { position: absolute; top: 0; left: 0; width: 100%; }
            .ticket-container { box-shadow: none; margin: 0 auto; width: 100%; padding: 10mm; box-sizing: border-box; font-size: 12pt; }
            .header h1 { font-size: 24pt; }
            .header p, .content table { font-size: 14pt; }
            .footer { font-size: 10pt; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
</head>
<body onload="window.print()">

    <div class="print-area">
        <div class="ticket-container">
            <div class="header"><h1>PARKIRKITA</h1><p>TIKET PARKIR MASUK</p></div>
            <div class="content">
                <table>
                    <tr><td class="label">ID Tiket</td><td>: <?= htmlspecialchars($tiket['token']); ?></td></tr>
                    <tr><td class="label">Waktu</td><td>: <?= date('d M Y', strtotime($tiket['waktu_masuk'])); ?></td></tr>
                    <tr><td class="label">Jam</td><td>: <?= date('H:i:s', strtotime($tiket['waktu_masuk'])); ?> WIB</td></tr>
                </table>
            </div>
            <div class="barcode-section">
                <img src="generate_barcode.php?code=<?= urlencode($tiket['token']); ?>" alt="Barcode Tiket Parkir">
                <p style="font-size: 0.8em; letter-spacing: 2px; margin-top: 5px;"><?= htmlspecialchars($tiket['token']); ?></p>
            </div>
            <div class="footer"><p><strong>PERHATIAN</strong></p><p>Simpan tiket ini dengan baik.</p><p>Tiket hilang akan dikenakan denda.</p><p>Terima Kasih</p></div>
        </div>
    </div>

    <div class="controls">
        <button onclick="window.print()" class="btn">
            <i class="fas fa-print"></i> Cetak Ulang
        </button>
        <a href="selesai_cetak.php" class="btn btn-primary">
            <i class="fas fa-check"></i> Selesai & Kembali
        </a>
    </div>

</body>
</html>