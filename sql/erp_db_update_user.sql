-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2026 at 07:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erp_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','admin','user') NOT NULL DEFAULT 'user',
  `employee_code` varchar(30) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `department` enum('Procurement','Inventory','Production','Sales','Finance','HR','Admin') DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `sss_no` varchar(20) DEFAULT NULL,
  `philhealth_no` varchar(20) DEFAULT NULL,
  `pagibig_no` varchar(20) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','Resigned') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `contact_number`, `role`, `employee_code`, `first_name`, `last_name`, `position`, `department`, `hire_date`, `birthdate`, `sss_no`, `philhealth_no`, `pagibig_no`, `basic_salary`, `status`, `created_at`, `updated_at`) VALUES
(1, 'user', '$2y$10$YqV89tYorbpnZFtYchE7le7CwoUkW1UpZhSA4MyhuI2FmEqhDwQya', 'user@gmail.com', '09977114098', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'Active', '2026-09-27 00:46:48', '2026-09-27 00:46:48'),
(2, 'admin', '$2y$10$ionF3tEUB.BRs7N9mFGY0.jPbwdEey1QTV3x59BmwC4uRsBT8wYeu', 'admin@gmail.com', '09977114098', 'super_admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'Active', '2026-09-27 00:57:21', '2026-09-27 00:57:21'),
(3, 'procurement', '$2y$10$d/VwA1RQpejZPvR0lDs8v.FBsyefX5/..XW7sMU3Tq/ObAJOJnibG', 'procurement@gmail.com', '099771123', 'admin', 'EMP-2026-0001', NULL, NULL, NULL, 'Procurement', NULL, NULL, NULL, NULL, NULL, 0.00, 'Active', '2026-10-04 03:57:23', '2026-10-04 04:55:14'),
(4, 'hr', '$2y$10$EKcmi4qDU3Fi58pQfzxgPumhqy.mrvdl0ZEuUhvNYbJ.Yn1Epmiu6', 'hr@gmail.com', NULL, 'admin', 'EMP-2026-0002', NULL, NULL, NULL, 'HR', NULL, NULL, NULL, NULL, NULL, 0.00, 'Active', '2026-10-04 04:16:51', '2026-10-04 04:18:42'),
(7, 'inventory', '$2y$10$XXtb62yOsWE21beq/ScuU.uj3VBZx8rGm5NRbsYRhVsRiP7JeZ3ZW', 'inventory@gmail.com', NULL, 'super_admin', 'EMP-2026-0003', NULL, NULL, NULL, 'Inventory', NULL, NULL, NULL, NULL, NULL, 0.00, 'Active', '2026-10-04 05:21:04', '2026-10-04 05:21:04'),
(8, 'production', '$2y$10$EWAlsQF/l0f/2iPhl1yOS.ttUZr.nK3yq6YC8uwETv3A7N84IKJkK', 'production@gmail.com', NULL, 'admin', 'EMP-2026-0004', NULL, NULL, NULL, 'Production', NULL, NULL, NULL, NULL, NULL, 0.00, 'Active', '2026-10-04 05:22:26', '2026-10-04 05:22:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `uniq_employee_code` (`employee_code`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
