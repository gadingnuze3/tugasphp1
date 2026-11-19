-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 07:53 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_umkm`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Daging & Unggas Beku'),
(2, 'Olahan Ikan & Seafood'),
(3, 'Dimsum & Steamboat'),
(4, 'Camilan & Gorengan Beku');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) NOT NULL,
  `avg_daily_usage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expired_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_code`, `name`, `category_id`, `stock`, `min_stock`, `unit`, `avg_daily_usage`, `cost_per_unit`, `expired_date`) VALUES
(1, 'FRZ-01', 'Ayam Potong / Fillet Beku', 1, 16.00, 15.00, 'Kg', 6.00, 36000.00, '2026-12-28'),
(2, 'FRZ-02', 'Daging Sapi Slice / Giling', 1, 4.00, 10.00, 'Kg', 3.50, 95000.00, '2026-12-28'),
(3, 'FRZ-03', 'Chicken Nugget (500gr)', 1, 18.00, 10.00, 'Pack', 5.00, 28000.00, '2026-12-28'),
(4, 'FRZ-04', 'Sosis Sapi / Ayam (1000gr)', 1, 6.00, 12.00, 'Pack', 4.50, 42000.00, '2026-12-28'),
(5, 'FRZ-05', 'Bakso Sapi Halus / Urat', 1, 15.00, 8.00, 'Pack', 3.00, 32000.00, '2027-01-12'),
(6, 'FRZ-06', 'Otak-Otak Ikan', 2, 5.00, 12.00, 'Pack', 4.00, 18000.00, '2026-11-23'),
(7, 'FRZ-07', 'Pempek Lenjer & Kapal Selam', 2, 14.00, 8.00, 'Pack', 2.50, 35000.00, '2027-01-12'),
(8, 'FRZ-08', 'Dimsum Ayam Udang (Isi 20)', 3, 4.00, 10.00, 'Pack', 4.00, 38000.00, '2026-11-23'),
(9, 'FRZ-09', 'Dumpling Keju / Ayam (500gr)', 3, 12.00, 6.00, 'Pack', 2.50, 27000.00, '2027-01-12'),
(10, 'FRZ-10', 'Jamur Enoki Segar / Chilled', 3, 3.00, 10.00, 'Pack', 4.50, 6500.00, '2026-11-16'),
(11, 'FRZ-11', 'Cireng Bumbu Rujak', 4, 20.00, 10.00, 'Pack', 4.00, 14000.00, '2026-12-13'),
(12, 'FRZ-12', 'Risol Mayo Smoked Beef', 4, 5.00, 10.00, 'Pack', 3.50, 22000.00, '2026-11-23'),
(13, 'FRZ-13', 'Pastel Ayam Sayur Beku', 4, 12.00, 6.00, 'Pack', 2.00, 20000.00, '2026-12-13'),
(14, 'FRZ-14', 'Lumpia Semarang / Sayur', 4, 9.00, 6.00, 'Pack', 2.00, 19000.00, '2026-12-13'),
(15, 'FRZ-15', 'Kentang Goreng Shoestring (1Kg)', 4, 7.00, 15.00, 'Pack', 5.50, 29000.00, '2027-01-12'),
(16, 'KKS-16', 'chikuwa', 3, 8.00, 100.00, '1Kg', 5.00, 30000.00, '2026-11-28'),
(17, 'KKS-17', 'pentol ayam', 2, 25.00, 10.00, '20', 3.00, 30000.00, '2026-11-25');

-- --------------------------------------------------------

--
-- Table structure for table `stock_logs`
--

CREATE TABLE `stock_logs` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_logs`
--

INSERT INTO `stock_logs` (`id`, `inventory_id`, `type`, `quantity`, `notes`, `created_at`) VALUES
(1, 13, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-13 01:34:23'),
(2, 1, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:12'),
(3, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:14'),
(4, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:15'),
(5, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:15'),
(6, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:19'),
(7, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:20'),
(8, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:20'),
(9, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:21'),
(10, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:22'),
(11, 1, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:22'),
(12, 16, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:24'),
(13, 16, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:25'),
(14, 16, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:26'),
(15, 16, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:27'),
(16, 16, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:27'),
(17, 16, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:28'),
(18, 16, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:34'),
(19, 16, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:40'),
(20, 16, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-17 01:35:41'),
(21, 16, 'OUT', 1.00, 'Penjualan Cepat (-1)', '2026-11-17 01:35:44'),
(22, 17, 'IN', 1.00, 'Quick Restock (+1)', '2026-11-19 06:31:58'),
(23, 17, 'IN', 4.00, 'restock mingguan', '2026-11-19 06:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Owner','Kasir') NOT NULL DEFAULT 'Kasir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'owner', '$2y$10$IdxtWtZR33J8/abkdHYjtOmpRfXziQtPh9ckhimBUcWq.gsaL/oDy', 'Juragan KKS', 'Owner'),
(2, 'kasir', '$2y$10$IdxtWtZR33J8/abkdHYjtOmpRfXziQtPh9ckhimBUcWq.gsaL/oDy', 'Admin Kasir KKS Frozen', 'Kasir');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
