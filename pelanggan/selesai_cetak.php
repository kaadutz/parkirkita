<?php
// pelanggan/selesai_cetak.php
session_start();

// Clear the ticket data from the session
unset($_SESSION['tiket_data']);

// Redirect back to the main kiosk page
header('Location: index.php');
exit();