<?php
// Memuat autoloader dari Composer
require '../vendor/autoload.php';

// Pastikan ada parameter 'code' yang dikirim melalui URL
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Inisialisasi generator barcode
    $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

    try {
        // Hasilkan data gambar barcode dalam format PNG
        // Parameter: (kode, tipe_barcode, lebar_per_bar, tinggi_dalam_pixel)
        $barcodeImage = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 60);

        // Beri tahu browser bahwa output dari file ini adalah gambar PNG
        header('Content-Type: image/png');
        
        // Tampilkan gambar
        echo $barcodeImage;

    } catch (Exception $e) {
        // Jika ada error (misalnya kode tidak valid), kirim header error
        header("HTTP/1.1 500 Internal Server Error");
        echo 'Error: ' . $e->getMessage();
    }
} else {
    // Jika tidak ada parameter 'code', kirim header error
    header("HTTP/1.1 400 Bad Request");
    echo 'Error: Missing barcode data.';
}