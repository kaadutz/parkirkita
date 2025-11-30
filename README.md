# ParkirKita - Smart Parking System

**ParkirKita** is a modern, web-based parking management system designed to streamline operations for parking lots, malls, and office buildings. It features dedicated interfaces for Customers (Pelanggan), Staff (Petugas), and Superadmins, ensuring a seamless experience from entry to exit.

## 🚀 Features

### 🏢 For Superadmin
- **Dashboard Analytics**: Real-time overview of revenue, transaction counts, and active staff. Includes interactive charts with custom date filtering.
- **Staff Management**: Add, edit, and manage parking attendants (Petugas).
- **Comprehensive Reports**: Export detailed transaction reports to PDF or Excel.

### 👮 For Staff (Petugas)
- **Transaction Processing**: Handle vehicle exits for regular visitors and members.
- **Lost Ticket Handling**: Specialized workflow to process penalties for lost tickets with fixed rates.
- **Member Management**: Register new members and handle subscription renewals.
- **Shift Reports**: View personal transaction history and daily earnings.

### 🚗 For Customers (Pelanggan)
- **Ticket Kiosk**: Self-service interface to generate parking barcodes/tickets upon entry.
- **Member Check-in**: Quick entry for registered members via card scan or ID input.

## 🛠️ Tech Stack

- **Backend**: Native PHP (Compatible with PHP 8.x)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, JavaScript
- **Styling**: [Tailwind CSS](https://tailwindcss.com/) (via CDN)
- **Icons**: [FontAwesome](https://fontawesome.com/) (via CDN)
- **Charts**: [Chart.js](https://www.chartjs.org/)

## 📂 Folder Structure

```
/
├── pelanggan/       # Customer-facing kiosk (Entry, Barcode Gen)
├── petugas/         # Staff dashboard (Transactions, Member Mgmt)
├── superadmin/      # Admin dashboard (Analytics, Staff Mgmt)
├── uploads/         # Storage for profile photos and assets
├── koneksi.php      # Database connection configuration
├── landing.php      # Main landing page
├── login.php        # Unified login for Staff and Superadmin
└── parkirrr (3).sql # Database schema import file
```

## ⚙️ Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/yourusername/parkirkita.git
    cd parkirkita
    ```

2.  **Database Configuration**
    - Create a new MySQL database (e.g., `parkirrr`).
    - Import the provided SQL file: `parkirrr (3).sql`.
    - Edit `koneksi.php` to match your database credentials:
      ```php
      $servername = "localhost";
      $username = "root";
      $password = "";
      $dbname = "parkirrr";
      ```

3.  **Run the Application**
    - Place the project folder in your web server's root directory (e.g., `htdocs` for XAMPP or `/var/www/html` for Apache).
    - Access the application via your browser:
      - **Landing Page**: `http://localhost/parkirkita/landing.php`
      - **Login**: `http://localhost/parkirkita/login.php`

## 📜 License

This project is open-source and available for educational and commercial use.

---
&copy; 2025 ParkirKita System. All rights reserved.
