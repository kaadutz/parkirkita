# Data Flow Diagram (DFD) Level 2 - Sistem ParkirKita

Dokumen ini menjabarkan DFD Level 2 untuk setiap proses utama dalam sistem ParkirKita. DFD Level 2 memberikan pandangan yang lebih rinci tentang bagaimana data mengalir dan diproses dalam sub-sistem.

## Simbol yang Digunakan
*   **Entitas Luar (External Entity):** Pengguna sistem (Pelanggan, Petugas, Superadmin).
*   **Proses (Process):** Aktivitas yang mengubah data.
*   **Data Store:** Tempat penyimpanan data (Tabel Database).
*   **Alur Data (Data Flow):** Perpindahan informasi antar komponen.

---

## 1. DFD Level 2: Proses 1.0 - Login & Autentikasi

Proses ini menangani validasi akses pengguna ke dalam sistem.

```mermaid
graph LR
    %% Entities
    User[User/Petugas/Admin]

    %% Processes
    P1_1((1.1 Input<br/>Kredensial))
    P1_2((1.2 Validasi<br/>User))
    P1_3((1.3 Cek<br/>Role))
    P1_4((1.4 Set<br/>Session))

    %% Data Stores
    DS_User[(DS Users)]

    %% Flows
    User -->|Email & Password| P1_1
    P1_1 -->|Data Login| P1_2
    P1_2 <-->|Cek Data User| DS_User
    P1_2 -->|Data Valid| P1_3
    P1_2 -->|Data Invalid (Error)| User

    P1_3 -->|Role: Superadmin| P1_4
    P1_3 -->|Role: Petugas| P1_4
    P1_4 -->|Akses Dashboard| User
```

**Penjelasan:**
*   **1.1 Input Kredensial:** User memasukkan email dan password di halaman login.
*   **1.2 Validasi User:** Sistem memeriksa kecocokan data dengan tabel `users`.
*   **1.3 Cek Role:** Jika valid, sistem memisahkan alur berdasarkan hak akses (Superadmin/Petugas).
*   **1.4 Set Session:** Sistem menyimpan ID dan Role ke dalam session browser.

---

## 2. DFD Level 2: Proses 2.0 - Transaksi Parkir

Proses inti yang mencakup kendaraan masuk (tiket/member) dan kendaraan keluar (pembayaran).

```mermaid
graph LR
    %% Entities
    Pelanggan[Pelanggan]
    Petugas[Petugas]

    %% Processes
    P2_1((2.1 Cetak<br/>Tiket Umum))
    P2_2((2.2 Scan<br/>Kartu Member))
    P2_3((2.3 Scan<br/>Tiket Keluar))
    P2_4((2.4 Hitung<br/>Biaya))
    P2_5((2.5 Proses<br/>Pembayaran))

    %% Data Stores
    DS_Trans[(DS Parking<br/>Transactions)]
    DS_Member[(DS Members)]

    %% FLOW MASUK (UMUM)
    Pelanggan -->|Request Tiket| P2_1
    P2_1 -->|Generate Token & Waktu| DS_Trans
    P2_1 -->|Tiket Fisik| Pelanggan

    %% FLOW MASUK (MEMBER)
    Pelanggan -->|Tap Kartu| P2_2
    P2_2 <-->|Validasi Status| DS_Member
    P2_2 -->|Simpan Log Masuk| DS_Trans
    P2_2 -->|Buka Gate| Pelanggan

    %% FLOW KELUAR
    Pelanggan -->|Serahkan Tiket/Kartu| Petugas
    Petugas -->|Input ID/Scan| P2_3
    P2_3 <-->|Ambil Data Masuk| DS_Trans
    P2_3 -->|Data Durasi| P2_4
    P2_4 -->|Total Biaya| Petugas

    Petugas -->|Input Uang| P2_5
    P2_5 -->|Update: Checkout Time<br/>& Payment| DS_Trans
    P2_5 -->|Struk & Kembalian| Pelanggan
```

**Penjelasan:**
*   **2.1 Cetak Tiket Umum:** Membuat record baru di `parking_transactions` dengan `parking_token` unik.
*   **2.2 Scan Kartu Member:** Memvalidasi status member di `members` sebelum mencatat transaksi masuk.
*   **2.4 Hitung Biaya:** Mengkalkulasi durasi (Waktu Keluar - Waktu Masuk). Gratis untuk member, tarif progresif untuk umum.
*   **2.5 Proses Pembayaran:** Mengupdate record transaksi dengan `check_out_time`, `total_fee`, dan `cash_paid`.

---

## 3. DFD Level 2: Proses 3.0 - Manajemen Member (Oleh Petugas)

Proses pengelolaan data anggota parkir langganan.

```mermaid
graph LR
    %% Entities
    Petugas[Petugas]

    %% Processes
    P3_1((3.1 Registrasi<br/>Member Baru))
    P3_2((3.2 Perpanjang<br/>Member))
    P3_3((3.3 Cetak<br/>Kartu))

    %% Data Stores
    DS_Member[(DS Members)]

    %% Flows
    Petugas -->|Data Diri & Plat| P3_1
    P3_1 -->|Simpan Data| DS_Member

    Petugas -->|ID Member & Biaya| P3_2
    P3_2 -->|Update Expired Date| DS_Member

    Petugas -->|Request Cetak| P3_3
    DS_Member -->|Data Member| P3_3
    P3_3 -->|Kartu Fisik| Petugas
```

---

## 4. DFD Level 2: Proses 4.0 - Manajemen Petugas (Oleh Superadmin)

Proses administrasi user yang dilakukan oleh Superadmin.

```mermaid
graph LR
    %% Entities
    Admin[Superadmin]

    %% Processes
    P4_1((4.1 Tambah<br/>Petugas))
    P4_2((4.2 Update<br/>Petugas))
    P4_3((4.3 Hapus<br/>Petugas))

    %% Data Stores
    DS_Users[(DS Users)]

    %% Flows
    Admin -->|Data Akun Baru| P4_1
    P4_1 -->|Insert User| DS_Users

    Admin -->|Data Update| P4_2
    P4_2 -->|Update User| DS_Users

    Admin -->|ID User| P4_3
    P4_3 -->|Delete User| DS_Users
```
