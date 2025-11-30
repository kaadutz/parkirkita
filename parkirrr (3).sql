-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 01:44 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkirrr`
--

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_card_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `join_date` date NOT NULL,
  `status` enum('aktif','tidak_aktif','ditangguhkan') NOT NULL DEFAULT 'aktif',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_card_id`, `name`, `phone_number`, `join_date`, `status`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(9, 'MBR1763562284', 'raka', '081807852840', '2025-11-19', 'aktif', 2, '2025-11-19 14:24:44', '2025-11-19 14:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `member_billings`
--

CREATE TABLE `member_billings` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `billing_period` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('belum_lunas','lunas') NOT NULL DEFAULT 'belum_lunas',
  `payment_date` datetime DEFAULT NULL,
  `cash_paid` decimal(10,2) DEFAULT NULL,
  `change_due` decimal(10,2) DEFAULT NULL,
  `processed_by_petugas_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `member_billings`
--

INSERT INTO `member_billings` (`id`, `member_id`, `billing_period`, `amount`, `status`, `payment_date`, `cash_paid`, `change_due`, `processed_by_petugas_id`, `created_at`, `updated_at`) VALUES
(19, 9, '2025-11-01', 150000.00, 'lunas', '2025-11-19 15:24:44', 150000.00, 0.00, 2, '2025-11-19 14:24:44', '2025-11-19 14:24:44'),
(20, 9, '2025-12-01', 150000.00, 'lunas', '2025-12-19 15:25:03', 150000.00, 0.00, 2, '2025-12-19 14:24:57', '2025-12-19 14:25:03');

-- --------------------------------------------------------

--
-- Table structure for table `parking_transactions`
--

CREATE TABLE `parking_transactions` (
  `id` int(11) NOT NULL,
  `parking_token` varchar(50) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `vehicle_category` enum('motor','mobil') DEFAULT NULL,
  `check_in_time` datetime NOT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `cash_paid` decimal(10,2) DEFAULT NULL,
  `change_due` decimal(10,2) DEFAULT NULL,
  `processed_by_petugas_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `parking_transactions`
--

INSERT INTO `parking_transactions` (`id`, `parking_token`, `member_id`, `license_plate`, `vehicle_category`, `check_in_time`, `check_out_time`, `total_fee`, `cash_paid`, `change_due`, `processed_by_petugas_id`, `created_at`) VALUES
(11, '176299517068', NULL, '', NULL, '2025-11-13 01:52:50', NULL, NULL, NULL, NULL, NULL, '2025-11-13 00:52:50'),
(12, '176353345707', NULL, NULL, NULL, '2025-11-19 07:24:17', NULL, NULL, NULL, NULL, NULL, '2025-11-19 06:24:17'),
(13, '176359752206', NULL, NULL, NULL, '2025-11-20 01:12:02', NULL, NULL, NULL, NULL, NULL, '2025-11-20 00:12:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','petugas') NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `profile_photo`, `reset_token`, `reset_token_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'nn', 'rakaanugrah2012@gmail.com', '111111', 'super_admin', 'user_1_1763039094.JPG', NULL, NULL, '2025-11-12 02:10:51', '2025-11-19 06:27:32'),
(2, 'k', 'raka.anugrah1561@smk.belajar.id', '111111', 'petugas', NULL, NULL, NULL, '2025-11-12 02:11:30', '2025-11-19 06:25:53'),
(3, 'raka', 'raka@gmail.com', '2', 'petugas', 'user_1762995927_IMG_6968.JPG', NULL, NULL, '2025-11-13 01:05:27', '2025-11-13 01:05:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_card_id` (`member_card_id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `member_billings`
--
ALTER TABLE `member_billings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_id` (`member_id`,`billing_period`),
  ADD KEY `processed_by_petugas_id` (`processed_by_petugas_id`);

--
-- Indexes for table `parking_transactions`
--
ALTER TABLE `parking_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parking_token` (`parking_token`),
  ADD KEY `processed_by_petugas_id` (`processed_by_petugas_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `member_billings`
--
ALTER TABLE `member_billings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `parking_transactions`
--
ALTER TABLE `parking_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `member_billings`
--
ALTER TABLE `member_billings`
  ADD CONSTRAINT `member_billings_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_billings_ibfk_2` FOREIGN KEY (`processed_by_petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `parking_transactions`
--
ALTER TABLE `parking_transactions`
  ADD CONSTRAINT `parking_transactions_ibfk_1` FOREIGN KEY (`processed_by_petugas_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `parking_transactions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
