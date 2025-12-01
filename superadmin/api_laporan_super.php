<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include '../koneksi.php'; // Sesuaikan path

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'super_admin') {
    echo json_encode(['status' => 'error']); exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filter_petugas = $_GET['filter_petugas'] ?? 'semua';
$laporan_aktif = $_GET['laporan'] ?? 'parkir';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$response = [
    'stats' => ['trx' => 0, 'money' => 0, 'avg' => 0, 'busiest' => '-'],
    'chart_trend' => ['labels' => [], 'data' => []],
    'chart_pie' => [0, 0], // [Member, Umum]
    'top_officers' => [],
    'html' => '',
    'pagination' => ''
];

// --- LOGIKA PARKIR ---
if ($laporan_aktif == 'parkir') {
    $where = "WHERE pt.check_out_time IS NOT NULL AND DATE(pt.check_out_time) BETWEEN '$start_date' AND '$end_date'";
    if ($filter_petugas !== 'semua') $where .= " AND pt.processed_by_petugas_id = '$filter_petugas'";

    // 1. UTAMA STATS
    $q_sum = mysqli_query($conn, "SELECT COUNT(id) as trx, SUM(total_fee) as uang FROM parking_transactions pt $where");
    $sum = mysqli_fetch_assoc($q_sum);
    $trx = $sum['trx'] ?? 0;
    $uang = $sum['uang'] ?? 0;

    $response['stats']['trx'] = number_format($trx);
    $response['stats']['money'] = number_format($uang, 0, ',', '.');
    $response['stats']['avg'] = ($trx > 0) ? number_format($uang / $trx, 0, ',', '.') : 0;

    // 2. JAM TERSIBUK
    $q_busy = mysqli_query($conn, "SELECT HOUR(check_out_time) as jam, COUNT(*) as total FROM parking_transactions pt $where GROUP BY jam ORDER BY total DESC LIMIT 1");
    $busy = mysqli_fetch_assoc($q_busy);
    $response['stats']['busiest'] = $busy ? $busy['jam'].":00" : "-";

    // 3. CHART TREND (Line)
    $q_trend = mysqli_query($conn, "SELECT DATE(check_out_time) as tgl, SUM(total_fee) as total FROM parking_transactions pt $where GROUP BY DATE(check_out_time) ORDER BY tgl ASC");
    while ($c = mysqli_fetch_assoc($q_trend)) {
        $response['chart_trend']['labels'][] = date('d M', strtotime($c['tgl']));
        $response['chart_trend']['data'][] = $c['total'];
    }

    // 4. CHART PIE (Member vs Umum - Berdasarkan Uang)
    // Note: Member biasanya gratis (0), jadi kita hitung jumlah transaksi saja untuk pie chart member
    $q_pie_mem = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) as total FROM parking_transactions pt $where AND member_id IS NOT NULL"));
    $q_pie_non = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) as total FROM parking_transactions pt $where AND member_id IS NULL"));
    $response['chart_pie'] = [$q_pie_mem['total'], $q_pie_non['total']];

    // 5. TOP OFFICERS
    $q_top = mysqli_query($conn, "SELECT u.name, COUNT(pt.id) as total_trx
                                  FROM parking_transactions pt
                                  JOIN users u ON pt.processed_by_petugas_id = u.id
                                  $where
                                  GROUP BY u.name
                                  ORDER BY total_trx DESC LIMIT 3");
    while($top = mysqli_fetch_assoc($q_top)) {
        $response['top_officers'][] = $top;
    }

    // 6. DATA TABLE
    $query = "SELECT pt.*, u.name as petugas_name, m.name as member_name
              FROM parking_transactions pt
              LEFT JOIN users u ON pt.processed_by_petugas_id = u.id
              LEFT JOIN members m ON pt.member_id = m.id
              $where ORDER BY pt.check_out_time DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tipe = $row['member_id'] ? '<span class="text-xs font-bold text-pink-600 bg-pink-100 px-2 py-1 rounded">MEMBER</span>' : '<span class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded">UMUM</span>';
            $response['html'] .= '
            <tr class="hover:bg-slate-50 border-b">
                <td class="px-6 py-4">'.$tipe.'</td>
                <td class="px-6 py-4">
                    <div class="font-bold text-slate-800">'.htmlspecialchars($row['license_plate']).'</div>
                    <div class="text-xs text-slate-500">'.$row['parking_token'].'</div>
                </td>
                <td class="px-6 py-4 text-slate-600">'.date('d/m H:i', strtotime($row['check_out_time'])).'</td>
                <td class="px-6 py-4">'.htmlspecialchars($row['petugas_name'] ?? '-').'</td>
                <td class="px-6 py-4 text-right font-bold text-slate-800">Rp '.number_format($row['total_fee'],0,',','.').'</td>
            </tr>';
        }
    } else {
        $response['html'] = '<tr><td colspan="5" class="text-center py-8 text-slate-400">Data tidak ditemukan.</td></tr>';
    }
}
// --- LOGIKA LANGGANAN (Sederhana) ---
else {
    $where = "WHERE mb.status = 'lunas' AND DATE(mb.payment_date) BETWEEN '$start_date' AND '$end_date'";
    if ($filter_petugas !== 'semua') $where .= " AND mb.processed_by_petugas_id = '$filter_petugas'";

    $q_sum = mysqli_query($conn, "SELECT COUNT(id) as trx, SUM(amount) as uang FROM member_billings mb $where");
    $sum = mysqli_fetch_assoc($q_sum);
    $response['stats']['trx'] = number_format($sum['trx']);
    $response['stats']['money'] = number_format($sum['uang'] ?? 0, 0, ',', '.');

    $query = "SELECT mb.*, m.name as m_name, u.name as p_name FROM member_billings mb
              JOIN members m ON mb.member_id = m.id
              LEFT JOIN users u ON mb.processed_by_petugas_id = u.id
              $where ORDER BY mb.payment_date DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $response['html'] .= '
            <tr class="hover:bg-slate-50 border-b">
                <td class="px-6 py-4 font-bold">'.htmlspecialchars($row['m_name']).'</td>
                <td class="px-6 py-4">'.date('F Y', strtotime($row['billing_period'])).'</td>
                <td class="px-6 py-4">'.date('d/m/Y H:i', strtotime($row['payment_date'])).'</td>
                <td class="px-6 py-4">'.htmlspecialchars($row['p_name'] ?? '-').'</td>
                <td class="px-6 py-4 text-right font-bold text-green-600">Rp '.number_format($row['amount'],0,',','.').'</td>
            </tr>';
        }
    } else {
        $response['html'] = '<tr><td colspan="5" class="text-center py-8 text-slate-400">Tidak ada data.</td></tr>';
    }
}

// Pagination
$total_pages = ceil($trx / $limit);
$prev = $page > 1 ? $page - 1 : 1;
$next = $page < $total_pages ? $page + 1 : $total_pages;
if($total_pages > 1) {
    $response['pagination'] = '<div class="flex justify-between mt-4 px-4"><button onclick="changePage('.$prev.')" class="px-3 py-1 border rounded hover:bg-gray-100" '.($page<=1?'disabled':'').'>Prev</button><span class="text-sm text-gray-500">Hal '.$page.'/'.$total_pages.'</span><button onclick="changePage('.$next.')" class="px-3 py-1 border rounded hover:bg-gray-100" '.($page>=$total_pages?'disabled':'').'>Next</button></div>';
}

echo json_encode($response);
?>