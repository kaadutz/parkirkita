<?php
// Langkah 1: Selalu mulai sesi di awal script
// Ini penting agar PHP tahu sesi mana yang akan dihancurkan.
session_start();

// Langkah 2: Menghapus semua data dari variabel global $_SESSION
// Ini akan mengosongkan array sesi, menghapus semua data seperti 'user_id', 'user_name', dll.
$_SESSION = array();

// Langkah 3: Menghancurkan sesi itu sendiri
// Ini akan menghapus ID sesi dari sisi server.
session_destroy();

// Langkah 4 (Opsional tapi Direkomendasikan): Menghapus cookie sesi
// Ini memaksa browser untuk "melupakan" sesi tersebut dari sisinya.
// Berguna untuk keamanan tambahan.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Langkah 5: Mengarahkan pengguna kembali ke halaman login
// Setelah sesi hancur, pengguna tidak lagi diautentikasi.
header("Location: landing.php");
exit; // Pastikan tidak ada kode lain yang dieksekusi setelah redirect.
?>