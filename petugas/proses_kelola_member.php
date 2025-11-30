<?php
session_start();
include '../koneksi.php';

// Keamanan dasar: Pastikan user login dan request adalah POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../login.php");
    exit();
}

$petugas_id = $_SESSION['user_id'];
$biaya_member_bulanan = 150000;

// =======================================================
// 1. DAFTAR MEMBER BARU
// =======================================================
if (isset($_POST['add_member_payment'])) {
    $cash_paid = (float)($_POST['cash_paid'] ?? 0);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number'] ?? '');
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    
    // Validasi Uang
    if ($cash_paid < $biaya_member_bulanan) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Pembayaran gagal. Uang kurang.'];
        header("Location: tambah_member.php");
        exit();
    }

    // Validasi Nomor HP
    if (!empty($phone_number)) {
        $checkPhone = mysqli_query($conn, "SELECT id FROM members WHERE phone_number = '$phone_number'");
        if (mysqli_num_rows($checkPhone) > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Nomor HP sudah terdaftar.'];
            header("Location: tambah_member.php");
            exit();
        }
    }
    
    $member_card_id = "MBR" . time();
    $join_date = date('Y-m-d');
    
    // Status Default Aktif karena langsung bayar
    $query_member = "INSERT INTO members (member_card_id, name, phone_number, join_date, status, created_by_user_id) 
                     VALUES ('$member_card_id', '$name', '$phone_number', '$join_date', 'aktif', '$petugas_id')";
    
    if (mysqli_query($conn, $query_member)) {
        $new_member_id = mysqli_insert_id($conn);
        $billing_period = date('Y-m-01');
        $payment_date = date('Y-m-d H:i:s');
        $change_due = $cash_paid - $biaya_member_bulanan;
        
        $query_billing = "INSERT INTO member_billings (member_id, billing_period, amount, status, payment_date, cash_paid, change_due, processed_by_petugas_id)
                          VALUES ('$new_member_id', '$billing_period', '$biaya_member_bulanan', 'lunas', '$payment_date', '$cash_paid', '$change_due', '$petugas_id')";
        
        if(mysqli_query($conn, $query_billing)) {
            $billing_id = mysqli_insert_id($conn);
            header("Location: invoice_member.php?billing_id=$billing_id");
            exit();
        }
    }
    
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal mendaftarkan member: ' . mysqli_error($conn)];
    header("Location: tambah_member.php");
    exit();
}

// =======================================================
// 2. BAYAR TAGIHAN BULANAN (AUTO-ACTIVATE)
// =======================================================
if (isset($_POST['pay_bill'])) {
    if (!isset($_POST['billing_id'])) { die("ERROR: billing_id tidak terkirim!"); }
    
    $billing_id = (int)$_POST['billing_id'];
    $cash_paid = (float)$_POST['cash_paid'];
    
    $query_get = "SELECT amount, member_id FROM member_billings WHERE id = '$billing_id'";
    $result_get = mysqli_query($conn, $query_get);

    if ($result_get && mysqli_num_rows($result_get) > 0) {
        $billing = mysqli_fetch_assoc($result_get);
        $amount = (float)$billing['amount'];
        $member_id = $billing['member_id'];

        if ($cash_paid < $amount) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Pembayaran gagal. Uang kurang."];
            header("Location: bayar_member.php?billing_id=$billing_id");
            exit();
        }
        
        $payment_date = date('Y-m-d H:i:s');
        $change_due = $cash_paid - $amount;
        
        $query_update = "UPDATE member_billings 
                         SET status='lunas', 
                             payment_date='$payment_date', 
                             cash_paid='$cash_paid', 
                             change_due='$change_due', 
                             processed_by_petugas_id='$petugas_id' 
                         WHERE id='$billing_id'";

        if (mysqli_query($conn, $query_update)) {
            if (mysqli_affected_rows($conn) > 0) {
                
                // --- LOGIKA BARU: AKTIFKAN MEMBER SETELAH BAYAR ---
                // Karena tagihan lunas, status member kembali aktif
                $query_activate = "UPDATE members SET status='aktif' WHERE id='$member_id'";
                mysqli_query($conn, $query_activate);
                // -------------------------------------------------

                header("Location: invoice_member.php?billing_id=$billing_id");
                exit();
            } else {
                $_SESSION['message'] = ['type' => 'warning', 'text' => 'Data tidak berubah. Mungkin sudah lunas.'];
                header("Location: kelola_member.php"); 
                exit();
            }
        } else {
            die("MySQL Error: " . mysqli_error($conn)); 
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Tagihan tidak ditemukan.'];
        header("Location: kelola_member.php");
        exit();
    }
}

// =======================================================
// 3. EDIT DATA MEMBER
// =======================================================
if (isset($_POST['edit_member'])) {
    $id = (int)$_POST['id'];
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number'] ?? '');

    if (!empty($phone_number)) {
        $checkPhone = mysqli_query($conn, "SELECT id FROM members WHERE phone_number = '$phone_number' AND id != '$id'");
        if (mysqli_num_rows($checkPhone) > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Nomor HP sudah digunakan member lain.'];
            header("Location: edit_member.php?id=$id");
            exit();
        }
    }
    
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    $query = "UPDATE members SET name='$name', phone_number='$phone_number', status='$status' WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Data member berhasil diperbarui.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal update: ' . mysqli_error($conn)];
    }
    header("Location: kelola_member.php");
    exit();
}

// =======================================================
// 4. HAPUS MEMBER
// =======================================================
if (isset($_POST['delete_member'])) {
    $id = $_POST['id'];
    $q_status = mysqli_query($conn, "SELECT status FROM members WHERE id = '$id'");
    $m = mysqli_fetch_assoc($q_status);

    if ($m && $m['status'] == 'aktif') {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal hapus. Member masih aktif.'];
    } else {
        if (mysqli_query($conn, "DELETE FROM members WHERE id='$id'")) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Member berhasil dihapus.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal hapus: ' . mysqli_error($conn)];
        }
    }
    header("Location: kelola_member.php");
    exit();
}

header("Location: dashboard.php");
exit();
?>