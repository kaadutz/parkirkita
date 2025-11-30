<?php
session_start();
// Ganti 'koneksi.php' dengan 'koneksi.php' jika nama file Anda berbeda
include 'koneksi.php'; 
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Menentukan mode tampilan halaman
$mode = 'enter_email';
if (isset($_SESSION['reset_token_verified']) && $_SESSION['reset_token_verified'] === true) {
    $mode = 'reset_password';
} elseif (isset($_SESSION['reset_email_pending'])) {
    $mode = 'enter_token';
}

$error_message = '';

// Fungsi untuk membatalkan proses
if (isset($_GET['cancel'])) {
    unset($_SESSION['reset_email_pending'], $_SESSION['reset_token_verified'], $_SESSION['reset_user_email']);
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Langkah 1: Pengguna mengirimkan email ---
    if (isset($_POST['email_submit'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) { 
            $error_message = "Format email tidak valid."; 
        } else {
            // Ganti '$koneksi' menjadi '$conn' jika variabel koneksi Anda adalah $conn
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email); 
            $stmt->execute(); 
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // Token 6 digit
                $token_hash = hash('sha256', $token);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
                $update_stmt->bind_param("sss", $token_hash, $expires_at, $email);
                if ($update_stmt->execute()) {
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP(); 
                        $mail->Host = 'smtp.gmail.com'; // Ganti dengan host SMTP Anda
                        $mail->SMTPAuth = true;
                        $mail->Username = 'rakacembol@gmail.com'; // Ganti dengan email Anda
                        $mail->Password = 'sixvaazbmlndzrtt'; // Ganti dengan App Password Anda
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                        $mail->Port = 465;
                        $mail->setFrom('no-reply@parkirkita.com', 'Admin ParkirKita');
                        $mail->addAddress($email); 
                        $mail->isHTML(true);
                        $mail->Subject = 'Kode Verifikasi Reset Password ParkirKita';
                        $mail->Body = "Gunakan kode <strong>{$token}</strong> untuk verifikasi reset password Anda. Kode ini berlaku 10 menit.";
                        $mail->send();
                        $_SESSION['reset_email_pending'] = $email;
                        header('Location: forgot_password.php'); 
                        exit;
                    } catch (Exception $e) { 
                        $error_message = "Gagal mengirim email verifikasi. Error: " . $mail->ErrorInfo; 
                    }
                }
            } else { 
                $error_message = "Email tidak ditemukan."; 
            }
        }
    }

    // --- Langkah 2: Pengguna mengirimkan token verifikasi ---
    if (isset($_POST['token_submit'])) {
        $mode = 'enter_token'; 
        $email = $_SESSION['reset_email_pending'] ?? ''; 
        $token_input = implode('', $_POST['token'] ?? []); // Menggabungkan 6 input menjadi satu
        if (empty($email) || strlen($token_input) < 6) { 
            $error_message = "Token 6 digit wajib diisi."; 
        } else {
            $token_hash_input = hash('sha256', $token_input);
            $stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE email = ? AND reset_token = ?");
            $stmt->bind_param("ss", $email, $token_hash_input); 
            $stmt->execute(); 
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (strtotime($user['reset_token_expires_at']) > time()) {
                    unset($_SESSION['reset_email_pending']); 
                    $_SESSION['reset_token_verified'] = true;
                    $_SESSION['reset_user_email'] = $email; 
                    header('Location: forgot_password.php'); 
                    exit;
                } else { 
                    $error_message = "Token sudah kedaluwarsa."; 
                }
            } else { 
                $error_message = "Token verifikasi salah."; 
            }
        }
    }

    // --- Langkah 3: Pengguna mengirimkan password baru (TANPA HASHING) ---
    if (isset($_POST['password_submit'])) {
        $mode = 'reset_password';
        $email = $_SESSION['reset_user_email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($email) || !isset($_SESSION['reset_token_verified'])) {
            session_destroy(); 
            header('Location: forgot_password.php'); 
            exit;
        }

        if (empty($password) || empty($password_confirm)) {
            $error_message = "Semua kolom password wajib diisi.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password minimal harus 6 karakter.";
        } elseif ($password !== $password_confirm) {
            $error_message = "Konfirmasi password tidak cocok.";
        } else {
            // PERINGATAN: Menyimpan password baru sebagai teks biasa (plain text).
            $new_password_plain = $password; 

            $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE email = ?");
            $update_stmt->bind_param("ss", $new_password_plain, $email);
            
            if ($update_stmt->execute()) {
                unset($_SESSION['reset_email_pending'], $_SESSION['reset_token_verified'], $_SESSION['reset_user_email']);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Password berhasil direset! Silakan login.'];
                header('Location: login.php');
                exit;
            } else {
                $error_message = "Gagal memperbarui password.";
            }
        }
    }
}

// Logika untuk indikator langkah
$step1_class = $step2_class = $step3_class = 'step-default';
$connector1_class = $connector2_class = '';
if ($mode === 'enter_email') { $step1_class = 'step-active'; } 
elseif ($mode === 'enter_token') { $step1_class = 'step-completed'; $step2_class = 'step-active'; $connector1_class = 'connector-completed'; } 
elseif ($mode === 'reset_password') { $step1_class = 'step-completed'; $step2_class = 'step-completed'; $step3_class = 'step-active'; $connector1_class = 'connector-completed'; $connector2_class = 'connector-completed'; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - ParkirKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .step-circle { transition: all 0.3s ease-in-out; }
        .step-connector { transition: background-color 0.3s ease-in-out; }
        .step-default .step-circle { border-color: #d1d5db; background-color: #fff; color: #9ca3af; }
        .step-active .step-circle { border-color: #F57C00; background-color: #F57C00; color: #fff; transform: scale(1.1); }
        .step-active .step-label { color: #F57C00; font-weight: 600; }
        .step-completed .step-circle { border-color: #16a34a; background-color: #16a34a; color: #fff; }
        .connector-completed { background-color: #16a34a; }
        .pin-input:focus { border-color: #F57C00; box-shadow: 0 0 0 2px rgba(245, 124, 0, 0.2); }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-indigo-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-slate-800">
                <i class="fas fa-parking text-orange-500"></i> Parkir<span class="text-pink-600">Kita</span>
            </h1>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center w-full mb-10">
            <div class="flex-1 text-center <?= $step1_class ?>">
                <div class="step-circle w-12 h-12 mx-auto rounded-full border-2 flex items-center justify-center font-bold text-lg"><?= ($mode !== 'enter_email') ? '<i class="fa fa-check"></i>' : '1' ?></div>
                <p class="step-label text-sm text-slate-500 mt-2">Email</p>
            </div>
            <div class="flex-auto border-t-2 transition-all duration-500 <?= $connector1_class ?>"></div>
            <div class="flex-1 text-center <?= $step2_class ?>">
                <div class="step-circle w-12 h-12 mx-auto rounded-full border-2 flex items-center justify-center font-bold text-lg"><?= ($mode === 'reset_password') ? '<i class="fa fa-check"></i>' : '2' ?></div>
                <p class="step-label text-sm text-slate-500 mt-2">Verifikasi</p>
            </div>
            <div class="flex-auto border-t-2 transition-all duration-500 <?= $connector2_class ?>"></div>
            <div class="flex-1 text-center <?= $step3_class ?>">
                <div class="step-circle w-12 h-12 mx-auto rounded-full border-2 flex items-center justify-center font-bold text-lg">3</div>
                <p class="step-label text-sm text-slate-500 mt-2">Password Baru</p>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <!-- Form Sections -->
        <?php if ($mode === 'enter_email'): ?>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-800">Lupa Password?</h2>
                <p class="text-slate-500 mt-2 mb-6">Jangan khawatir! Masukkan email Anda di bawah ini.</p>
            </div>
            <form action="forgot_password.php" method="POST" class="space-y-6">
                <div class="relative">
                    <i class="fa fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="email" name="email" class="w-full pl-12 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none" placeholder="Alamat Email Anda" required autofocus>
                </div>
                <button type="submit" name="email_submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg shadow-md transition-transform duration-200 hover:-translate-y-1">Kirim Kode Verifikasi</button>
            </form>
            <div class="text-center mt-6">
                <a href="login.php" class="text-sm font-medium text-orange-600 hover:underline">Kembali ke Halaman Login</a>
            </div>

        <?php elseif ($mode === 'enter_token'): ?>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-800">Periksa Email Anda</h2>
                <p class="text-slate-500 mt-2 mb-6">Kami telah mengirim kode 6 digit ke <br><strong><?= htmlspecialchars($_SESSION['reset_email_pending']) ?></strong>.</p>
            </div>
            <form action="forgot_password.php" method="POST">
                <div class="flex justify-center gap-2 mb-6" id="pin-container">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" name="token[]" maxlength="1" inputmode="numeric" class="pin-input w-12 h-14 text-center text-2xl font-bold border border-slate-300 rounded-lg outline-none" required>
                    <?php endfor; ?>
                </div>
                <button type="submit" name="token_submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg shadow-md transition-transform duration-200 hover:-translate-y-1">Verifikasi</button>
            </form>
            <div class="text-center mt-6">
                <a href="forgot_password.php?cancel=1" class="text-sm font-medium text-slate-500 hover:underline">Bukan email Anda?</a>
            </div>

        <?php elseif ($mode === 'reset_password'): ?>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-800">Atur Password Baru</h2>
                <p class="text-slate-500 mt-2 mb-6">Masukkan password baru yang kuat dan mudah diingat.</p>
            </div>
            <form action="forgot_password.php" method="POST" class="space-y-6">
                <div class="relative">
                    <i class="fa fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" id="password" name="password" class="w-full pl-12 pr-12 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none" placeholder="Password Baru" required autofocus>
                    <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-800" onclick="togglePassword('password')">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="relative">
                    <i class="fa fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" id="password_confirm" name="password_confirm" class="w-full pl-12 pr-12 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none" placeholder="Konfirmasi Password" required>
                    <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-800" onclick="togglePassword('password_confirm')">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <button type="submit" name="password_submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg shadow-md transition-transform duration-200 hover:-translate-y-1">Simpan Password Baru</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        const pinContainer = document.getElementById('pin-container');
        if (pinContainer) {
            const inputs = [...pinContainer.children];
            inputs[0].focus();
            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    if (input.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });
        }
    </script>
</body>
</html>