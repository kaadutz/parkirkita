<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include '../koneksi.php';

header('Content-Type: application/json');

// Security Check: Role Based Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Security: Sanitize Inputs to prevent SQL Injection
$start_date = mysqli_real_escape_string($conn, $_GET['start_date'] ?? date('Y-m-01'));
$end_date = mysqli_real_escape_string($conn, $_GET['end_date'] ?? date('Y-m-d'));

$response = [
    'stats' => ['trx' => 0, 'money' => 0],
    'chart_trend' => ['labels' => [], 'data' => []]
];

// 1. STATS (Transactions & Revenue)
// Use sanitized variables in query
$q_sum = mysqli_query($conn, "SELECT COUNT(id) as trx, SUM(total_fee) as uang FROM parking_transactions WHERE DATE(check_out_time) BETWEEN '$start_date' AND '$end_date'");
$sum = mysqli_fetch_assoc($q_sum);

$response['stats']['trx'] = number_format($sum['trx'] ?? 0);
$response['stats']['money'] = number_format($sum['uang'] ?? 0, 0, ',', '.');

// 2. CHART DATA (Daily Revenue) - Optimized with GROUP BY
// Fetch all data in one query instead of N+1 queries
$query_chart = "SELECT DATE(check_out_time) as tgl, SUM(total_fee) as total
                FROM parking_transactions
                WHERE DATE(check_out_time) BETWEEN '$start_date' AND '$end_date'
                GROUP BY tgl
                ORDER BY tgl ASC";
$result_chart = mysqli_query($conn, $query_chart);

// Store DB results in an associative array for easy lookup
$db_data = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $db_data[$row['tgl']] = (float)$row['total'];
}

// Generate continuous date range to fill gaps
$period = new DatePeriod(
     new DateTime($start_date),
     new DateInterval('P1D'),
     (new DateTime($end_date))->modify('+1 day')
);

foreach ($period as $dt) {
    $current_date = $dt->format("Y-m-d");
    $display_date = $dt->format("d M");

    $response['chart_trend']['labels'][] = $display_date;
    // Use data from DB if exists, otherwise 0
    $response['chart_trend']['data'][] = $db_data[$current_date] ?? 0;
}

echo json_encode($response);
?>