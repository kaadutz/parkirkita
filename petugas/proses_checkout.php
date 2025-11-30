<?php
session_start();
// Keamanan dasar: Pastikan user login dan request adalah POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Validasi input dasar
if (!isset($_POST['transaction_id']) || !is_numeric($_POST['transaction_id'])) {
    $_SESSION['checkout_message'] = ['type' => 'error', 'text' => 'ID Transaksi tidak valid.'];
    header("Location: transaksi_keluar.php");
    exit();
}

// Ambil semua data dari form
$transaction_id = (int)$_POST['transaction_id'];
$petugas_id = (int)$_POST['petugas_id'];
$license_plate = strtoupper(mysqli_real_escape_string($conn, $_POST['license_plate']));
$vehicle_category = mysqli_real_escape_string($conn, $_POST['vehicle_category']);
$cash_paid = (float)$_POST['cash_paid'];
$check_out_time = date('Y-m-d H:i:s');

// Ambil data asli dari DB untuk perhitungan ulang (keamanan)
$query_get = "SELECT * FROM parking_transactions WHERE id = '$transaction_id'";
$result_get = mysqli_query($conn, $query_get);
$transaction = mysqli_fetch_assoc($result_get);

if ($transaction) {
    // Tentukan logika biaya berdasarkan apakah ini transaksi member atau bukan
    $total_fee = 0;
    if ($transaction['member_id'] === NULL) {
        // Logika biaya untuk NON-MEMBER
        $check_in_time = new DateTime($transaction['check_in_time']);
        $current_time = new DateTime($check_out_time);
        $interval = $check_in_time->diff($current_time);
        $total_minutes = ($interval->d * 24 * 60) + ($interval->h * 60) + $interval->i;
        
        if ($total_minutes > 10) {
            $total_fee = 3000;
            if ($total_minutes > 60) {
                $remaining_minutes = $total_minutes - 60;
                $additional_hours = ceil($remaining_minutes / 60);
                $total_fee += $additional_hours * 2000;
            }
        }
    } else {
        // Logika biaya untuk MEMBER (selalu gratis)
        $total_fee = 0;
    }

    // Validasi terakhir sisi server untuk pembayaran
    if ($cash_paid < $total_fee) {
        $_SESSION['checkout_message'] = ['type' => 'error', 'text' => 'Gagal, uang yang dibayarkan kurang dari total biaya!'];
        // Arahkan kembali ke halaman yang sesuai
        $redirect_page = $transaction['member_id'] === NULL ? 'proses_non_member.php' : 'proses_member_keluar.php';
        header("Location: $redirect_page");
        exit();
    }
    
    $change_due = $cash_paid - $total_fee;

    // Update data transaksi di database menggunakan prepared statement
    $stmt = $conn->prepare("UPDATE parking_transactions SET 
                                license_plate = ?, 
                                vehicle_category = ?, 
                                check_out_time = ?, 
                                total_fee = ?, 
                                cash_paid = ?, 
                                change_due = ?, 
                                processed_by_petugas_id = ? 
                            WHERE id = ?");
    $stmt->bind_param("sssiddis", $license_plate, $vehicle_category, $check_out_time, $total_fee, $cash_paid, $change_due, $petugas_id, $transaction_id);

    // =======================================================
    //     PERBAIKAN LOGIKA REDIRECT
    // =======================================================
    if ($stmt->execute()) {
        // Jika berhasil, SELALU arahkan ke halaman invoice
        header("Location: invoice_transaksi.php?id=$transaction_id");
        exit();
    } else {
        $_SESSION['checkout_message'] = ['type' => 'error', 'text' => 'Gagal menyimpan transaksi ke database: ' . $stmt->error];
    }
} else {
     $_SESSION['checkout_message'] = ['type' => 'error', 'text' => 'Transaksi dengan ID tersebut tidak ditemukan.'];
}

// Jika terjadi error, kembali ke halaman pilihan utama
header("Location: transaksi_keluar.php");
exit();
?>