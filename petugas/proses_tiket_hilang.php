<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['petugas', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tiket_hilang.php");
    exit();
}

$petugas_id = $_SESSION['user_id'];
$cash_paid = (float)$_POST['cash_paid'];
$vehicle_category = mysqli_real_escape_string($conn, $_POST['vehicle_category']);
$license_plate = strtoupper(mysqli_real_escape_string($conn, $_POST['license_plate']));
$check_out_time = date('Y-m-d H:i:s');

// Denda Config
$denda_amount = ($vehicle_category == 'motor') ? 25000 : 50000;

// === SKENARIO 1: UPDATE TRANSAKSI EKSISTING ===
if (isset($_POST['transaction_id']) && !empty($_POST['transaction_id'])) {
    $trx_id = (int)$_POST['transaction_id'];

    // Ambil data transaksi
    $q = mysqli_query($conn, "SELECT * FROM parking_transactions WHERE id = '$trx_id'");
    $trx = mysqli_fetch_assoc($q);

    if ($trx) {
        $total_fee = 0;

        // Hitung Biaya Parkir Normal (Jika bukan member)
        if ($trx['member_id'] === NULL) {
            $check_in = new DateTime($trx['check_in_time']);
            $check_out = new DateTime($check_out_time);
            $diff = $check_in->diff($check_out);
            $minutes = ($diff->d * 24 * 60) + ($diff->h * 60) + $diff->i;

            if ($minutes > 10) {
                $total_fee = 3000;
                if ($minutes > 60) {
                    $extra_hours = ceil(($minutes - 60) / 60);
                    $total_fee += $extra_hours * 2000;
                }
            }
        }
        // Jika member, fee parkir = 0

        // Tambahkan Denda
        $final_total = $total_fee + $denda_amount;
        $change_due = $cash_paid - $final_total;

        if ($cash_paid < $final_total) {
            $_SESSION['pesan'] = "Uang pembayaran kurang!";
            $_SESSION['pesan_tipe'] = "gagal";
            header("Location: tiket_hilang.php?plate=" . $license_plate);
            exit();
        }

        // Update Database
        // Ubah token menjadi LOST-TokenLama agar terdeteksi di dashboard sebagai tiket hilang
        // Gunakan CONCAT, pastikan panjang kolom cukup. Jika token sudah ada LOST, biarkan.
        $new_token_pfx = (strpos($trx['parking_token'], 'LOST') === 0) ? '' : 'LOST-';

        $query_update = "UPDATE parking_transactions SET
                         check_out_time = '$check_out_time',
                         total_fee = '$final_total',
                         cash_paid = '$cash_paid',
                         change_due = '$change_due',
                         processed_by_petugas_id = '$petugas_id',
                         parking_token = CONCAT('$new_token_pfx', parking_token)
                         WHERE id = '$trx_id'";

        if (mysqli_query($conn, $query_update)) {
            $_SESSION['pesan'] = "Transaksi Tiket Hilang Berhasil Disimpan.";
            $_SESSION['pesan_tipe'] = "sukses";
            header("Location: invoice_transaksi.php?id=" . $trx_id);
            exit();
        } else {
            $_SESSION['pesan'] = "Gagal update database: " . mysqli_error($conn);
            $_SESSION['pesan_tipe'] = "gagal";
            header("Location: tiket_hilang.php?plate=" . $license_plate);
            exit();
        }

    } else {
        $_SESSION['pesan'] = "Transaksi tidak ditemukan!";
        $_SESSION['pesan_tipe'] = "gagal";
        header("Location: tiket_hilang.php");
        exit();
    }
}

// === SKENARIO 2: INSERT BARU (MANUAL) ===
else {
    $final_total = $denda_amount; // Hanya denda
    $change_due = $cash_paid - $final_total;

    if ($cash_paid < $final_total) {
        $_SESSION['pesan'] = "Uang pembayaran kurang!";
        $_SESSION['pesan_tipe'] = "gagal";
        header("Location: tiket_hilang.php?plate=" . $license_plate);
        exit();
    }

    $parking_token = 'LOST-' . time() . rand(100, 999);

    // Insert
    $stmt = $conn->prepare("INSERT INTO parking_transactions
                            (parking_token, license_plate, vehicle_category, check_in_time, check_out_time, total_fee, cash_paid, change_due, processed_by_petugas_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdddi", $parking_token, $license_plate, $vehicle_category, $check_out_time, $check_out_time, $final_total, $cash_paid, $change_due, $petugas_id);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $_SESSION['pesan'] = "Transaksi Tiket Hilang (Manual) Berhasil.";
        $_SESSION['pesan_tipe'] = "sukses";
        header("Location: invoice_transaksi.php?id=" . $new_id);
        exit();
    } else {
        $_SESSION['pesan'] = "Gagal menyimpan: " . $stmt->error;
        $_SESSION['pesan_tipe'] = "gagal";
        header("Location: tiket_hilang.php");
        exit();
    }
}
?>