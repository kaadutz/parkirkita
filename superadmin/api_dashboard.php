<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include '../koneksi.php';

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$response = [
    'stats' => ['trx' => 0, 'money' => 0],
    'chart_trend' => ['labels' => [], 'data' => []]
];

// Base Condition: Transactions that are completed (have check_out_time)
$where = "WHERE check_out_time IS NOT NULL AND DATE(check_out_time) BETWEEN '$start_date' AND '$end_date'";

// 1. MAIN STATS (Total Income & Transactions)
$q_sum = mysqli_query($conn, "SELECT COUNT(id) as trx, SUM(total_fee) as uang FROM parking_transactions $where");
if ($q_sum) {
    $sum = mysqli_fetch_assoc($q_sum);
    $response['stats']['trx'] = number_format($sum['trx'] ?? 0);
    $response['stats']['money'] = number_format($sum['uang'] ?? 0, 0, ',', '.');
}

// 2. CHART DATA (Income Trend per Day)
$q_trend = mysqli_query($conn, "SELECT DATE(check_out_time) as tgl, SUM(total_fee) as total
                                FROM parking_transactions
                                $where
                                GROUP BY DATE(check_out_time)
                                ORDER BY tgl ASC");

if ($q_trend) {
    while ($c = mysqli_fetch_assoc($q_trend)) {
        $response['chart_trend']['labels'][] = date('d M', strtotime($c['tgl']));
        $response['chart_trend']['data'][] = (int)$c['total'];
    }
}

echo json_encode($response);
?>