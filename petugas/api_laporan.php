<?php
// File: api_laporan.php
// SIMPAN DI FOLDER YANG SAMA DENGAN laporan_petugas.php

session_start();
// Matikan error display agar tidak merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

include '../koneksi.php';

header('Content-Type: application/json');

// 1. Keamanan: Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$petugas_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filter_tipe = $_GET['filter_tipe'] ?? 'semua';
$laporan_aktif = $_GET['laporan'] ?? 'parkir';

// --- PAGINATION SETUP ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah baris per halaman
$offset = ($page - 1) * $limit;

$response = [
    'stats' => ['trx' => 0, 'money' => 0],
    'chart' => ['labels' => [], 'data' => []],
    'html' => '',
    'pagination' => ''
];

try {
    if ($laporan_aktif == 'parkir') {
        // --- LOGIKA PARKIR ---
        $where = "WHERE pt.processed_by_petugas_id = '$petugas_id' AND DATE(pt.check_out_time) BETWEEN '$start_date' AND '$end_date'";

        if ($filter_tipe === 'member') $where .= " AND pt.member_id IS NOT NULL";
        elseif ($filter_tipe === 'umum') $where .= " AND pt.member_id IS NULL";

        // A. Hitung Total (Stats)
        $q_sum = mysqli_query($conn, "SELECT COUNT(id) as trx, SUM(total_fee) as uang FROM parking_transactions pt $where");
        $sum = mysqli_fetch_assoc($q_sum);
        $response['stats']['trx'] = number_format($sum['trx']);
        $response['stats']['money'] = number_format($sum['uang'] ?? 0, 0, ',', '.');
        $total_rows = $sum['trx'];

        // B. Data Grafik
        $q_chart = mysqli_query($conn, "SELECT DATE(check_out_time) as tgl, SUM(total_fee) as total FROM parking_transactions pt $where GROUP BY DATE(check_out_time) ORDER BY tgl ASC");
        while ($c = mysqli_fetch_assoc($q_chart)) {
            $response['chart']['labels'][] = date('d M', strtotime($c['tgl']));
            $response['chart']['data'][] = $c['total'];
        }

        // C. Data Tabel
        $query = "SELECT pt.*, m.name as member_name FROM parking_transactions pt LEFT JOIN members m ON pt.member_id = m.id $where ORDER BY pt.check_out_time DESC LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $jenis = $row['member_id']
                    ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Member</span>'
                    : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">Umum</span>';

                $response['html'] .= '
                <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                    <td class="px-6 py-4">'.$jenis.'</td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-slate-900">'.htmlspecialchars($row['license_plate']).'</div>
                        <div class="text-xs text-slate-500">'.htmlspecialchars($row['parking_token']).'</div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">'.date('d/m H:i', strtotime($row['check_in_time'])).'</td>
                    <td class="px-6 py-4 text-slate-600">'.date('d/m H:i', strtotime($row['check_out_time'])).'</td>
                    <td class="px-6 py-4 text-right font-medium text-slate-900">Rp '.number_format($row['total_fee'], 0, ',', '.').'</td>
                </tr>';
            }
        } else {
            $response['html'] = '<tr><td colspan="5" class="text-center py-8 text-gray-400 italic">Tidak ada data ditemukan.</td></tr>';
        }

    } else {
        // --- LOGIKA LANGGANAN ---
        $where = "WHERE mb.processed_by_petugas_id = '$petugas_id' AND mb.status = 'lunas' AND DATE(mb.payment_date) BETWEEN '$start_date' AND '$end_date'";

        $q_sum = mysqli_query($conn, "SELECT COUNT(id) as trx, SUM(amount) as uang FROM member_billings mb $where");
        $sum = mysqli_fetch_assoc($q_sum);
        $response['stats']['trx'] = number_format($sum['trx']);
        $response['stats']['money'] = number_format($sum['uang'] ?? 0, 0, ',', '.');
        $total_rows = $sum['trx'];

        $q_chart = mysqli_query($conn, "SELECT DATE(payment_date) as tgl, SUM(amount) as total FROM member_billings mb $where GROUP BY DATE(payment_date) ORDER BY tgl ASC");
        while ($c = mysqli_fetch_assoc($q_chart)) {
            $response['chart']['labels'][] = date('d M', strtotime($c['tgl']));
            $response['chart']['data'][] = $c['total'];
        }

        $query = "SELECT mb.*, m.name as member_name FROM member_billings mb JOIN members m ON mb.member_id = m.id $where ORDER BY mb.payment_date DESC LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response['html'] .= '
                <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                    <td class="px-6 py-4 font-medium text-slate-900">'.htmlspecialchars($row['member_name']).'</td>
                    <td class="px-6 py-4 text-slate-600">'.date('F Y', strtotime($row['billing_period'])).'</td>
                    <td class="px-6 py-4 text-slate-600">'.date('d/m/Y H:i', strtotime($row['payment_date'])).'</td>
                    <td class="px-6 py-4 text-slate-600">'.htmlspecialchars($_SESSION['user_name']).'</td>
                    <td class="px-6 py-4 text-right font-medium text-green-600">Rp '.number_format($row['amount'], 0, ',', '.').'</td>
                </tr>';
            }
        } else {
            $response['html'] = '<tr><td colspan="5" class="text-center py-8 text-gray-400 italic">Tidak ada data pembayaran.</td></tr>';
        }
    }

    // --- PAGINATION BUTTONS ---
    $total_pages = ceil($total_rows / $limit);
    $prev = $page > 1 ? $page - 1 : 1;
    $next = $page < $total_pages ? $page + 1 : $total_pages;

    if ($total_pages > 1) {
        $response['pagination'] = '
        <div class="flex justify-between items-center mt-4 px-2">
            <span class="text-sm text-slate-500">Hal '.$page.' dari '.$total_pages.'</span>
            <div class="flex gap-2">
                <button type="button" onclick="changePage('.$prev.')" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 text-sm disabled:opacity-50" '.($page <= 1 ? 'disabled' : '').'>Prev</button>
                <button type="button" onclick="changePage('.$next.')" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 text-sm disabled:opacity-50" '.($page >= $total_pages ? 'disabled' : '').'>Next</button>
            </div>
        </div>';
    }

} catch (Exception $e) {
    $response['html'] = '<tr><td colspan="5" class="text-center text-red-500">Error: '.$e->getMessage().'</td></tr>';
}

echo json_encode($response);
?>