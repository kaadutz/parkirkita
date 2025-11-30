<?php
session_start();
include '../koneksi.php';

// Validasi Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validasi Role (Fix Security Vulnerability)
if (!in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tiket_hilang.php");
    exit();
}

$license_plate = strtoupper(trim($_POST['license_plate']));
$vehicle_category = $_POST['vehicle_category'];
$cash_paid = (float)$_POST['cash_paid'];
$petugas_id = $_SESSION['user_id'];

// Validasi
if (empty($license_plate) || empty($vehicle_category)) {
    $_SESSION['pesan'] = "Semua field harus diisi!";
    $_SESSION['pesan_tipe'] = "gagal";
    header("Location: tiket_hilang.php");
    exit();
}

// Tentukan Denda
$denda_motor = 25000;
$denda_mobil = 50000;
$total_fee = ($vehicle_category == 'motor') ? $denda_motor : $denda_mobil;

// Validasi Pembayaran
if ($cash_paid < $total_fee) {
    $_SESSION['pesan'] = "Uang pembayaran kurang!";
    $_SESSION['pesan_tipe'] = "gagal";
    header("Location: tiket_hilang.php");
    exit();
}

$change_due = $cash_paid - $total_fee;

// Generate Token Unik Khusus
$parking_token = 'LOST-' . time() . rand(100, 999);
$now = date('Y-m-d H:i:s');

// Insert ke Database
$query = "INSERT INTO parking_transactions
          (parking_token, license_plate, vehicle_category, check_in_time, check_out_time, total_fee, cash_paid, change_due, processed_by_petugas_id)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssdddi", $parking_token, $license_plate, $vehicle_category, $now, $now, $total_fee, $cash_paid, $change_due, $petugas_id);

if ($stmt->execute()) {
    $last_id = $conn->insert_id;

    // Redirect ke Invoice
    $_SESSION['pesan'] = "Transaksi tiket hilang berhasil diproses.";
    $_SESSION['pesan_tipe'] = "sukses";
    header("Location: invoice_transaksi.php?id=" . $last_id);
} else {
    $_SESSION['pesan'] = "Gagal memproses transaksi: " . $stmt->error;
    $_SESSION['pesan_tipe'] = "gagal";
    header("Location: tiket_hilang.php");
}

$stmt->close();
$conn->close();
?>