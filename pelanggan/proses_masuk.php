<?php
session_start();
include '../koneksi.php'; 

if (!isset($conn) || !isset($_POST['action'])) {
    header('Location: index.php');
    exit();
}
$action = $_POST['action'];

if ($action == 'cetak_tiket') {
    $parking_token = time() . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
    $check_in_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO parking_transactions (parking_token, check_in_time) VALUES (?, ?)");
    $stmt->bind_param("ss", $parking_token, $check_in_time);
    if ($stmt->execute()) {
        $_SESSION['tiket_data'] = ['token' => $parking_token, 'waktu_masuk' => $check_in_time];
        header('Location: cetak.php');
        exit();
    } else { /* ... error handling ... */ }
}

if ($action == 'scan_member') {
    $member_card_id = trim($_POST['member_card_id']);
    if (empty($member_card_id)) { /* ... error handling ... */ }

    $stmt = $conn->prepare("SELECT id, name FROM members WHERE member_card_id = ? AND status = 'aktif'");
    $stmt->bind_param("s", $member_card_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $id_member_masuk = $member['id'];
        $member_name = $member['name'];

        $stmt_check = $conn->prepare("SELECT id FROM parking_transactions WHERE member_id = ? AND check_out_time IS NULL");
        $stmt_check->bind_param("i", $id_member_masuk);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['pesan'] = "Member " . htmlspecialchars($member_name) . " masih memiliki transaksi parkir aktif!";
            $_SESSION['pesan_tipe'] = "gagal";
            header('Location: index.php');
            exit();
        }
        
        $parking_token = 'MBR' . time() . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        $check_in_time = date('Y-m-d H:i:s');

        // =======================================================
        //           PERUBAHAN UTAMA: ISI KOLOM BARU
        // =======================================================
        $stmt_insert = $conn->prepare(
            "INSERT INTO parking_transactions (parking_token, member_id, scanned_card_id, check_in_time) VALUES (?, ?, ?, ?)"
        );
        // 'siss' = string, integer, string, string
        $stmt_insert->bind_param("siss", $parking_token, $id_member_masuk, $member_card_id, $check_in_time);
        
        if ($stmt_insert->execute()) {
            $_SESSION['pesan'] = "Selamat Datang, " . htmlspecialchars($member_name) . "! Silakan masuk.";
            $_SESSION['pesan_tipe'] = "sukses";
        } else {
            $_SESSION['pesan'] = "Gagal mencatat transaksi member. Error: " . $stmt_insert->error;
            $_SESSION['pesan_tipe'] = "gagal";
        }
        $stmt_insert->close();
    } else {
        $_SESSION['pesan'] = "Kartu Member tidak valid atau tidak aktif.";
        $_SESSION['pesan_tipe'] = "gagal";
    }
    $stmt->close();
    header('Location: index.php');
    exit();
}
?>