# Alur Sistem ParkirKita

Berikut adalah dokumentasi alur kerja sistem ParkirKita, mencakup proses Login, Operasional Petugas/Admin, dan Alur Masuk Pelanggan.

## Diagram Flowchart

```mermaid
graph TD
    %% Nodes Utama
    Start((Mulai)) --> Landing[Landing Page<br/>landing.php]
    DB[(Database MySQL)]

    %% --- ALUR LOGIN (Admin & Petugas) ---
    Landing -->|Login Staff| Login[Halaman Login<br/>login.php]
    Login -->|Input Email & Password| Auth{Cek Validitas}

    %% LOGIKA GAGAL LOGIN
    Auth -- Gagal --> Error[Tampilkan Pesan Error]
    Error --> Login

    %% LOGIKA SUKSES
    Auth -- Sukses --> RoleCheck{Cek Role}

    RoleCheck -- Super Admin --> SADash[Dashboard Superadmin]
    RoleCheck -- Petugas --> PDash[Dashboard Petugas]

    %% --- ALUR SUPERADMIN ---
    subgraph Superadmin Area
    SADash -->|Lihat Statistik| SA_Stats[Statistik Real-time]
    SADash -->|Kelola Akun| SA_Staff[Kelola Petugas<br/>CRUD User]
    SADash -->|Laporan| SA_Report[Laporan Lengkap]
    SA_Staff & SA_Report -.-> DB
    end

    %% --- ALUR PETUGAS ---
    subgraph Petugas Area
    PDash -->|Transaksi Keluar| P_Checkout[Checkout Parkir]
    PDash -->|Kelola Member| P_Member[Manajemen Member]
    PDash -->|Tiket Hilang| P_Lost[Proses Tiket Hilang]

    P_Checkout -->|Scan Tiket/Kartu| P_Calc[Hitung Durasi & Biaya]
    P_Calc -->|Bayar| P_Struk[Cetak Struk]
    P_Struk -.-> DB

    P_Member -->|Tambah/Edit/Bayar| DB
    P_Lost -->|Denda + Bayar| DB
    end

    %% --- ALUR PELANGGAN (Masuk Parkir) ---
    subgraph Gerbang Masuk
    Landing -->|Masuk Parkir| Gate[Interface Gerbang<br/>pelanggan/index.php]
    Gate -->|Pilih| GateOpt{Tipe Pengunjung}

    %% Non-Member
    GateOpt -- Umum --> GenTicket[Cetak Tiket]
    GenTicket -->|Generate Token| ProcIn[Proses Masuk]
    ProcIn -->|Simpan Data| DB
    ProcIn --> Print[Cetak Kertas Tiket]

    %% Member
    GateOpt -- Member --> ScanCard[Scan Kartu Member]
    ScanCard -->|Validasi Aktif| ProcMember[Cek Member]
    ProcMember -.-> DB
    ProcMember -- Valid --> Success[Gerbang Terbuka]
    ProcMember -- Tidak Valid/Expired --> Gate
    end
```

## Penjelasan Alur Sistem

### 1. Alur Login & Autentikasi
*   **Akses Awal:** Pengguna mengakses `landing.php` yang menampilkan informasi umum.
*   **Login (`login.php`):** Staff (Petugas/Admin) memasukkan email dan password.
*   **Logika Validasi:**
    *   **Jika Gagal:** Sistem akan menampilkan pesan error "Kombinasi Email atau Password salah!" dan **tetap berada di halaman login** agar pengguna dapat mencoba lagi.
    *   **Jika Sukses:** Sistem mengarahkan pengguna ke dashboard sesuai perannya (`superadmin` atau `petugas`).

### 2. Alur Pelanggan (Masuk Parkir)
Pelanggan berinteraksi dengan Kiosk/Gerbang di `pelanggan/index.php`:
*   **Pengunjung Umum:** Menekan tombol "Cetak Tiket". Sistem membuat token unik, mencatat waktu masuk, dan mencetak tiket.
*   **Member:** Melakukan scan kartu. Sistem memvalidasi status member. Jika aktif, palang dibuka dan transaksi dicatat.

### 3. Alur Petugas (Operasional)
Petugas mengelola transaksi di `petugas/dashboard.php`:
*   **Checkout:** Memindai tiket keluar. Sistem menghitung durasi dan biaya secara otomatis.
*   **Manajemen Member:** Mendaftarkan dan memperbarui data member.
*   **Tiket Hilang:** Memproses denda khusus bagi pengunjung yang kehilangan tiket.

### 4. Alur Superadmin (Manajemen)
Superadmin memiliki kontrol penuh di `superadmin/dashboard.php`:
*   **Monitoring:** Melihat statistik pendapatan dan okupansi parkir real-time.
*   **Manajemen User:** Mengelola akun petugas yang memiliki akses login.
*   **Laporan:** Mengunduh laporan keuangan dan aktivitas operasional.
