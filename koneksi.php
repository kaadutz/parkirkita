<?php
// --- Konfigurasi Koneksi Database ---
// Pastikan timezone diset di awal agar date() mengembalikan waktu lokal yang benar
date_default_timezone_set('Asia/Jakarta'); // ganti sesuai zona Anda, mis. 'Asia/Jakarta'

// koneksi MySQL
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'parkirrr';
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Database connection error: ' . mysqli_connect_error());
}

// Opsional: Atur character set ke utf8mb4 untuk mendukung emoji dan karakter internasional
$conn->set_charset("utf8mb4");
?>