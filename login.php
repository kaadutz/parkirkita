<?php
// Selalu mulai session di baris paling awal
session_start();

// Ganti 'koneksi.php' dengan path yang benar ke file koneksi Anda
// Contoh isi koneksi.php:
/*
<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "parkirrr";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
*/
include 'koneksi.php'; // Sesuaikan path ini jika perlu, misal 'koneksi.php'

// Inisialisasi variabel pesan error
$error_message = null;

// Arahkan jika pengguna sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'super_admin') {
        header("Location: superadmin/dashboard.php"); // Pastikan folder ini ada
        exit();
    } elseif ($_SESSION['user_role'] == 'petugas') {
        header("Location: petugas/dashboard.php"); // Pastikan folder ini ada
        exit();
    }
}

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Password dari form (teks biasa)

    if (empty($email) || empty($password)) {
        $error_message = "Email dan Password wajib diisi!";
    } else {
        // Kueri menargetkan tabel 'users' untuk semua role
        $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
             $error_message = "Terjadi kesalahan pada sistem. Coba lagi nanti.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                // PERINGATAN: Perbandingan Password Teks Biasa (TIDAK AMAN)
                // Metode ini digunakan sesuai dengan struktur database Anda saat ini.
                // Sangat disarankan untuk beralih ke password_hash() dan password_verify().
                if ($password === $user['password']) {
                    
                    // Jika password cocok, lanjutkan proses login
                    session_regenerate_id(true); // Mencegah session fixation

                    // Simpan data penting ke dalam sesi
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    $stmt->close();
                    $conn->close();

                    // Arahkan ke folder yang benar berdasarkan peran
                    if ($user['role'] == 'super_admin') {
                        header("Location: superadmin/dashboard.php");
                    } elseif ($user['role'] == 'petugas') {
                        header("Location: petugas/dashboard.php");
                    } else {
                        // Untuk role lain yang mungkin ada tapi tidak punya dashboard
                        $error_message = "Anda tidak memiliki akses ke sistem.";
                    }
                    exit();

                } else {
                    // Password tidak cocok
                    $error_message = "Kombinasi Email atau Password salah!";
                }
            } else {
                // Email tidak ditemukan
                $error_message = "Kombinasi Email atau Password salah!";
            }
            $stmt->close();
        }
    }
    // Tutup koneksi jika login gagal atau ada error
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Parkir Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-orange: #F57C00;
            --brand-pink: #D81B60;
            --brand-light-bg: #FFF8F2;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--brand-light-bg);
        }
        .form-input:focus {
            outline: none;
            border-color: var(--brand-orange) !important;
            box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.4) !important;
        }
        .btn-brand {
            background-color: var(--brand-orange); color: white; font-weight: 700; padding: 0.8rem 1.5rem;
            border-radius: 0.5rem; transition: all 0.2s ease-in-out; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-brand:hover { background-color: #E67E22; transform: translateY(-1px); box-shadow: 0 6px 10px rgba(0, 0, 0, 0.12); }
        .btn-brand:focus { outline: none; box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.4), 0 4px 6px rgba(0, 0, 0, 0.1); }
        .message-box { display: flex; align-items: flex-start; padding: 0.8rem 1rem; margin-bottom: 1.25rem; border-radius: 0.5rem; border-width: 1px; font-size: 0.875rem; font-weight: 500; }
        .message-box i { margin-right: 0.75rem; font-size: 1.1rem; flex-shrink: 0; margin-top: 2px; }
        .message-error { color: #991b1b; background-color: #fee2e2; border-color: #fecaca; }
        .decorative-panel { background: linear-gradient(145deg, var(--brand-pink) 0%, var(--brand-orange) 100%); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-xl flex flex-col md:flex-row w-full max-w-4xl overflow-hidden my-8">

        <!-- Bagian Kiri (Dekoratif) -->
        <div class="w-full md:w-1/2 decorative-panel p-8 md:p-12 flex flex-col justify-center items-center text-center text-white relative">
            <div class="mb-8"> <i class="fas fa-parking fa-4x text-white opacity-70"></i> </div>
            <h1 class="text-3xl md:text-4xl font-extrabold mb-3 leading-tight tracking-tight"> Parkir Kita<span style="color: var(--brand-orange); text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">.</span> </h1>
            <p class="text-base md:text-lg font-medium opacity-90 max-w-sm"> Akses sistem manajemen parkir Anda untuk mengelola transaksi dengan mudah. </p>
            <div class="absolute bottom-4 left-4 text-white opacity-20 text-6xl -rotate-12"> <i class="fas fa-car"></i> </div>
            <div class="absolute top-4 right-4 text-white opacity-20 text-5xl rotate-12"> <i class="fas fa-receipt"></i> </div>
        </div>

        <!-- Bagian Kanan (Form Login) -->
        <div class="w-full md:w-1/2 p-8 md:p-10 lg:p-12 flex flex-col justify-center">
            <div class="text-center mb-8">
                 <span class="text-3xl font-bold tracking-tight" style="color: var(--brand-orange);">Parkir</span>
                 <span class="text-3xl font-bold tracking-tight" style="color: var(--brand-pink);">Kita</span>
             </div>
            <h2 class="text-xl font-semibold text-gray-700 mb-6 text-center">Silakan Login</h2>

            <?php if ($error_message): ?>
                <div class="message-box message-error" role="alert">
                    <i class="fas fa-times-circle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-1">Email Address</label>
                    <div class="relative rounded-md shadow-sm">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-envelope text-gray-400"></i></div>
                         <input type="email" id="email" name="email" class="form-input block w-full pl-10 p-3 border border-gray-300 rounded-md transition duration-150 ease-in-out focus:ring-1 sm:text-sm" placeholder="anda@email.com" required>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                     <div class="relative rounded-md shadow-sm">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-400"></i></div>
                        <input type="password" id="password" name="password" class="form-input block w-full pl-10 p-3 border border-gray-300 rounded-md transition duration-150 ease-in-out focus:ring-1 sm:text-sm" placeholder="********" required>
                    </div>
                </div>

                 <div class="flex items-center justify-end text-sm mt-6">
                    <a href="forgot_password.php" class="font-medium hover:underline" style="color: var(--brand-orange);">
                        Lupa Password?
                    </a>
                </div>

                <button type="submit" class="w-full btn-brand mt-6">
                    <i class="fas fa-sign-in-alt mr-2"></i> LOGIN SEKARANG
                </button>
            </form>
        </div>
    </div>
</body>
</html>