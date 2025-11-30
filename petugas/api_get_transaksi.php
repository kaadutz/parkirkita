<?php
header('Content-Type: application/json');
session_start();

// Keamanan dasar
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Akses tidak sah. Silakan login kembali.']);
    exit();
}

// Gunakan path absolut untuk keamanan dan keandalan
include __DIR__ . '/../koneksi.php';

// Cek koneksi database
if (!$conn) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Koneksi ke database gagal.']);
    exit();
}

$response = ['success' => false, 'message' => 'Parameter tidak valid.'];

// =======================================================
//   LOGIKA UNTUK TRANSAKSI NON-MEMBER (BERDASARKAN TOKEN)
// =======================================================
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    if (empty($token)) {
        $response['message'] = 'Token tidak boleh kosong.';
    } else {
        $query = "SELECT * FROM parking_transactions WHERE parking_token = '$token'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $transaction = mysqli_fetch_assoc($result);
            if ($transaction['member_id'] !== null) {
                $response['message'] = 'Token ini milik MEMBER. Gunakan menu Member.';
            } elseif ($transaction['check_out_time'] !== null) {
                $response['message'] = 'Tiket ini sudah diproses keluar.';
            } else {
                // Logika hitung biaya untuk non-member
                $check_in_time = new DateTime($transaction['check_in_time']);
                $current_time = new DateTime();
                $interval = $check_in_time->diff($current_time);
                $total_minutes = ($interval->d * 24 * 60) + ($interval->h * 60) + $interval->i;
                
                $total_fee = 0;
                if ($total_minutes > 10) {
                    $total_fee = 3000; // Biaya jam pertama
                    if ($total_minutes > 60) {
                        $remaining_minutes = $total_minutes - 60;
                        $additional_hours = ceil($remaining_minutes / 60);
                        $total_fee += $additional_hours * 2000; // Biaya jam berikutnya
                    }
                }
                
                $duration_formatted = '';
                if ($interval->d > 0) $duration_formatted .= $interval->d . ' hari ';
                if ($interval->h > 0) $duration_formatted .= $interval->h . ' jam ';
                $duration_formatted .= $interval->i . ' mnt';

                $response = [
                    'success' => true,
                    'data' => [
                        'id' => (int)$transaction['id'],
                        'check_in_formatted' => date('d M Y, H:i', strtotime($transaction['check_in_time'])),
                        'current_time_formatted' => date('d M Y, H:i'),
                        'duration_formatted' => trim($duration_formatted),
                        'total_fee' => (float)$total_fee,
                    ]
                ];
            }
        } else {
            $response['message'] = 'Token parkir tidak valid atau tidak ditemukan.';
        }
    }
}
// =======================================================
//   LOGIKA UNTUK TRANSAKSI MEMBER (BERDASARKAN CARD ID)
// =======================================================
elseif (isset($_GET['card_id'])) {
    $card_id = mysqli_real_escape_string($conn, $_GET['card_id']);
    if (empty($card_id)) {
        $response['message'] = 'ID Kartu tidak boleh kosong.';
    } else {
        // =================================================================
        // PERBAIKAN UTAMA DI SINI: MENCARI BERDASARKAN `scanned_card_id`
        // =================================================================
        $query_tx = "
            SELECT pt.*, m.name as member_name 
            FROM parking_transactions pt
            JOIN members m ON pt.member_id = m.id
            WHERE pt.scanned_card_id = '$card_id' 
              AND pt.check_out_time IS NULL 
            ORDER BY pt.check_in_time DESC
            LIMIT 1
        ";
        $result_tx = mysqli_query($conn, $query_tx);

        if ($result_tx && mysqli_num_rows($result_tx) > 0) {
            $transaction = mysqli_fetch_assoc($result_tx);
            
            $check_in_time = new DateTime($transaction['check_in_time']);
            $current_time = new DateTime();
            $interval = $check_in_time->diff($current_time);
            
            // Asumsi parkir harian untuk member adalah GRATIS
            $total_fee = 0;
            
            $duration_formatted = '';
            if ($interval->d > 0) $duration_formatted .= $interval->d . ' hari ';
            if ($interval->h > 0) $duration_formatted .= $interval->h . ' jam ';
            $duration_formatted .= $interval->i . ' mnt';

            $response = [
                'success' => true,
                'data' => [
                    'transaction_id' => (int)$transaction['id'],
                    'member_name' => $transaction['member_name'],
                    'check_in_formatted' => date('d M Y, H:i', strtotime($transaction['check_in_time'])),
                    'current_time_formatted' => date('d M Y, H:i'),
                    'duration_formatted' => trim($duration_formatted),
                    'total_fee' => $total_fee
                ]
            ];
        } else {
            $response['message'] = 'Tidak ada transaksi parkir aktif untuk kartu member ini.';
        }
    }
}

echo json_encode($response);
?>