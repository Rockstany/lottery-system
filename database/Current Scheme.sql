-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 11, 2026 at 06:09 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u717011923_gettoknow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text DEFAULT NULL,
  `feature_key` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action_type`, `action_description`, `feature_key`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 18:59:58'),
(2, 1, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-11-28 19:00:21'),
(3, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 19:00:31'),
(4, 1, 'user_status_change', 'User \'Albin Test\' (ID: 2) deactivated', NULL, '103.211.52.166', NULL, '2025-11-28 19:39:46'),
(5, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 19:40:29'),
(6, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:60a2:91f7:48df:72ec:20a8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 17:32:33'),
(7, 1, 'logout', 'User logged out', NULL, '2401:4900:1f39:60a2:91f7:48df:72ec:20a8', NULL, '2025-11-29 17:32:50'),
(8, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:60a2:91f7:48df:72ec:20a8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 17:33:02'),
(9, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:60a2:91f7:48df:72ec:20a8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 17:33:32'),
(10, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:60a2:91f7:48df:72ec:20a8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 17:56:50'),
(11, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 08:32:21'),
(12, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 08:33:21'),
(13, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 08:51:09'),
(14, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 09:05:32'),
(15, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:d59a:5a2d:60ac:6dd3', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.148 Mobile/15E148 Safari/604.1', '2025-11-30 09:41:24'),
(16, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:2d69:eb64:f669:f974', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.148 Mobile/15E148 Safari/604.1', '2025-11-30 16:48:38'),
(17, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 17:07:07'),
(18, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 17:45:08'),
(19, 1, 'logout', 'User logged out', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', NULL, '2025-11-30 17:47:22'),
(20, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 17:48:05'),
(21, 1, 'user_status_change', 'User \'A.D.Thomas\' (ID: 4) deactivated', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', NULL, '2025-11-30 17:48:31'),
(22, 1, 'logout', 'User logged out', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', NULL, '2025-11-30 17:49:55'),
(23, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 17:49:59'),
(24, 5, 'logout', 'User logged out', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', NULL, '2025-11-30 17:50:22'),
(25, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 17:50:25'),
(26, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:adc1:1727:25f5:d278', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 17:50:57'),
(27, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5c:4ef9:1d53:b12f:c9eb:341f', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36', '2025-11-30 18:16:30'),
(28, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 12:14:23'),
(29, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 13:03:01'),
(30, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:03:04'),
(31, 1, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 13:10:30'),
(32, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:10:41'),
(33, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 13:35:49'),
(34, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:35:53'),
(35, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-12-02 13:37:16'),
(36, 1, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 13:38:16'),
(37, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:38:20'),
(38, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 13:57:51'),
(39, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:57:55'),
(40, 1, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 13:58:02'),
(41, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 14:13:33'),
(42, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-02 14:16:06'),
(43, 5, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 14:21:01'),
(44, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-02 17:15:39'),
(45, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 17:16:05'),
(46, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 17:16:41'),
(47, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 17:16:46'),
(48, 5, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 17:18:01'),
(49, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 17:18:06'),
(50, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-02 17:18:31'),
(51, 5, 'login', 'User logged in successfully', NULL, '2401:4900:47f2:6f48:3410:e754:166c:9851', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-12-02 17:20:18'),
(52, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5b:1a3e:b8d1:8c5f:cacc:1812', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-03 01:25:01'),
(53, 5, 'logout', 'User logged out', NULL, '2401:4900:1c5b:1a3e:b8d1:8c5f:cacc:1812', NULL, '2025-12-03 01:38:45'),
(54, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:05:02'),
(55, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:17:35'),
(56, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-03 04:17:48'),
(57, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:17:51'),
(58, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 09:17:14'),
(59, 5, 'login', 'User logged in successfully', NULL, '2401:4900:47f2:10d3:a471:c50e:116c:9823', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.148 Mobile/15E148 Safari/604.1', '2025-12-03 19:42:32'),
(60, 5, 'logout', 'User logged out', NULL, '2401:4900:47f2:10d3:a471:c50e:116c:9823', NULL, '2025-12-03 19:45:51'),
(61, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 05:26:44'),
(62, 5, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-04 05:29:39'),
(63, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 05:29:47'),
(64, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹1,000 - Book #1 (Christmas 25). Reason: Wrong payment marked', NULL, NULL, NULL, '2025-12-04 05:37:39'),
(65, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 05:41:56'),
(66, 1, 'deletion_approved', 'Approved deletion request #1: Transaction: ₹1,000 - Book #1 (Christmas 25)', NULL, NULL, NULL, '2025-12-04 05:42:09'),
(67, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.148 Mobile/15E148 Safari/604.1', '2025-12-04 06:00:49'),
(68, 1, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-04 06:02:56'),
(69, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 06:02:59'),
(70, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 06:16:00'),
(71, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-04 06:24:40'),
(72, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹700 - Book #1 (Christmas 25). Reason: Testing', NULL, NULL, NULL, '2025-12-04 06:59:57'),
(73, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 07:00:19'),
(74, 1, 'deletion_approved', 'Approved deletion request #2: Transaction: ₹700 - Book #1 (Christmas 25)', NULL, NULL, NULL, '2025-12-04 07:00:28'),
(75, 5, 'book_returned', 'Marked book #1 as RETURNED (Distribution ID: 4)', NULL, NULL, NULL, '2025-12-04 07:38:47'),
(76, 5, 'book_return_undone', 'Unmarked book #1 return status (Distribution ID: 4)', NULL, NULL, NULL, '2025-12-04 07:38:54'),
(77, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 10:16:06'),
(78, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-04 10:16:13'),
(79, 5, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 10:16:16'),
(80, 5, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-04 10:33:47'),
(81, 1, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 10:33:51'),
(82, 1, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-04 10:34:26'),
(83, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 10:34:31'),
(84, 3, 'logout', 'User logged out', NULL, '103.211.52.166', NULL, '2025-12-04 10:40:20'),
(85, 3, 'login', 'User logged in successfully', NULL, '103.211.52.166', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.148 Mobile/15E148 Safari/604.1', '2025-12-04 10:40:31'),
(86, 3, 'book_reassigned', 'Reassigned Book #1 from \'C > 2\' to \'C > 1\'', NULL, NULL, NULL, '2025-12-04 11:14:14'),
(87, 3, 'book_reassigned', 'Reassigned Book #2 from \'C > 2\' to \'C > 1\'', NULL, NULL, NULL, '2025-12-04 11:14:28'),
(88, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 11:16:45'),
(89, 3, 'book_returned', 'Marked book #1 as RETURNED (Distribution ID: 173)', NULL, NULL, NULL, '2025-12-04 11:20:41'),
(90, 3, 'book_returned', 'Marked book #2 as RETURNED (Distribution ID: 174)', NULL, NULL, NULL, '2025-12-04 11:28:52'),
(91, 3, 'book_returned', 'Marked book #3 as RETURNED (Distribution ID: 175)', NULL, NULL, NULL, '2025-12-04 11:28:57'),
(92, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.148 Mobile/15E148 Safari/604.1', '2025-12-04 11:51:07'),
(93, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-04 11:55:50'),
(94, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 12:00:34'),
(95, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 17:03:23'),
(96, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-04 17:28:38'),
(97, 3, 'logout', 'User logged out', NULL, '103.211.52.168', NULL, '2025-12-04 17:37:53'),
(98, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-04 17:38:16'),
(99, 3, 'login', 'User logged in successfully', NULL, '2401:4900:47f7:911c:6033:6b9c:b4ee:5a96', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-12-04 17:42:28'),
(100, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 17:48:56'),
(101, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 18:00:49'),
(102, 3, 'book_returned', 'Marked book #10 as RETURNED (Distribution ID: 182)', NULL, NULL, NULL, '2025-12-04 18:07:06'),
(103, 3, 'logout', 'User logged out', NULL, '2401:4900:47f3:74c1:2948:be26:c75c:2b52', NULL, '2025-12-04 18:28:01'),
(104, 5, 'login', 'User logged in successfully', NULL, '2401:4900:47f3:74c1:2948:be26:c75c:2b52', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-12-04 18:28:15'),
(105, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-05 10:31:19'),
(106, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-05 10:43:56'),
(107, 3, 'logout', 'User logged out', NULL, '103.211.52.168', NULL, '2025-12-05 10:50:49'),
(108, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-05 10:51:04'),
(109, 3, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-05 10:54:46'),
(110, 3, 'logout', 'User logged out', NULL, '103.211.52.168', NULL, '2025-12-05 11:00:41'),
(111, 3, 'logout', 'User logged out', NULL, '103.211.52.168', NULL, '2025-12-05 11:01:04'),
(112, 1, 'login', 'User logged in successfully', NULL, '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-05 11:01:08'),
(113, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f38:41dd:f4aa:9114:6594:9161', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-06 18:24:49'),
(114, 5, 'logout', 'User logged out', NULL, '2401:4900:1f38:41dd:f4aa:9114:6594:9161', NULL, '2025-12-06 18:28:40'),
(115, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5a:70d9:255d:2987:2ba:2084', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-07 15:16:18'),
(116, 5, 'book_reassigned', 'Reassigned Book #2 from \'Assisi Convent\' to \'B.H.L Convent\'', NULL, NULL, NULL, '2025-12-07 15:20:56'),
(117, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-13 19:44:54'),
(118, 5, 'login', 'User logged in successfully', NULL, '2401:4900:839a:374f:71c9:2a9a:6664:2640', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-14 04:35:52'),
(119, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5d:3d63:53c:8285:88fd:4d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 13:52:19'),
(120, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1c5d:3d63:53c:8285:88fd:4d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 14:00:56'),
(121, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5d:3d63:53c:8285:88fd:4d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 14:06:27'),
(122, 5, 'login', 'User logged in successfully', NULL, '2401:4900:83a5:ef10:805:b1d7:aaf3:24c3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-21 04:39:08'),
(123, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:6953:18d3:21f3:9dce:2524', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-21 09:04:25'),
(124, 5, 'login', 'User logged in successfully', NULL, '103.211.52.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 09:02:03'),
(125, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5a:af1e:571:ee02:261d:3b4c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-24 12:05:46'),
(126, 5, 'login', 'User logged in successfully', NULL, '2401:4900:47f1:1fc4:64e2:d361:4f82:7de1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-24 14:00:07'),
(127, 5, 'login', 'User logged in successfully', NULL, '2401:4900:47fc:21be:b955:b518:d005:44ac', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2025-12-24 16:36:55'),
(128, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5a:af1e:b5a0:3c3c:8056:2603', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 04:15:06'),
(129, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5a:af1e:15fe:c21e:f16e:d5e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:15:15'),
(130, 5, 'logout', 'User logged out', NULL, '2401:4900:1c5a:af1e:15fe:c21e:f16e:d5e1', NULL, '2025-12-25 07:40:22'),
(131, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5a:af1e:15fe:c21e:f16e:d5e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:40:42'),
(132, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1c5a:af1e:15fe:c21e:f16e:d5e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 11:05:10'),
(133, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:3b34:a903:6b6:6e22:c436', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-03 16:21:05'),
(134, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹1,000 - Book #2 (Christmas 25). Reason: Delete It', NULL, NULL, NULL, '2026-01-03 16:43:05'),
(135, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:3b34:a903:6b6:6e22:c436', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-03 16:43:40'),
(136, 1, 'deletion_approved', 'Approved deletion request #3: Transaction: ₹1,000 - Book #2 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:43:51'),
(137, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹500 - Book #92 (Christmas 25). Reason: Delete it', NULL, NULL, NULL, '2026-01-03 16:45:07'),
(138, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹1,500 - Book #92 (Christmas 25). Reason: Delete it', NULL, NULL, NULL, '2026-01-03 16:45:21'),
(139, 1, 'deletion_approved', 'Approved deletion request #5: Transaction: ₹1,500 - Book #92 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:45:35'),
(140, 1, 'deletion_approved', 'Approved deletion request #4: Transaction: ₹500 - Book #92 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:45:41'),
(141, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹2,000 - Book #165 (Christmas 25). Reason: Delete it', NULL, NULL, NULL, '2026-01-03 16:46:34'),
(142, 1, 'deletion_approved', 'Approved deletion request #6: Transaction: ₹2,000 - Book #165 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:46:45'),
(143, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹600 - Book #28 (Christmas 25). Reason: Delete it', NULL, NULL, NULL, '2026-01-03 16:48:21'),
(144, 1, 'deletion_approved', 'Approved deletion request #7: Transaction: ₹600 - Book #28 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:48:34'),
(145, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹1,400 - Book #29 (Christmas 25). Reason: Delete it', NULL, NULL, NULL, '2026-01-03 16:51:27'),
(146, 1, 'deletion_approved', 'Approved deletion request #8: Transaction: ₹1,400 - Book #29 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:51:49'),
(147, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹2,000 - Book #139 (Christmas 25). Reason: delete it', NULL, NULL, NULL, '2026-01-03 16:55:55'),
(148, 1, 'deletion_approved', 'Approved deletion request #9: Transaction: ₹2,000 - Book #139 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 16:56:09'),
(149, 5, 'deletion_requested', 'Requested deletion of payment transaction: Transaction: ₹2,000 - Book #186 (Christmas 25). Reason: Delete it', NULL, NULL, NULL, '2026-01-03 17:04:44'),
(150, 1, 'deletion_approved', 'Approved deletion request #10: Transaction: ₹2,000 - Book #186 (Christmas 25)', NULL, NULL, NULL, '2026-01-03 17:04:53'),
(151, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:3b34:2dea:9793:c897:a7ce', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-03 17:39:20'),
(152, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:41cc:596b:25e8:d0f7:b1b2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-04 10:59:54'),
(153, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:41cc:596b:25e8:d0f7:b1b2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-04 11:23:09'),
(154, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:41cc:ed2c:f581:9022:22b7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-04 12:30:14'),
(155, 5, 'login', 'User logged in successfully', NULL, '2401:4900:47f3:2317:e8:794b:24b1:93b4', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2026-01-09 18:52:23'),
(156, 5, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:297f:2972:2417:2bff:5c72', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2026-01-11 16:15:16'),
(157, 1, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:297f:b9dd:ff09:a003:45c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 17:34:50'),
(158, 1, 'password_reset', 'Password reset for user: Testing', NULL, NULL, NULL, '2026-01-11 17:35:57'),
(159, 3, 'login', 'User logged in successfully', NULL, '2401:4900:1f39:297f:b9dd:ff09:a003:45c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 17:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `alert_notifications`
--

CREATE TABLE `alert_notifications` (
  `alert_id` int(11) NOT NULL,
  `alert_type` enum('critical_error','security_threat','system_down','disk_space','failed_login','performance') NOT NULL,
  `alert_title` varchar(255) NOT NULL,
  `alert_message` text NOT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `notified_via_email` tinyint(1) DEFAULT 0,
  `notified_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alert_notifications`
--

INSERT INTO `alert_notifications` (`alert_id`, `alert_type`, `alert_title`, `alert_message`, `severity`, `is_read`, `is_resolved`, `notified_via_email`, `notified_at`, `acknowledged_by`, `acknowledged_at`, `resolved_at`, `created_at`) VALUES
(1, 'critical_error', 'Security_threat: Multiple failed login attempts detected for email: 7899015076 from IP: 103.211.52.166. Total attempt', 'Multiple failed login attempts detected for email: 7899015076 from IP: 103.211.52.166. Total attempts: 5', 'high', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-12-03 04:15:34'),
(2, 'critical_error', 'Security_threat: Multiple failed login attempts detected for email: 7899015076 from IP: 2401:4900:1c5d:2f0b:a9c8:634e', 'Multiple failed login attempts detected for email: 7899015076 from IP: 2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b. Total attempts: 5', 'high', 0, 0, 0, NULL, NULL, NULL, NULL, '2025-12-13 19:44:40');

-- --------------------------------------------------------

--
-- Table structure for table `book_distribution`
--

CREATE TABLE `book_distribution` (
  `distribution_id` int(10) UNSIGNED NOT NULL,
  `book_id` int(10) UNSIGNED NOT NULL,
  `mobile_number` varchar(15) DEFAULT NULL,
  `distribution_path` varchar(255) DEFAULT NULL,
  `level_1_value_id` int(10) UNSIGNED DEFAULT NULL,
  `level_2_value_id` int(10) UNSIGNED DEFAULT NULL,
  `level_3_value_id` int(10) UNSIGNED DEFAULT NULL,
  `distributed_at` timestamp NULL DEFAULT current_timestamp(),
  `is_extra_book` tinyint(1) DEFAULT 0 COMMENT 'Mark this book as extra book for commission purposes',
  `distributed_by` int(10) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `is_returned` tinyint(1) DEFAULT 0 COMMENT '0=Not Returned, 1=Returned',
  `returned_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'User who marked as returned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_distribution`
--

INSERT INTO `book_distribution` (`distribution_id`, `book_id`, `mobile_number`, `distribution_path`, `level_1_value_id`, `level_2_value_id`, `level_3_value_id`, `distributed_at`, `is_extra_book`, `distributed_by`, `notes`, `is_returned`, `returned_by`) VALUES
(1, 1, '', NULL, NULL, NULL, NULL, '2025-11-29 18:04:36', 0, 3, 'St Mary Unit', 0, NULL),
(2, 2, '', 'St Thomas', NULL, NULL, NULL, '2025-11-30 09:33:38', 0, 3, '', 0, NULL),
(3, 3, '', 'St.Mary', NULL, NULL, NULL, '2025-11-30 09:42:45', 0, 3, '', 0, NULL),
(4, 601, '', 'Fr. Joseph', NULL, NULL, NULL, '2025-12-02 17:30:50', 0, 5, '', 1, NULL),
(5, 602, '', 'B.H.L Convent', NULL, NULL, NULL, '2025-12-07 15:20:56', 0, 5, 'Sr.Thereselit', 1, NULL),
(6, 603, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(7, 604, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(8, 605, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(9, 606, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(10, 607, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(11, 608, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(12, 609, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(13, 610, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(14, 611, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(15, 612, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(16, 613, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(17, 614, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(18, 615, '', 'St. Mary’s Unit', NULL, NULL, NULL, '2025-12-02 17:33:06', 0, 5, 'Mr jose Samuel', 1, NULL),
(19, 616, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(20, 617, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(21, 618, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(22, 619, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(23, 620, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(24, 621, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(25, 622, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(26, 623, '', 'St. Joseph’s Unit', NULL, NULL, NULL, '2025-12-02 17:34:17', 0, 5, 'Mr Paul PM', 1, NULL),
(27, 624, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(28, 625, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(29, 626, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(30, 627, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(31, 628, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(32, 629, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(33, 630, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 1, NULL),
(34, 631, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 0, NULL),
(35, 632, '', 'Infant Jesus Unit', NULL, NULL, NULL, '2025-12-02 17:35:18', 0, 5, 'Mrs Sophy Joseph', 0, NULL),
(36, 633, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(37, 634, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(38, 635, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(39, 636, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(40, 637, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(41, 638, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(42, 639, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(43, 640, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(44, 641, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(45, 642, '', 'St. Anthony’s Unit', NULL, NULL, NULL, '2025-12-02 17:36:21', 0, 5, 'Mr T.O. Thomas', 1, NULL),
(46, 643, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(47, 644, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(48, 645, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(49, 646, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(50, 647, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(51, 648, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(52, 649, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(53, 650, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(54, 651, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(55, 652, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(56, 653, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(57, 654, '', 'St. George Unit', NULL, NULL, NULL, '2025-12-02 17:37:27', 0, 5, 'Ms Riju Alex', 1, NULL),
(58, 655, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(59, 656, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(60, 657, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(61, 658, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(62, 659, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(63, 660, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(64, 661, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(65, 662, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(66, 663, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(67, 664, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(68, 665, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(69, 666, '', 'St. Thomas Unit', NULL, NULL, NULL, '2025-12-02 17:38:08', 0, 5, 'Mr Babu Abraham', 1, NULL),
(70, 667, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(71, 668, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(72, 669, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(73, 670, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(74, 671, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(75, 672, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(76, 673, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(77, 674, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(78, 675, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(79, 676, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(80, 677, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(81, 678, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(82, 679, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(83, 680, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(84, 681, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 1, NULL),
(85, 682, '', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-02 17:39:12', 0, 5, 'Mr Byju Kurian', 0, NULL),
(86, 683, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(87, 684, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(88, 685, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(89, 686, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(90, 687, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(91, 688, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(92, 689, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(93, 690, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(94, 691, '', 'St. Sebastian’s Unit', NULL, NULL, NULL, '2025-12-02 17:40:14', 0, 5, 'Ms Mary James', 1, NULL),
(95, 692, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(96, 693, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(97, 694, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(98, 695, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(99, 696, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(100, 697, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(101, 698, '', 'BI. Mariam Thresia Unit', NULL, NULL, NULL, '2025-12-02 17:41:11', 0, 5, 'Mr Binoy', 1, NULL),
(102, 699, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(103, 700, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(104, 701, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(105, 702, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(106, 703, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(107, 704, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(108, 705, '', 'Holy Family Unit', NULL, NULL, NULL, '2025-12-02 17:41:58', 0, 5, 'Mr Sophia Elizabeth Jose', 1, NULL),
(109, 706, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(110, 707, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(111, 708, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(112, 709, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(113, 710, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(114, 711, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(115, 712, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(116, 713, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(117, 714, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(118, 715, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(119, 716, '', 'St. Chavara Unit', NULL, NULL, NULL, '2025-12-02 17:42:47', 0, 5, 'Mr Toji Abraham', 1, NULL),
(120, 722, '', 'St. Stephen Unit', NULL, NULL, NULL, '2025-12-02 17:46:25', 0, 5, 'Mr Yohanan Daniel', 0, NULL),
(121, 723, '', 'St. Stephen Unit', NULL, NULL, NULL, '2025-12-02 17:46:25', 0, 5, 'Mr Yohanan Daniel', 0, NULL),
(122, 724, '', 'St. Stephen Unit', NULL, NULL, NULL, '2025-12-02 17:46:25', 0, 5, 'Mr Yohanan Daniel', 1, NULL),
(123, 725, '', 'St. Stephen Unit', NULL, NULL, NULL, '2025-12-02 17:46:25', 0, 5, 'Mr Yohanan Daniel', 1, NULL),
(124, 726, '', 'St. Stephen Unit', NULL, NULL, NULL, '2025-12-02 17:46:25', 0, 5, 'Mr Yohanan Daniel', 1, NULL),
(125, 727, '', 'St. Stephen Unit', NULL, NULL, NULL, '2025-12-02 17:46:25', 0, 5, 'Mr Yohanan Daniel', 1, NULL),
(126, 728, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(127, 729, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(128, 730, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(129, 731, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(130, 732, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(131, 733, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(132, 734, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(133, 735, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(134, 736, '', 'St. Peter Unit', NULL, NULL, NULL, '2025-12-02 17:47:14', 0, 5, 'Mr Roy K J', 1, NULL),
(135, 745, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(136, 746, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(137, 747, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(138, 748, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(139, 749, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(140, 750, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(141, 751, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(142, 752, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(143, 753, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(144, 754, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(145, 755, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(146, 756, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(147, 757, '', 'St. Cecila Unit', NULL, NULL, NULL, '2025-12-02 17:48:14', 0, 5, 'Mr Sherin Anson', 1, NULL),
(148, 765, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(149, 766, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(150, 767, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(151, 768, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(152, 769, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(153, 770, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(154, 771, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(155, 772, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(156, 773, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(157, 774, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(158, 775, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(159, 776, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(160, 777, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(161, 778, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(162, 779, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(163, 780, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(164, 781, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(165, 782, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(166, 783, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(167, 784, '', 'DSYM Unit', NULL, NULL, NULL, '2025-12-02 17:49:53', 0, 5, 'Mr Alvin Verghese', 0, NULL),
(168, 717, '', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-02 17:51:27', 0, 5, 'Ms Saju George', 1, NULL),
(169, 718, '', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-02 17:51:27', 0, 5, 'Ms Saju George', 1, NULL),
(170, 719, '', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-02 17:51:27', 0, 5, 'Ms Saju George', 1, NULL),
(171, 720, '', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-02 17:51:27', 0, 5, 'Ms Saju George', 1, NULL),
(172, 721, '', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-02 17:51:27', 0, 5, 'Ms Saju George', 1, NULL),
(173, 801, '', 'C > 1', NULL, NULL, NULL, '2025-12-04 11:14:14', 0, 3, '', 1, 3),
(174, 802, '', 'C > 1', NULL, NULL, NULL, '2025-12-04 11:14:28', 0, 3, '', 1, 3),
(175, 803, '', 'A > 1', NULL, NULL, NULL, '2025-12-04 11:03:52', 0, 3, '', 1, 3),
(176, 804, '', 'A > 1', NULL, NULL, NULL, '2025-12-04 11:03:52', 0, 3, '', 0, NULL),
(177, 805, '', 'A > 2', NULL, NULL, NULL, '2025-12-04 11:14:57', 0, 3, '', 0, NULL),
(178, 806, '', 'A > 2', NULL, NULL, NULL, '2025-12-04 11:14:57', 0, 3, '', 0, NULL),
(179, 807, '', 'B > 1', NULL, NULL, NULL, '2025-12-04 11:15:20', 0, 3, '', 0, NULL),
(180, 808, '', 'B > 1', NULL, NULL, NULL, '2025-12-04 11:15:20', 0, 3, '', 0, NULL),
(181, 809, '', 'C > 1', NULL, NULL, NULL, '2025-12-04 11:15:37', 0, 3, '', 0, NULL),
(182, 810, '', 'A > 1', NULL, NULL, NULL, '2025-12-04 11:15:50', 1, 3, '', 1, 3),
(183, 741, '9711172824', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-07 15:27:18', 1, 5, 'Byju Kurien', 1, NULL),
(184, 742, '9711172824', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-07 15:27:18', 1, 5, 'Byju Kurien', 1, NULL),
(185, 743, '9711172824', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-07 15:27:18', 1, 5, 'Byju Kurien', 1, NULL),
(186, 744, '9711172824', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-07 15:27:18', 1, 5, 'Byju Kurien', 1, NULL),
(187, 786, '9711172824', 'St. Mathew Unit', NULL, NULL, NULL, '2025-12-07 15:27:18', 1, 5, 'Byju Kurien', 1, NULL),
(188, 739, '9540946472', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-07 15:31:26', 1, 5, 'Saju George', 1, NULL),
(189, 740, '9540946472', 'St. Xavier Unit', NULL, NULL, NULL, '2025-12-07 15:31:26', 1, 5, 'Saju George', 0, NULL),
(190, 737, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(191, 738, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(192, 758, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(193, 759, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(194, 760, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(195, 761, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(196, 762, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(197, 763, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL),
(198, 764, '9910200997', 'St. Paul Unit', NULL, NULL, NULL, '2025-12-07 15:35:28', 0, 5, 'Subhash Joseph', 1, NULL);

--
-- Triggers `book_distribution`
--
DELIMITER $$
CREATE TRIGGER `after_book_distribution` AFTER INSERT ON `book_distribution` FOR EACH ROW BEGIN
    UPDATE lottery_books
    SET book_status = 'distributed'
    WHERE book_id = NEW.book_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `commission_earned`
--

CREATE TABLE `commission_earned` (
  `commission_id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `distribution_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Link to specific book distribution',
  `level_1_value` varchar(100) NOT NULL COMMENT 'Level 1 name (Wing A, Building B, etc.)',
  `payment_collection_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Link to specific payment',
  `book_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Link to book',
  `commission_type` enum('early','standard','extra_books') NOT NULL,
  `commission_percent` decimal(5,2) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL COMMENT 'Amount on which commission is calculated',
  `commission_amount` decimal(10,2) NOT NULL COMMENT 'Calculated commission amount',
  `payment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commission_earned`
--

INSERT INTO `commission_earned` (`commission_id`, `event_id`, `distribution_id`, `level_1_value`, `payment_collection_id`, `book_id`, `commission_type`, `commission_percent`, `payment_amount`, `commission_amount`, `payment_date`, `created_at`) VALUES
(14, 6, 173, 'C', NULL, 801, 'early', 15.00, 1000.00, 150.00, '2025-12-04', '2025-12-04 17:45:24'),
(15, 6, 174, 'C', NULL, 802, 'early', 15.00, 1000.00, 150.00, '2025-12-04', '2025-12-04 17:45:24'),
(16, 6, 175, 'A', NULL, 803, 'early', 15.00, 1000.00, 150.00, '2025-12-04', '2025-12-04 17:45:24'),
(17, 6, 182, 'A', NULL, 810, 'extra_books', 20.00, 1000.00, 200.00, '2025-12-04', '2025-12-04 17:45:24'),
(18, 6, 182, 'A', NULL, 810, 'early', 15.00, 1000.00, 150.00, '2025-12-04', '2025-12-04 17:45:24'),
(19, 5, 50, 'St. George Unit', NULL, 647, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(20, 5, 49, 'St. George Unit', NULL, 646, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(21, 5, 48, 'St. George Unit', NULL, 645, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(22, 5, 47, 'St. George Unit', NULL, 644, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(23, 5, 46, 'St. George Unit', NULL, 643, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(24, 5, 114, 'St. Chavara Unit', NULL, 711, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(25, 5, 113, 'St. Chavara Unit', NULL, 710, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(26, 5, 51, 'St. George Unit', NULL, 648, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(27, 5, 52, 'St. George Unit', NULL, 649, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(28, 5, 53, 'St. George Unit', NULL, 650, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(29, 5, 54, 'St. George Unit', NULL, 651, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(30, 5, 55, 'St. George Unit', NULL, 652, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(31, 5, 56, 'St. George Unit', NULL, 653, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(32, 5, 57, 'St. George Unit', NULL, 654, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(33, 5, 19, 'St. Joseph’s Unit', NULL, 616, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(34, 5, 20, 'St. Joseph’s Unit', NULL, 617, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(35, 5, 112, 'St. Chavara Unit', NULL, 709, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(36, 5, 111, 'St. Chavara Unit', NULL, 708, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(37, 5, 147, 'St. Cecila Unit', NULL, 757, 'early', 10.00, 400.00, 40.00, '2025-12-14', '2026-01-04 11:42:35'),
(38, 5, 33, 'Infant Jesus Unit', NULL, 630, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(39, 5, 34, 'Infant Jesus Unit', NULL, 631, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(40, 5, 35, 'Infant Jesus Unit', NULL, 632, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(41, 5, 135, 'St. Cecila Unit', NULL, 745, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(42, 5, 136, 'St. Cecila Unit', NULL, 746, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(43, 5, 137, 'St. Cecila Unit', NULL, 747, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(44, 5, 138, 'St. Cecila Unit', NULL, 748, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(45, 5, 139, 'St. Cecila Unit', NULL, 749, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(46, 5, 140, 'St. Cecila Unit', NULL, 750, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(47, 5, 141, 'St. Cecila Unit', NULL, 751, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(48, 5, 142, 'St. Cecila Unit', NULL, 752, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(49, 5, 143, 'St. Cecila Unit', NULL, 753, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(50, 5, 144, 'St. Cecila Unit', NULL, 754, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(51, 5, 145, 'St. Cecila Unit', NULL, 755, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(52, 5, 146, 'St. Cecila Unit', NULL, 756, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(53, 5, 101, 'BI. Mariam Thresia Unit', NULL, 698, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(54, 5, 21, 'St. Joseph’s Unit', NULL, 618, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(55, 5, 22, 'St. Joseph’s Unit', NULL, 619, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(56, 5, 59, 'St. Thomas Unit', NULL, 656, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(57, 5, 60, 'St. Thomas Unit', NULL, 657, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(58, 5, 61, 'St. Thomas Unit', NULL, 658, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(59, 5, 62, 'St. Thomas Unit', NULL, 659, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(60, 5, 63, 'St. Thomas Unit', NULL, 660, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(61, 5, 64, 'St. Thomas Unit', NULL, 661, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(62, 5, 65, 'St. Thomas Unit', NULL, 662, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(63, 5, 66, 'St. Thomas Unit', NULL, 663, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(64, 5, 67, 'St. Thomas Unit', NULL, 664, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(65, 5, 168, 'St. Xavier Unit', NULL, 717, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(66, 5, 169, 'St. Xavier Unit', NULL, 718, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(67, 5, 170, 'St. Xavier Unit', NULL, 719, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(68, 5, 171, 'St. Xavier Unit', NULL, 720, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(69, 5, 172, 'St. Xavier Unit', NULL, 721, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(70, 5, 188, 'St. Xavier Unit', NULL, 739, 'extra_books', 15.00, 2000.00, 300.00, '2025-12-14', '2026-01-04 11:42:35'),
(71, 5, 188, 'St. Xavier Unit', NULL, 739, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(72, 5, 58, 'St. Thomas Unit', NULL, 655, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(73, 5, 125, 'St. Stephen Unit', NULL, 727, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(74, 5, 124, 'St. Stephen Unit', NULL, 726, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(75, 5, 23, 'St. Joseph’s Unit', NULL, 620, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(76, 5, 24, 'St. Joseph’s Unit', NULL, 621, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(77, 5, 25, 'St. Joseph’s Unit', NULL, 622, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(78, 5, 26, 'St. Joseph’s Unit', NULL, 623, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(79, 5, 86, 'St. Sebastian’s Unit', NULL, 683, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(80, 5, 87, 'St. Sebastian’s Unit', NULL, 684, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(81, 5, 88, 'St. Sebastian’s Unit', NULL, 685, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(82, 5, 89, 'St. Sebastian’s Unit', NULL, 686, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(83, 5, 90, 'St. Sebastian’s Unit', NULL, 687, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(84, 5, 91, 'St. Sebastian’s Unit', NULL, 688, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(85, 5, 92, 'St. Sebastian’s Unit', NULL, 689, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(86, 5, 93, 'St. Sebastian’s Unit', NULL, 690, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(87, 5, 94, 'St. Sebastian’s Unit', NULL, 691, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(88, 5, 120, 'St. Stephen Unit', NULL, 722, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(89, 5, 121, 'St. Stephen Unit', NULL, 723, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(90, 5, 189, 'St. Xavier Unit', NULL, 740, 'extra_books', 15.00, 400.00, 60.00, '2025-12-14', '2026-01-04 11:42:35'),
(91, 5, 189, 'St. Xavier Unit', NULL, 740, 'early', 10.00, 400.00, 40.00, '2025-12-14', '2026-01-04 11:42:35'),
(92, 5, 109, 'St. Chavara Unit', NULL, 706, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(93, 5, 186, 'St. Mathew Unit', NULL, 744, 'extra_books', 15.00, 2000.00, 300.00, '2025-12-14', '2026-01-04 11:42:35'),
(94, 5, 186, 'St. Mathew Unit', NULL, 744, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(95, 5, 126, 'St. Peter Unit', NULL, 728, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(96, 5, 127, 'St. Peter Unit', NULL, 729, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(97, 5, 128, 'St. Peter Unit', NULL, 730, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(98, 5, 129, 'St. Peter Unit', NULL, 731, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(99, 5, 130, 'St. Peter Unit', NULL, 732, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(100, 5, 131, 'St. Peter Unit', NULL, 733, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(101, 5, 132, 'St. Peter Unit', NULL, 734, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(102, 5, 134, 'St. Peter Unit', NULL, 736, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(103, 5, 133, 'St. Peter Unit', NULL, 735, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(104, 5, 190, 'St. Paul Unit', NULL, 737, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(105, 5, 191, 'St. Paul Unit', NULL, 738, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(106, 5, 192, 'St. Paul Unit', NULL, 758, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(107, 5, 193, 'St. Paul Unit', NULL, 759, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(108, 5, 194, 'St. Paul Unit', NULL, 760, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(109, 5, 195, 'St. Paul Unit', NULL, 761, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(110, 5, 196, 'St. Paul Unit', NULL, 762, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(111, 5, 185, 'St. Mathew Unit', NULL, 743, 'extra_books', 15.00, 2000.00, 300.00, '2025-12-14', '2026-01-04 11:42:35'),
(112, 5, 185, 'St. Mathew Unit', NULL, 743, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(113, 5, 184, 'St. Mathew Unit', NULL, 742, 'extra_books', 15.00, 2000.00, 300.00, '2025-12-14', '2026-01-04 11:42:35'),
(114, 5, 184, 'St. Mathew Unit', NULL, 742, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(115, 5, 183, 'St. Mathew Unit', NULL, 741, 'extra_books', 15.00, 2000.00, 300.00, '2025-12-14', '2026-01-04 11:42:35'),
(116, 5, 183, 'St. Mathew Unit', NULL, 741, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(117, 5, 110, 'St. Chavara Unit', NULL, 707, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(118, 5, 70, 'St. Mathew Unit', NULL, 667, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(119, 5, 71, 'St. Mathew Unit', NULL, 668, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(120, 5, 72, 'St. Mathew Unit', NULL, 669, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(121, 5, 73, 'St. Mathew Unit', NULL, 670, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(122, 5, 74, 'St. Mathew Unit', NULL, 671, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(123, 5, 75, 'St. Mathew Unit', NULL, 672, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(124, 5, 76, 'St. Mathew Unit', NULL, 673, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(125, 5, 77, 'St. Mathew Unit', NULL, 674, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(126, 5, 78, 'St. Mathew Unit', NULL, 675, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(127, 5, 79, 'St. Mathew Unit', NULL, 676, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(128, 5, 80, 'St. Mathew Unit', NULL, 677, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(129, 5, 81, 'St. Mathew Unit', NULL, 678, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(130, 5, 82, 'St. Mathew Unit', NULL, 679, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(131, 5, 83, 'St. Mathew Unit', NULL, 680, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(132, 5, 84, 'St. Mathew Unit', NULL, 681, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(133, 5, 85, 'St. Mathew Unit', NULL, 682, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(134, 5, 197, 'St. Paul Unit', NULL, 763, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(135, 5, 99, 'BI. Mariam Thresia Unit', NULL, 696, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(136, 5, 30, 'Infant Jesus Unit', NULL, 627, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(137, 5, 31, 'Infant Jesus Unit', NULL, 628, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(138, 5, 6, 'St. Mary’s Unit', NULL, 603, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(139, 5, 7, 'St. Mary’s Unit', NULL, 604, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(140, 5, 100, 'BI. Mariam Thresia Unit', NULL, 697, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(141, 5, 8, 'St. Mary’s Unit', NULL, 605, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(142, 5, 9, 'St. Mary’s Unit', NULL, 606, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(143, 5, 10, 'St. Mary’s Unit', NULL, 607, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(144, 5, 11, 'St. Mary’s Unit', NULL, 608, 'early', 10.00, 1000.00, 100.00, '2025-12-14', '2026-01-04 11:42:35'),
(145, 5, 95, 'BI. Mariam Thresia Unit', NULL, 692, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(146, 5, 96, 'BI. Mariam Thresia Unit', NULL, 693, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(147, 5, 97, 'BI. Mariam Thresia Unit', NULL, 694, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(148, 5, 98, 'BI. Mariam Thresia Unit', NULL, 695, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(149, 5, 29, 'Infant Jesus Unit', NULL, 626, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(150, 5, 28, 'Infant Jesus Unit', NULL, 625, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(151, 5, 198, 'St. Paul Unit', NULL, 764, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(152, 5, 5, 'B.H.L Convent', NULL, 602, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(153, 5, 148, 'DSYM Unit', NULL, 765, 'early', 10.00, 1500.00, 150.00, '2025-12-14', '2026-01-04 11:42:35'),
(154, 5, 148, 'DSYM Unit', NULL, 765, 'early', 10.00, 500.00, 50.00, '2025-12-14', '2026-01-04 11:42:35'),
(155, 5, 36, 'St. Anthony’s Unit', NULL, 633, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(156, 5, 37, 'St. Anthony’s Unit', NULL, 634, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(157, 5, 38, 'St. Anthony’s Unit', NULL, 635, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(158, 5, 39, 'St. Anthony’s Unit', NULL, 636, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(159, 5, 40, 'St. Anthony’s Unit', NULL, 637, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(160, 5, 27, 'Infant Jesus Unit', NULL, 624, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(161, 5, 43, 'St. Anthony’s Unit', NULL, 640, 'early', 10.00, 400.00, 40.00, '2025-12-14', '2026-01-04 11:42:35'),
(162, 5, 42, 'St. Anthony’s Unit', NULL, 639, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(163, 5, 41, 'St. Anthony’s Unit', NULL, 638, 'early', 10.00, 2000.00, 200.00, '2025-12-14', '2026-01-04 11:42:35'),
(164, 5, 13, 'St. Mary’s Unit', NULL, 610, 'standard', 5.00, 1000.00, 50.00, '2025-12-21', '2026-01-04 11:42:35'),
(165, 5, 15, 'St. Mary’s Unit', NULL, 612, 'standard', 5.00, 100.00, 5.00, '2025-12-21', '2026-01-04 11:42:35'),
(166, 5, 12, 'St. Mary’s Unit', NULL, 609, 'standard', 5.00, 2000.00, 100.00, '2025-12-21', '2026-01-04 11:42:35');

-- --------------------------------------------------------

--
-- Table structure for table `commission_settings`
--

CREATE TABLE `commission_settings` (
  `setting_id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `commission_enabled` tinyint(1) DEFAULT 0 COMMENT 'Group admin can toggle commission on/off',
  `early_commission_enabled` tinyint(1) DEFAULT 0 COMMENT 'Enable/disable early payment commission',
  `early_payment_date` date DEFAULT NULL COMMENT 'Date 1: Before this date = 10% commission',
  `early_commission_percent` decimal(5,2) DEFAULT 10.00,
  `standard_commission_enabled` tinyint(1) DEFAULT 0 COMMENT 'Enable/disable standard payment commission',
  `standard_payment_date` date DEFAULT NULL COMMENT 'Date 2: Before this date = 5% commission',
  `standard_commission_percent` decimal(5,2) DEFAULT 5.00,
  `extra_books_commission_enabled` tinyint(1) DEFAULT 0 COMMENT 'Enable/disable extra books commission',
  `extra_books_date` date DEFAULT NULL COMMENT 'After this date, new books get 15% commission',
  `extra_books_commission_percent` decimal(5,2) DEFAULT 15.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commission_settings`
--

INSERT INTO `commission_settings` (`setting_id`, `event_id`, `commission_enabled`, `early_commission_enabled`, `early_payment_date`, `early_commission_percent`, `standard_commission_enabled`, `standard_payment_date`, `standard_commission_percent`, `extra_books_commission_enabled`, `extra_books_date`, `extra_books_commission_percent`, `created_at`, `updated_at`) VALUES
(4, 5, 1, 1, '2025-12-14', 10.00, 1, '2025-12-21', 5.00, 1, '2025-12-06', 15.00, '2025-12-03 01:35:43', '2026-01-04 11:33:38'),
(5, 6, 1, 1, '2025-12-04', 15.00, 0, NULL, 5.00, 1, '2025-12-04', 20.00, '2025-12-04 10:38:59', '2025-12-04 10:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `community_id` int(10) UNSIGNED NOT NULL,
  `community_name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `total_members` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`community_id`, `community_name`, `address`, `city`, `state`, `pincode`, `total_members`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Tesst', '', '', '', '', 0, 'active', 1, '2025-11-29 17:55:41', '2025-11-29 17:56:19'),
(2, 'St. Alphonsa Church', '', '', '', '', 0, 'active', 1, '2025-11-30 17:50:46', '2025-11-30 17:50:46');

-- --------------------------------------------------------

--
-- Table structure for table `community_features`
--

CREATE TABLE `community_features` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `feature_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `enabled_date` timestamp NULL DEFAULT NULL,
  `disabled_date` timestamp NULL DEFAULT NULL,
  `enabled_by` int(10) UNSIGNED DEFAULT NULL,
  `disabled_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `community_features`
--

INSERT INTO `community_features` (`id`, `community_id`, `feature_id`, `is_enabled`, `enabled_date`, `disabled_date`, `enabled_by`, `disabled_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2026-01-11 18:09:23', NULL, 1, NULL, '2026-01-11 18:09:23', '2026-01-11 18:09:23'),
(2, 2, 1, 1, '2026-01-11 18:09:23', NULL, 1, NULL, '2026-01-11 18:09:23', '2026-01-11 18:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `community_settings`
--

CREATE TABLE `community_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `database_connection_logs`
--

CREATE TABLE `database_connection_logs` (
  `connection_id` int(11) NOT NULL,
  `connection_status` enum('success','failed','timeout') NOT NULL,
  `response_time` decimal(8,3) DEFAULT NULL COMMENT 'in milliseconds',
  `error_message` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `database_connection_logs`
--

INSERT INTO `database_connection_logs` (`connection_id`, `connection_status`, `response_time`, `error_message`, `checked_at`) VALUES
(1, 'success', 0.211, NULL, '2025-12-03 04:10:56'),
(2, 'success', 0.130, NULL, '2025-12-03 04:13:05'),
(3, 'success', 0.177, NULL, '2025-12-03 04:13:06'),
(4, 'success', 0.087, NULL, '2025-12-03 04:13:09'),
(5, 'success', 0.238, NULL, '2025-12-03 04:13:34'),
(6, 'success', 0.148, NULL, '2025-12-03 04:14:40'),
(7, 'success', 0.158, NULL, '2025-12-03 04:15:38'),
(8, 'success', 0.138, NULL, '2025-12-03 04:15:51'),
(9, 'success', 0.136, NULL, '2025-12-03 04:15:51'),
(10, 'success', 0.193, NULL, '2025-12-03 04:16:51'),
(11, 'success', 0.200, NULL, '2025-12-03 04:33:30'),
(12, 'success', 0.214, NULL, '2025-12-05 11:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `deletion_requests`
--

CREATE TABLE `deletion_requests` (
  `request_id` int(11) NOT NULL,
  `request_type` enum('lottery_event','transaction') NOT NULL,
  `item_id` int(11) NOT NULL COMMENT 'ID of the lottery event or transaction',
  `item_name` varchar(255) NOT NULL COMMENT 'Name/description of item to delete',
  `requested_by` int(11) NOT NULL COMMENT 'User ID who requested deletion',
  `reason` text NOT NULL COMMENT 'Reason for deletion request',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who reviewed',
  `review_notes` text DEFAULT NULL COMMENT 'Admin notes for approval/rejection',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deletion_requests`
--

INSERT INTO `deletion_requests` (`request_id`, `request_type`, `item_id`, `item_name`, `requested_by`, `reason`, `status`, `reviewed_by`, `review_notes`, `reviewed_at`, `created_at`) VALUES
(1, 'transaction', 8, 'Transaction: ₹1,000 - Book #1 (Christmas 25)', 5, 'Wrong payment marked', 'approved', 1, '', '2025-12-04 05:42:09', '2025-12-04 05:37:39'),
(2, 'transaction', 9, 'Transaction: ₹700 - Book #1 (Christmas 25)', 5, 'Testing', 'approved', 1, '', '2025-12-04 07:00:28', '2025-12-04 06:59:57'),
(3, 'transaction', 174, 'Transaction: ₹1,000 - Book #2 (Christmas 25)', 5, 'Delete It', 'approved', 1, 'Delete it', '2026-01-03 16:43:51', '2026-01-03 16:43:05'),
(4, 'transaction', 175, 'Transaction: ₹500 - Book #92 (Christmas 25)', 5, 'Delete it', 'approved', 1, 'Delet it', '2026-01-03 16:45:41', '2026-01-03 16:45:07'),
(5, 'transaction', 176, 'Transaction: ₹1,500 - Book #92 (Christmas 25)', 5, 'Delete it', 'approved', 1, 'Delet it', '2026-01-03 16:45:35', '2026-01-03 16:45:21'),
(6, 'transaction', 90, 'Transaction: ₹2,000 - Book #165 (Christmas 25)', 5, 'Delete it', 'approved', 1, 'Delete it', '2026-01-03 16:46:45', '2026-01-03 16:46:34'),
(7, 'transaction', 97, 'Transaction: ₹600 - Book #28 (Christmas 25)', 5, 'Delete it', 'approved', 1, 'Delet it', '2026-01-03 16:48:34', '2026-01-03 16:48:21'),
(8, 'transaction', 98, 'Transaction: ₹1,400 - Book #29 (Christmas 25)', 5, 'Delete it', 'approved', 1, 'Delete it', '2026-01-03 16:51:49', '2026-01-03 16:51:27'),
(9, 'transaction', 172, 'Transaction: ₹2,000 - Book #139 (Christmas 25)', 5, 'delete it', 'approved', 1, 'delete it', '2026-01-03 16:56:09', '2026-01-03 16:55:55'),
(10, 'transaction', 71, 'Transaction: ₹2,000 - Book #186 (Christmas 25)', 5, 'Delete it', 'approved', 1, 'Delete it', '2026-01-03 17:04:53', '2026-01-03 17:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `distribution_levels`
--

CREATE TABLE `distribution_levels` (
  `level_id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `level_number` tinyint(3) UNSIGNED NOT NULL,
  `level_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `distribution_levels`
--

INSERT INTO `distribution_levels` (`level_id`, `event_id`, `level_number`, `level_name`, `created_at`) VALUES
(2, 5, 1, 'Unit Name', '2025-12-02 17:21:40'),
(3, 6, 1, 'Unit Name', '2025-12-04 10:35:20'),
(4, 6, 2, 'Member Name', '2025-12-04 10:35:33');

-- --------------------------------------------------------

--
-- Table structure for table `distribution_level_values`
--

CREATE TABLE `distribution_level_values` (
  `value_id` int(10) UNSIGNED NOT NULL,
  `level_id` int(10) UNSIGNED NOT NULL,
  `parent_value_id` int(10) UNSIGNED DEFAULT NULL,
  `value_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `distribution_level_values`
--

INSERT INTO `distribution_level_values` (`value_id`, `level_id`, `parent_value_id`, `value_name`, `created_at`) VALUES
(3, 2, NULL, 'St. Thomas Unit', '2025-12-02 17:22:30'),
(4, 2, NULL, 'Fr. Joseph', '2025-12-02 17:30:50'),
(5, 2, NULL, 'Assisi Convent', '2025-12-02 17:31:49'),
(6, 2, NULL, 'St. Mary’s Unit', '2025-12-02 17:33:06'),
(7, 2, NULL, 'St. Joseph’s Unit', '2025-12-02 17:34:17'),
(8, 2, NULL, 'Infant Jesus Unit', '2025-12-02 17:35:18'),
(9, 2, NULL, 'St. Anthony’s Unit', '2025-12-02 17:36:21'),
(10, 2, NULL, 'St. George Unit', '2025-12-02 17:37:27'),
(11, 2, NULL, 'St. Mathew Unit', '2025-12-02 17:39:12'),
(12, 2, NULL, 'St. Sebastian’s Unit', '2025-12-02 17:40:14'),
(13, 2, NULL, 'BI. Mariam Thresia Unit', '2025-12-02 17:41:11'),
(14, 2, NULL, 'Holy Family Unit', '2025-12-02 17:41:58'),
(15, 2, NULL, 'St. Chavara Unit', '2025-12-02 17:42:47'),
(16, 2, NULL, 'St. Stephen Unit', '2025-12-02 17:46:25'),
(17, 2, NULL, 'St. Peter Unit', '2025-12-02 17:47:14'),
(18, 2, NULL, 'St. Cecila Unit', '2025-12-02 17:48:14'),
(19, 2, NULL, 'DSYM Unit', '2025-12-02 17:49:53'),
(20, 2, NULL, 'St. Xavier Unit', '2025-12-02 17:51:27'),
(21, 3, NULL, 'A', '2025-12-04 10:35:41'),
(22, 4, 21, '1', '2025-12-04 10:35:48'),
(24, 3, NULL, 'B', '2025-12-04 10:36:05'),
(25, 3, NULL, 'C', '2025-12-04 10:36:10'),
(26, 4, 24, '1', '2025-12-04 10:36:18'),
(27, 4, 25, '1', '2025-12-04 10:36:24'),
(28, 4, 21, '2', '2025-12-04 10:36:42'),
(29, 2, NULL, 'B.H.L Convent', '2025-12-07 15:20:56'),
(30, 2, NULL, 'St. Paul Unit', '2025-12-07 15:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `failed_login_attempts`
--

CREATE TABLE `failed_login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `failed_login_attempts`
--

INSERT INTO `failed_login_attempts` (`attempt_id`, `email`, `ip_address`, `user_agent`, `attempted_at`) VALUES
(1, '7899015076', '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:15:12'),
(2, '7899015076', '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:15:17'),
(3, '7899015076', '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:15:25'),
(4, '7899015076', '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:15:30'),
(5, '7899015076', '103.211.52.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:15:34'),
(6, '9999999999', '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-05 11:00:51'),
(7, '9999999999', '103.211.52.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-05 11:00:56'),
(8, '7899015076', '2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-13 19:44:00'),
(9, '7899015076', '2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-13 19:44:08'),
(10, '7899015076', '2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-13 19:44:19'),
(11, '7899015076', '2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-13 19:44:30'),
(12, '7899015076', '2401:4900:1c5d:2f0b:a9c8:634e:2f13:c84b', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-13 19:44:40'),
(13, '9810154881', '2401:4900:839a:374f:71c9:2a9a:6664:2640', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.108 Mobile/15E148 Safari/604.1', '2025-12-14 04:35:34'),
(14, '7899015076', '2401:4900:1f39:297f:b9dd:ff09:a003:45c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 17:35:24'),
(15, '7899015076', '2401:4900:1f39:297f:b9dd:ff09:a003:45c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 17:35:33'),
(16, '7899015076', '2401:4900:1f39:297f:b9dd:ff09:a003:45c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 17:35:38');

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

CREATE TABLE `features` (
  `feature_id` int(11) NOT NULL,
  `feature_name` varchar(100) NOT NULL,
  `feature_key` varchar(50) NOT NULL,
  `feature_description` text DEFAULT NULL,
  `feature_icon` varchar(100) DEFAULT '/public/images/features/default.svg',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`feature_id`, `feature_name`, `feature_key`, `feature_description`, `feature_icon`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Lottery System', 'lottery_system', 'Complete 6-part lottery event management with auto-generation, distribution, and payment tracking', '/public/images/features/lottery.svg', 1, 1, '2026-01-11 18:09:23', '2026-01-11 18:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `group_admin_assignments`
--

CREATE TABLE `group_admin_assignments` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  `assigned_by` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_admin_assignments`
--

INSERT INTO `group_admin_assignments` (`assignment_id`, `user_id`, `community_id`, `assigned_at`, `assigned_by`) VALUES
(1, 3, 1, '2025-11-29 17:55:41', 1),
(2, 5, 2, '2025-11-30 17:50:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lottery_books`
--

CREATE TABLE `lottery_books` (
  `book_id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `book_number` int(10) UNSIGNED NOT NULL,
  `start_ticket_number` int(10) UNSIGNED NOT NULL,
  `end_ticket_number` int(10) UNSIGNED NOT NULL,
  `book_status` enum('available','distributed','collected') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lottery_books`
--

INSERT INTO `lottery_books` (`book_id`, `event_id`, `book_number`, `start_ticket_number`, `end_ticket_number`, `book_status`, `created_at`) VALUES
(601, 5, 1, 1001, 1020, 'distributed', '2025-12-02 17:21:22'),
(602, 5, 2, 1021, 1040, 'distributed', '2025-12-02 17:21:22'),
(603, 5, 3, 1041, 1060, 'distributed', '2025-12-02 17:21:22'),
(604, 5, 4, 1061, 1080, 'distributed', '2025-12-02 17:21:22'),
(605, 5, 5, 1081, 1100, 'distributed', '2025-12-02 17:21:22'),
(606, 5, 6, 1101, 1120, 'distributed', '2025-12-02 17:21:22'),
(607, 5, 7, 1121, 1140, 'distributed', '2025-12-02 17:21:22'),
(608, 5, 8, 1141, 1160, 'distributed', '2025-12-02 17:21:22'),
(609, 5, 9, 1161, 1180, 'distributed', '2025-12-02 17:21:22'),
(610, 5, 10, 1181, 1200, 'distributed', '2025-12-02 17:21:22'),
(611, 5, 11, 1201, 1220, 'distributed', '2025-12-02 17:21:22'),
(612, 5, 12, 1221, 1240, 'distributed', '2025-12-02 17:21:22'),
(613, 5, 13, 1241, 1260, 'distributed', '2025-12-02 17:21:22'),
(614, 5, 14, 1261, 1280, 'distributed', '2025-12-02 17:21:22'),
(615, 5, 15, 1281, 1300, 'distributed', '2025-12-02 17:21:22'),
(616, 5, 16, 1301, 1320, 'distributed', '2025-12-02 17:21:22'),
(617, 5, 17, 1321, 1340, 'distributed', '2025-12-02 17:21:22'),
(618, 5, 18, 1341, 1360, 'distributed', '2025-12-02 17:21:22'),
(619, 5, 19, 1361, 1380, 'distributed', '2025-12-02 17:21:22'),
(620, 5, 20, 1381, 1400, 'distributed', '2025-12-02 17:21:22'),
(621, 5, 21, 1401, 1420, 'distributed', '2025-12-02 17:21:22'),
(622, 5, 22, 1421, 1440, 'distributed', '2025-12-02 17:21:22'),
(623, 5, 23, 1441, 1460, 'distributed', '2025-12-02 17:21:22'),
(624, 5, 24, 1461, 1480, 'distributed', '2025-12-02 17:21:22'),
(625, 5, 25, 1481, 1500, 'distributed', '2025-12-02 17:21:22'),
(626, 5, 26, 1501, 1520, 'distributed', '2025-12-02 17:21:22'),
(627, 5, 27, 1521, 1540, 'distributed', '2025-12-02 17:21:22'),
(628, 5, 28, 1541, 1560, 'distributed', '2025-12-02 17:21:22'),
(629, 5, 29, 1561, 1580, 'distributed', '2025-12-02 17:21:22'),
(630, 5, 30, 1581, 1600, 'distributed', '2025-12-02 17:21:22'),
(631, 5, 31, 1601, 1620, 'distributed', '2025-12-02 17:21:22'),
(632, 5, 32, 1621, 1640, 'distributed', '2025-12-02 17:21:22'),
(633, 5, 33, 1641, 1660, 'distributed', '2025-12-02 17:21:22'),
(634, 5, 34, 1661, 1680, 'distributed', '2025-12-02 17:21:22'),
(635, 5, 35, 1681, 1700, 'distributed', '2025-12-02 17:21:22'),
(636, 5, 36, 1701, 1720, 'distributed', '2025-12-02 17:21:22'),
(637, 5, 37, 1721, 1740, 'distributed', '2025-12-02 17:21:22'),
(638, 5, 38, 1741, 1760, 'distributed', '2025-12-02 17:21:22'),
(639, 5, 39, 1761, 1780, 'distributed', '2025-12-02 17:21:22'),
(640, 5, 40, 1781, 1800, 'distributed', '2025-12-02 17:21:22'),
(641, 5, 41, 1801, 1820, 'distributed', '2025-12-02 17:21:22'),
(642, 5, 42, 1821, 1840, 'distributed', '2025-12-02 17:21:22'),
(643, 5, 43, 1841, 1860, 'distributed', '2025-12-02 17:21:22'),
(644, 5, 44, 1861, 1880, 'distributed', '2025-12-02 17:21:22'),
(645, 5, 45, 1881, 1900, 'distributed', '2025-12-02 17:21:22'),
(646, 5, 46, 1901, 1920, 'distributed', '2025-12-02 17:21:22'),
(647, 5, 47, 1921, 1940, 'distributed', '2025-12-02 17:21:22'),
(648, 5, 48, 1941, 1960, 'distributed', '2025-12-02 17:21:22'),
(649, 5, 49, 1961, 1980, 'distributed', '2025-12-02 17:21:22'),
(650, 5, 50, 1981, 2000, 'distributed', '2025-12-02 17:21:22'),
(651, 5, 51, 2001, 2020, 'distributed', '2025-12-02 17:21:22'),
(652, 5, 52, 2021, 2040, 'distributed', '2025-12-02 17:21:22'),
(653, 5, 53, 2041, 2060, 'distributed', '2025-12-02 17:21:22'),
(654, 5, 54, 2061, 2080, 'distributed', '2025-12-02 17:21:22'),
(655, 5, 55, 2081, 2100, 'distributed', '2025-12-02 17:21:22'),
(656, 5, 56, 2101, 2120, 'distributed', '2025-12-02 17:21:22'),
(657, 5, 57, 2121, 2140, 'distributed', '2025-12-02 17:21:22'),
(658, 5, 58, 2141, 2160, 'distributed', '2025-12-02 17:21:22'),
(659, 5, 59, 2161, 2180, 'distributed', '2025-12-02 17:21:22'),
(660, 5, 60, 2181, 2200, 'distributed', '2025-12-02 17:21:22'),
(661, 5, 61, 2201, 2220, 'distributed', '2025-12-02 17:21:22'),
(662, 5, 62, 2221, 2240, 'distributed', '2025-12-02 17:21:22'),
(663, 5, 63, 2241, 2260, 'distributed', '2025-12-02 17:21:22'),
(664, 5, 64, 2261, 2280, 'distributed', '2025-12-02 17:21:22'),
(665, 5, 65, 2281, 2300, 'distributed', '2025-12-02 17:21:22'),
(666, 5, 66, 2301, 2320, 'distributed', '2025-12-02 17:21:22'),
(667, 5, 67, 2321, 2340, 'distributed', '2025-12-02 17:21:22'),
(668, 5, 68, 2341, 2360, 'distributed', '2025-12-02 17:21:22'),
(669, 5, 69, 2361, 2380, 'distributed', '2025-12-02 17:21:22'),
(670, 5, 70, 2381, 2400, 'distributed', '2025-12-02 17:21:22'),
(671, 5, 71, 2401, 2420, 'distributed', '2025-12-02 17:21:22'),
(672, 5, 72, 2421, 2440, 'distributed', '2025-12-02 17:21:22'),
(673, 5, 73, 2441, 2460, 'distributed', '2025-12-02 17:21:22'),
(674, 5, 74, 2461, 2480, 'distributed', '2025-12-02 17:21:22'),
(675, 5, 75, 2481, 2500, 'distributed', '2025-12-02 17:21:22'),
(676, 5, 76, 2501, 2520, 'distributed', '2025-12-02 17:21:22'),
(677, 5, 77, 2521, 2540, 'distributed', '2025-12-02 17:21:22'),
(678, 5, 78, 2541, 2560, 'distributed', '2025-12-02 17:21:22'),
(679, 5, 79, 2561, 2580, 'distributed', '2025-12-02 17:21:22'),
(680, 5, 80, 2581, 2600, 'distributed', '2025-12-02 17:21:22'),
(681, 5, 81, 2601, 2620, 'distributed', '2025-12-02 17:21:22'),
(682, 5, 82, 2621, 2640, 'distributed', '2025-12-02 17:21:22'),
(683, 5, 83, 2641, 2660, 'distributed', '2025-12-02 17:21:22'),
(684, 5, 84, 2661, 2680, 'distributed', '2025-12-02 17:21:22'),
(685, 5, 85, 2681, 2700, 'distributed', '2025-12-02 17:21:22'),
(686, 5, 86, 2701, 2720, 'distributed', '2025-12-02 17:21:22'),
(687, 5, 87, 2721, 2740, 'distributed', '2025-12-02 17:21:22'),
(688, 5, 88, 2741, 2760, 'distributed', '2025-12-02 17:21:22'),
(689, 5, 89, 2761, 2780, 'distributed', '2025-12-02 17:21:22'),
(690, 5, 90, 2781, 2800, 'distributed', '2025-12-02 17:21:22'),
(691, 5, 91, 2801, 2820, 'distributed', '2025-12-02 17:21:22'),
(692, 5, 92, 2821, 2840, 'distributed', '2025-12-02 17:21:22'),
(693, 5, 93, 2841, 2860, 'distributed', '2025-12-02 17:21:22'),
(694, 5, 94, 2861, 2880, 'distributed', '2025-12-02 17:21:22'),
(695, 5, 95, 2881, 2900, 'distributed', '2025-12-02 17:21:22'),
(696, 5, 96, 2901, 2920, 'distributed', '2025-12-02 17:21:22'),
(697, 5, 97, 2921, 2940, 'distributed', '2025-12-02 17:21:22'),
(698, 5, 98, 2941, 2960, 'distributed', '2025-12-02 17:21:22'),
(699, 5, 99, 2961, 2980, 'distributed', '2025-12-02 17:21:22'),
(700, 5, 100, 2981, 3000, 'distributed', '2025-12-02 17:21:22'),
(701, 5, 101, 3001, 3020, 'distributed', '2025-12-02 17:21:22'),
(702, 5, 102, 3021, 3040, 'distributed', '2025-12-02 17:21:22'),
(703, 5, 103, 3041, 3060, 'distributed', '2025-12-02 17:21:22'),
(704, 5, 104, 3061, 3080, 'distributed', '2025-12-02 17:21:22'),
(705, 5, 105, 3081, 3100, 'distributed', '2025-12-02 17:21:22'),
(706, 5, 106, 3101, 3120, 'distributed', '2025-12-02 17:21:22'),
(707, 5, 107, 3121, 3140, 'distributed', '2025-12-02 17:21:22'),
(708, 5, 108, 3141, 3160, 'distributed', '2025-12-02 17:21:22'),
(709, 5, 109, 3161, 3180, 'distributed', '2025-12-02 17:21:22'),
(710, 5, 110, 3181, 3200, 'distributed', '2025-12-02 17:21:22'),
(711, 5, 111, 3201, 3220, 'distributed', '2025-12-02 17:21:22'),
(712, 5, 112, 3221, 3240, 'distributed', '2025-12-02 17:21:22'),
(713, 5, 113, 3241, 3260, 'distributed', '2025-12-02 17:21:22'),
(714, 5, 114, 3261, 3280, 'distributed', '2025-12-02 17:21:22'),
(715, 5, 115, 3281, 3300, 'distributed', '2025-12-02 17:21:22'),
(716, 5, 116, 3301, 3320, 'distributed', '2025-12-02 17:21:22'),
(717, 5, 117, 3321, 3340, 'distributed', '2025-12-02 17:21:22'),
(718, 5, 118, 3341, 3360, 'distributed', '2025-12-02 17:21:22'),
(719, 5, 119, 3361, 3380, 'distributed', '2025-12-02 17:21:22'),
(720, 5, 120, 3381, 3400, 'distributed', '2025-12-02 17:21:22'),
(721, 5, 121, 3401, 3420, 'distributed', '2025-12-02 17:21:22'),
(722, 5, 122, 3421, 3440, 'distributed', '2025-12-02 17:21:22'),
(723, 5, 123, 3441, 3460, 'distributed', '2025-12-02 17:21:22'),
(724, 5, 124, 3461, 3480, 'distributed', '2025-12-02 17:21:22'),
(725, 5, 125, 3481, 3500, 'distributed', '2025-12-02 17:21:22'),
(726, 5, 126, 3501, 3520, 'distributed', '2025-12-02 17:21:22'),
(727, 5, 127, 3521, 3540, 'distributed', '2025-12-02 17:21:22'),
(728, 5, 128, 3541, 3560, 'distributed', '2025-12-02 17:21:22'),
(729, 5, 129, 3561, 3580, 'distributed', '2025-12-02 17:21:22'),
(730, 5, 130, 3581, 3600, 'distributed', '2025-12-02 17:21:22'),
(731, 5, 131, 3601, 3620, 'distributed', '2025-12-02 17:21:22'),
(732, 5, 132, 3621, 3640, 'distributed', '2025-12-02 17:21:22'),
(733, 5, 133, 3641, 3660, 'distributed', '2025-12-02 17:21:22'),
(734, 5, 134, 3661, 3680, 'distributed', '2025-12-02 17:21:22'),
(735, 5, 135, 3681, 3700, 'distributed', '2025-12-02 17:21:22'),
(736, 5, 136, 3701, 3720, 'distributed', '2025-12-02 17:21:22'),
(737, 5, 137, 3721, 3740, 'distributed', '2025-12-02 17:21:22'),
(738, 5, 138, 3741, 3760, 'distributed', '2025-12-02 17:21:22'),
(739, 5, 139, 3761, 3780, 'distributed', '2025-12-02 17:21:22'),
(740, 5, 140, 3781, 3800, 'distributed', '2025-12-02 17:21:22'),
(741, 5, 141, 3801, 3820, 'distributed', '2025-12-02 17:21:22'),
(742, 5, 142, 3821, 3840, 'distributed', '2025-12-02 17:21:22'),
(743, 5, 143, 3841, 3860, 'distributed', '2025-12-02 17:21:22'),
(744, 5, 144, 3861, 3880, 'distributed', '2025-12-02 17:21:22'),
(745, 5, 145, 3881, 3900, 'distributed', '2025-12-02 17:21:22'),
(746, 5, 146, 3901, 3920, 'distributed', '2025-12-02 17:21:22'),
(747, 5, 147, 3921, 3940, 'distributed', '2025-12-02 17:21:22'),
(748, 5, 148, 3941, 3960, 'distributed', '2025-12-02 17:21:22'),
(749, 5, 149, 3961, 3980, 'distributed', '2025-12-02 17:21:22'),
(750, 5, 150, 3981, 4000, 'distributed', '2025-12-02 17:21:22'),
(751, 5, 151, 4001, 4020, 'distributed', '2025-12-02 17:21:22'),
(752, 5, 152, 4021, 4040, 'distributed', '2025-12-02 17:21:22'),
(753, 5, 153, 4041, 4060, 'distributed', '2025-12-02 17:21:22'),
(754, 5, 154, 4061, 4080, 'distributed', '2025-12-02 17:21:22'),
(755, 5, 155, 4081, 4100, 'distributed', '2025-12-02 17:21:22'),
(756, 5, 156, 4101, 4120, 'distributed', '2025-12-02 17:21:22'),
(757, 5, 157, 4121, 4140, 'distributed', '2025-12-02 17:21:22'),
(758, 5, 158, 4141, 4160, 'distributed', '2025-12-02 17:21:22'),
(759, 5, 159, 4161, 4180, 'distributed', '2025-12-02 17:21:22'),
(760, 5, 160, 4181, 4200, 'distributed', '2025-12-02 17:21:22'),
(761, 5, 161, 4201, 4220, 'distributed', '2025-12-02 17:21:22'),
(762, 5, 162, 4221, 4240, 'distributed', '2025-12-02 17:21:22'),
(763, 5, 163, 4241, 4260, 'distributed', '2025-12-02 17:21:22'),
(764, 5, 164, 4261, 4280, 'distributed', '2025-12-02 17:21:22'),
(765, 5, 165, 4281, 4300, 'distributed', '2025-12-02 17:21:22'),
(766, 5, 166, 4301, 4320, 'distributed', '2025-12-02 17:21:22'),
(767, 5, 167, 4321, 4340, 'distributed', '2025-12-02 17:21:22'),
(768, 5, 168, 4341, 4360, 'distributed', '2025-12-02 17:21:22'),
(769, 5, 169, 4361, 4380, 'distributed', '2025-12-02 17:21:22'),
(770, 5, 170, 4381, 4400, 'distributed', '2025-12-02 17:21:22'),
(771, 5, 171, 4401, 4420, 'distributed', '2025-12-02 17:21:22'),
(772, 5, 172, 4421, 4440, 'distributed', '2025-12-02 17:21:22'),
(773, 5, 173, 4441, 4460, 'distributed', '2025-12-02 17:21:22'),
(774, 5, 174, 4461, 4480, 'distributed', '2025-12-02 17:21:22'),
(775, 5, 175, 4481, 4500, 'distributed', '2025-12-02 17:21:22'),
(776, 5, 176, 4501, 4520, 'distributed', '2025-12-02 17:21:22'),
(777, 5, 177, 4521, 4540, 'distributed', '2025-12-02 17:21:22'),
(778, 5, 178, 4541, 4560, 'distributed', '2025-12-02 17:21:22'),
(779, 5, 179, 4561, 4580, 'distributed', '2025-12-02 17:21:22'),
(780, 5, 180, 4581, 4600, 'distributed', '2025-12-02 17:21:22'),
(781, 5, 181, 4601, 4620, 'distributed', '2025-12-02 17:21:22'),
(782, 5, 182, 4621, 4640, 'distributed', '2025-12-02 17:21:22'),
(783, 5, 183, 4641, 4660, 'distributed', '2025-12-02 17:21:22'),
(784, 5, 184, 4661, 4680, 'distributed', '2025-12-02 17:21:22'),
(785, 5, 185, 4681, 4700, 'available', '2025-12-02 17:21:22'),
(786, 5, 186, 4701, 4720, 'distributed', '2025-12-02 17:21:22'),
(787, 5, 187, 4721, 4740, 'available', '2025-12-02 17:21:22'),
(788, 5, 188, 4741, 4760, 'available', '2025-12-02 17:21:22'),
(789, 5, 189, 4761, 4780, 'available', '2025-12-02 17:21:22'),
(790, 5, 190, 4781, 4800, 'available', '2025-12-02 17:21:22'),
(791, 5, 191, 4801, 4820, 'available', '2025-12-02 17:21:22'),
(792, 5, 192, 4821, 4840, 'available', '2025-12-02 17:21:22'),
(793, 5, 193, 4841, 4860, 'available', '2025-12-02 17:21:22'),
(794, 5, 194, 4861, 4880, 'available', '2025-12-02 17:21:22'),
(795, 5, 195, 4881, 4900, 'available', '2025-12-02 17:21:22'),
(796, 5, 196, 4901, 4920, 'available', '2025-12-02 17:21:22'),
(797, 5, 197, 4921, 4940, 'available', '2025-12-02 17:21:22'),
(798, 5, 198, 4941, 4960, 'available', '2025-12-02 17:21:22'),
(799, 5, 199, 4961, 4980, 'available', '2025-12-02 17:21:22'),
(800, 5, 200, 4981, 5000, 'available', '2025-12-02 17:21:22'),
(801, 6, 1, 10012, 10021, 'distributed', '2025-12-04 10:35:10'),
(802, 6, 2, 10022, 10031, 'distributed', '2025-12-04 10:35:10'),
(803, 6, 3, 10032, 10041, 'distributed', '2025-12-04 10:35:10'),
(804, 6, 4, 10042, 10051, 'distributed', '2025-12-04 10:35:10'),
(805, 6, 5, 10052, 10061, 'distributed', '2025-12-04 10:35:10'),
(806, 6, 6, 10062, 10071, 'distributed', '2025-12-04 10:35:10'),
(807, 6, 7, 10072, 10081, 'distributed', '2025-12-04 10:35:10'),
(808, 6, 8, 10082, 10091, 'distributed', '2025-12-04 10:35:10'),
(809, 6, 9, 10092, 10101, 'distributed', '2025-12-04 10:35:10'),
(810, 6, 10, 10102, 10111, 'distributed', '2025-12-04 10:35:10');

-- --------------------------------------------------------

--
-- Table structure for table `lottery_events`
--

CREATE TABLE `lottery_events` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `event_name` varchar(150) NOT NULL,
  `event_description` text DEFAULT NULL,
  `total_books` int(10) UNSIGNED NOT NULL,
  `tickets_per_book` int(10) UNSIGNED NOT NULL,
  `price_per_ticket` decimal(10,2) NOT NULL,
  `first_ticket_number` int(10) UNSIGNED NOT NULL,
  `total_tickets` int(10) UNSIGNED GENERATED ALWAYS AS (`total_books` * `tickets_per_book`) STORED,
  `total_predicted_amount` decimal(12,2) GENERATED ALWAYS AS (`total_books` * `tickets_per_book` * `price_per_ticket`) STORED,
  `status` enum('draft','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `book_return_deadline` date DEFAULT NULL COMMENT 'Date after which non-returned books are flagged'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lottery_events`
--

INSERT INTO `lottery_events` (`event_id`, `community_id`, `event_name`, `event_description`, `total_books`, `tickets_per_book`, `price_per_ticket`, `first_ticket_number`, `status`, `created_by`, `created_at`, `updated_at`, `book_return_deadline`) VALUES
(5, 2, 'Christmas 25', '', 200, 20, 100.00, 1001, 'active', 5, '2025-12-02 17:20:51', '2025-12-02 17:21:22', NULL),
(6, 1, 'Testing Evenett', '', 10, 10, 100.00, 10012, 'active', 3, '2025-12-04 10:34:53', '2025-12-04 10:35:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lottery_winners`
--

CREATE TABLE `lottery_winners` (
  `winner_id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `ticket_number` int(10) UNSIGNED NOT NULL,
  `prize_position` enum('1st','2nd','3rd','consolation') NOT NULL,
  `book_number` int(10) UNSIGNED DEFAULT NULL,
  `distribution_path` varchar(255) DEFAULT NULL COMMENT 'Full location path (Level 1 > Level 2 > Level 3)',
  `winner_name` varchar(100) DEFAULT NULL COMMENT 'Winner name from lottery or manual entry',
  `winner_contact` varchar(15) DEFAULT NULL COMMENT 'Winner mobile number',
  `added_by` int(10) UNSIGNED NOT NULL,
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lottery_winners`
--

INSERT INTO `lottery_winners` (`winner_id`, `event_id`, `ticket_number`, `prize_position`, `book_number`, `distribution_path`, `winner_name`, `winner_contact`, `added_by`, `added_at`, `updated_at`) VALUES
(2, 6, 10014, '1st', 1, 'C > 1', 'Testing', '9898989889', 3, '2025-12-05 10:32:46', '2025-12-05 10:36:14'),
(3, 6, 10044, 'consolation', 4, 'A > 1', '', '', 3, '2025-12-05 10:36:32', '2025-12-05 10:36:32'),
(5, 5, 1676, 'consolation', 34, 'St. Anthony’s Unit', '', '', 5, '2025-12-24 16:46:46', '2025-12-24 16:46:46'),
(6, 5, 1484, 'consolation', 25, 'Infant Jesus Unit', '', '', 5, '2025-12-24 16:47:02', '2025-12-24 16:47:02'),
(7, 5, 1637, 'consolation', 32, 'Infant Jesus Unit', 'Justin', '', 5, '2025-12-24 16:57:46', '2025-12-24 16:57:46'),
(8, 5, 4608, 'consolation', 181, 'DSYM Unit', '', '', 5, '2025-12-24 16:57:55', '2025-12-24 16:57:55'),
(9, 5, 2669, 'consolation', 84, 'St. Sebastian’s Unit', 'Christina', '', 5, '2025-12-24 16:58:17', '2025-12-24 16:58:17'),
(10, 5, 3954, 'consolation', 148, 'St. Cecila Unit', 'Sandeep', '', 5, '2025-12-24 16:59:11', '2025-12-24 16:59:11'),
(11, 5, 3925, 'consolation', 147, 'St. Cecila Unit', 'Tharun', '', 5, '2025-12-24 16:59:40', '2025-12-24 16:59:40'),
(12, 5, 2435, 'consolation', 72, 'St. Mathew Unit', 'Angel', '', 5, '2025-12-24 17:00:01', '2025-12-24 17:00:01'),
(13, 5, 4287, 'consolation', 165, 'DSYM Unit', 'Alwin', '', 5, '2025-12-24 17:00:20', '2025-12-24 17:00:20'),
(14, 5, 1528, 'consolation', 27, 'Infant Jesus Unit', 'Liya', '', 5, '2025-12-24 17:00:36', '2025-12-24 17:00:36'),
(15, 5, 1510, 'consolation', 26, 'Infant Jesus Unit', 'B1/188', '', 5, '2025-12-24 17:00:54', '2025-12-24 17:00:54'),
(16, 5, 3886, 'consolation', 145, 'St. Cecila Unit', 'Jainamma', '', 5, '2025-12-24 17:01:20', '2025-12-24 17:01:20'),
(17, 5, 1487, 'consolation', 25, 'Infant Jesus Unit', 'Sorrabh', '', 5, '2025-12-24 17:01:41', '2025-12-24 17:01:41'),
(18, 5, 3201, 'consolation', 111, 'St. Chavara Unit', 'Santosh', '', 5, '2025-12-24 17:01:56', '2025-12-24 17:01:56'),
(19, 5, 2518, 'consolation', 76, 'St. Mathew Unit', 'Vemina', '', 5, '2025-12-24 17:02:13', '2025-12-24 17:02:13'),
(20, 5, 2657, 'consolation', 83, 'St. Sebastian’s Unit', 'Mary', '', 5, '2025-12-24 17:02:28', '2025-12-24 17:02:28'),
(21, 5, 1964, 'consolation', 49, 'St. George Unit', 'Mr shivam', '', 5, '2025-12-24 17:02:46', '2025-12-24 17:02:46'),
(22, 5, 4183, 'consolation', 160, 'St. Paul Unit', 'Johney', '', 5, '2025-12-24 17:03:04', '2025-12-24 17:03:04'),
(23, 5, 2423, 'consolation', 72, 'St. Mathew Unit', 'Rosemary', '', 5, '2025-12-24 17:03:21', '2025-12-24 17:03:21'),
(24, 5, 1938, 'consolation', 47, 'St. George Unit', 'Jais George', '', 5, '2025-12-24 17:03:39', '2025-12-24 17:03:39'),
(25, 5, 1846, 'consolation', 43, 'St. George Unit', 'Siji', '', 5, '2025-12-24 17:03:54', '2025-12-24 17:03:54'),
(26, 5, 1893, 'consolation', 45, 'St. George Unit', 'Jessy', '', 5, '2025-12-24 17:04:09', '2025-12-24 17:04:09'),
(27, 5, 2185, 'consolation', 60, 'St. Thomas Unit', 'Nelson', '', 5, '2025-12-24 17:04:24', '2025-12-24 17:04:24'),
(28, 5, 2620, 'consolation', 81, 'St. Mathew Unit', '', '', 5, '2025-12-24 17:04:35', '2025-12-24 17:04:35'),
(29, 5, 2187, 'consolation', 60, 'St. Thomas Unit', 'Jeemol', '', 5, '2025-12-24 17:04:55', '2025-12-24 17:04:55'),
(30, 5, 3628, 'consolation', 132, 'St. Peter Unit', 'Neha', '', 5, '2025-12-24 17:05:07', '2025-12-24 17:05:07'),
(31, 5, 1543, 'consolation', 28, 'Infant Jesus Unit', 'Lilly', '', 5, '2025-12-24 17:05:20', '2025-12-24 17:05:20'),
(32, 5, 3220, 'consolation', 111, 'St. Chavara Unit', 'Naren', '', 5, '2025-12-24 17:05:44', '2025-12-24 17:05:44'),
(33, 5, 1626, 'consolation', 32, 'Infant Jesus Unit', 'Aida', '', 5, '2025-12-24 17:05:59', '2025-12-24 17:05:59'),
(34, 5, 4618, 'consolation', 181, 'DSYM Unit', '', '', 5, '2025-12-24 17:06:09', '2025-12-24 17:06:09'),
(35, 5, 2421, 'consolation', 72, 'St. Mathew Unit', 'Nikhil', '', 5, '2025-12-24 17:06:24', '2025-12-24 17:06:24'),
(36, 5, 1278, 'consolation', 14, 'St. Mary’s Unit', 'Edwin', '', 5, '2025-12-24 17:06:37', '2025-12-24 17:06:37'),
(37, 5, 3217, 'consolation', 111, 'St. Chavara Unit', 'Navijeet', '', 5, '2025-12-24 17:06:55', '2025-12-24 17:06:55'),
(38, 5, 3703, 'consolation', 136, 'St. Peter Unit', 'Guffan', '', 5, '2025-12-24 17:07:14', '2025-12-24 17:07:14'),
(39, 5, 1515, 'consolation', 26, 'Infant Jesus Unit', 'B1/188', '', 5, '2025-12-24 17:07:31', '2025-12-24 17:07:31'),
(40, 5, 2340, 'consolation', 67, 'St. Mathew Unit', 'Delvin', '', 5, '2025-12-24 17:07:49', '2025-12-24 17:07:49'),
(41, 5, 3959, 'consolation', 148, 'St. Cecila Unit', 'Sandeep', '', 5, '2025-12-24 17:08:06', '2025-12-24 17:08:06'),
(42, 5, 1092, 'consolation', 5, 'St. Mary’s Unit', 'Molly', '', 5, '2025-12-24 17:08:22', '2025-12-24 17:08:22'),
(43, 5, 2261, 'consolation', 64, 'St. Thomas Unit', 'Eva', '', 5, '2025-12-24 17:08:36', '2025-12-24 17:08:36'),
(44, 5, 3412, 'consolation', 121, 'St. Xavier Unit', 'Deepak', '', 5, '2025-12-24 17:08:53', '2025-12-24 17:08:53'),
(45, 5, 2841, 'consolation', 93, 'BI. Mariam Thresia Unit', 'George', '', 5, '2025-12-24 17:09:08', '2025-12-24 17:09:08'),
(46, 5, 2357, 'consolation', 68, 'St. Mathew Unit', 'Lorah', '', 5, '2025-12-24 17:09:25', '2025-12-24 17:09:25'),
(47, 5, 2459, 'consolation', 73, 'St. Mathew Unit', 'Toji', '', 5, '2025-12-24 17:09:41', '2025-12-24 17:09:41'),
(48, 5, 4611, 'consolation', 181, 'DSYM Unit', '', '', 5, '2025-12-24 17:09:53', '2025-12-24 17:09:53'),
(49, 5, 4073, 'consolation', 154, 'St. Cecila Unit', 'Ryan anson', '', 5, '2025-12-24 17:10:11', '2025-12-24 17:10:11'),
(50, 5, 3466, 'consolation', 124, 'St. Stephen Unit', 'Aniumoh', '', 5, '2025-12-24 17:10:28', '2025-12-24 17:10:28'),
(51, 5, 1572, 'consolation', 29, 'Infant Jesus Unit', 'Sujatha', '', 5, '2025-12-24 17:10:51', '2025-12-24 17:10:51'),
(52, 5, 3136, 'consolation', 107, 'St. Chavara Unit', 'Sejmon', '', 5, '2025-12-24 17:11:09', '2025-12-24 17:11:09'),
(53, 5, 2714, 'consolation', 86, 'St. Sebastian’s Unit', 'P.v paulose', '', 5, '2025-12-24 17:11:28', '2025-12-24 17:11:28'),
(54, 5, 2842, 'consolation', 93, 'BI. Mariam Thresia Unit', 'George', '', 5, '2025-12-24 17:11:45', '2025-12-24 17:11:45'),
(55, 5, 1336, '3rd', 17, 'St. Joseph’s Unit', 'Franci', '9810145783', 5, '2025-12-24 17:12:17', '2025-12-24 17:12:17'),
(56, 5, 2686, '2nd', 85, 'St. Sebastian’s Unit', 'Anil', '9891043530', 5, '2025-12-24 17:12:35', '2025-12-24 17:12:35'),
(57, 5, 4152, '1st', 158, 'St. Paul Unit', 'Vedika', '9999190675', 5, '2025-12-24 17:13:06', '2025-12-24 17:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `payment_collections`
--

CREATE TABLE `payment_collections` (
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `distribution_id` int(10) UNSIGNED NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','upi','bank','other') NOT NULL DEFAULT 'cash',
  `payment_date` date NOT NULL,
  `payment_status` enum('paid','no_payment_book_returned') DEFAULT 'paid' COMMENT 'Payment status or book return',
  `return_reason` text DEFAULT NULL COMMENT 'Reason for book return if no payment',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT 'Can this payment be edited',
  `payment_notes` text DEFAULT NULL,
  `collected_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_collections`
--

INSERT INTO `payment_collections` (`payment_id`, `distribution_id`, `amount_paid`, `payment_method`, `payment_date`, `payment_status`, `return_reason`, `is_editable`, `payment_notes`, `collected_by`, `created_at`) VALUES
(1, 1, 1000.00, 'other', '2025-11-29', 'paid', NULL, 1, NULL, 3, '2025-11-29 18:05:47'),
(2, 1, 500.00, 'upi', '2025-11-30', 'paid', NULL, 1, NULL, 3, '2025-11-29 18:51:18'),
(3, 1, 500.00, 'bank', '2025-11-30', 'paid', NULL, 1, NULL, 3, '2025-11-30 08:34:23'),
(4, 2, 2000.00, 'cash', '2025-11-30', 'paid', NULL, 1, NULL, 3, '2025-11-30 09:42:13'),
(5, 3, 1000.00, 'bank', '2025-11-30', 'paid', NULL, 1, NULL, 3, '2025-11-30 09:43:07'),
(6, 2, 0.00, 'upi', '2025-11-30', 'paid', NULL, 1, NULL, 3, '2025-11-30 16:49:24'),
(7, 3, 800.00, 'upi', '2025-12-02', 'paid', NULL, 1, NULL, 3, '2025-12-02 13:12:56'),
(10, 173, 1000.00, 'cash', '2025-12-04', 'paid', NULL, 1, NULL, 3, '2025-12-04 11:16:36'),
(11, 174, 500.00, 'bank', '2025-12-04', 'paid', NULL, 1, NULL, 3, '2025-12-04 11:17:30'),
(12, 175, 1000.00, 'other', '2025-12-04', 'paid', NULL, 1, NULL, 3, '2025-12-04 11:21:00'),
(13, 174, 500.00, 'bank', '2025-12-04', 'paid', NULL, 1, NULL, 3, '2025-12-04 11:27:57'),
(14, 182, 1000.00, 'cash', '2025-12-04', 'paid', NULL, 1, NULL, 3, '2025-12-04 17:44:28'),
(15, 109, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 04:42:27'),
(16, 110, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 04:42:58'),
(17, 70, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(18, 71, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(19, 72, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(20, 73, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(21, 74, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(22, 75, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(23, 76, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(24, 77, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(25, 78, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(26, 79, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(27, 80, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(28, 81, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(29, 82, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(30, 83, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(31, 84, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(32, 85, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(33, 183, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(34, 184, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(35, 185, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(36, 186, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-14 14:20:15'),
(37, 126, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(38, 127, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(39, 128, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(40, 129, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(41, 130, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(42, 131, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(43, 132, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(44, 134, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:16'),
(45, 133, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:44:46'),
(46, 190, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:49:41'),
(47, 191, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:49:41'),
(48, 192, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:49:41'),
(49, 193, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:49:41'),
(50, 194, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:49:41'),
(51, 195, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:49:41'),
(52, 196, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:50:08'),
(53, 197, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:50:08'),
(54, 198, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:50:08'),
(55, 5, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:51:03'),
(56, 148, 1500.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:53:14'),
(57, 148, 500.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:53:24'),
(58, 36, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(59, 37, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(60, 38, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(61, 39, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(62, 40, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(63, 41, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(64, 42, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:56:58'),
(65, 43, 400.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:57:41'),
(66, 27, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:59:41'),
(67, 28, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:59:41'),
(68, 29, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:59:41'),
(69, 30, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:59:41'),
(70, 31, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 04:59:41'),
(72, 6, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:12:16'),
(73, 7, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:12:16'),
(74, 8, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:12:16'),
(75, 9, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:14:24'),
(76, 10, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:14:24'),
(77, 11, 1000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:15:38'),
(78, 12, 2000.00, 'upi', '2025-12-21', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:17:04'),
(79, 15, 100.00, 'upi', '2025-12-21', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:17:41'),
(80, 13, 1000.00, 'upi', '2025-12-21', 'paid', NULL, 1, NULL, 5, '2025-12-21 05:18:15'),
(83, 95, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(84, 96, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(85, 97, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(86, 98, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(87, 99, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(88, 100, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(89, 101, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(91, 4, 2000.00, 'cash', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(92, 104, 1000.00, 'cash', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(93, 105, 400.00, 'upi', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(94, 106, 200.00, 'upi', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(95, 107, 2000.00, 'upi', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(96, 108, 400.00, 'upi', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(99, 33, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(100, 34, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(101, 35, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(102, 135, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(103, 136, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(104, 137, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(105, 138, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(106, 139, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(107, 140, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(108, 141, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(109, 142, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(110, 143, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(111, 144, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(112, 145, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(113, 146, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(114, 147, 400.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(115, 111, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(116, 112, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(117, 113, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(118, 114, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(119, 117, 2000.00, 'cash', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(120, 46, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(121, 47, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(122, 48, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(123, 49, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(124, 50, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(125, 51, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(126, 52, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(127, 53, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(128, 54, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(129, 55, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(130, 56, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(131, 57, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(132, 19, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(133, 20, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(134, 21, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(135, 22, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(136, 23, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(137, 24, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(138, 25, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(139, 26, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(140, 86, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(141, 87, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(142, 88, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(143, 89, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(144, 90, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(145, 91, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(146, 92, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(147, 93, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(148, 94, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(149, 120, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(150, 121, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(151, 122, 800.00, 'upi', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(152, 123, 1300.00, 'upi', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(153, 124, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(154, 125, 2000.00, 'upi', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(155, 58, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(156, 59, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(157, 60, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(158, 61, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(159, 62, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(160, 63, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(161, 64, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(162, 65, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(163, 66, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(164, 67, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(165, 68, 2000.00, 'cash', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(166, 69, 2000.00, 'cash', '2025-12-24', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(167, 168, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(168, 169, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(169, 170, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(170, 171, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(171, 172, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(173, 188, 2000.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2025-12-25 11:18:51'),
(177, 189, 400.00, 'cash', '2025-12-14', 'paid', NULL, 1, NULL, 5, '2026-01-03 16:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `payment_edit_history`
--

CREATE TABLE `payment_edit_history` (
  `edit_id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `old_amount` decimal(10,2) NOT NULL,
  `old_payment_method` enum('cash','upi','bank','other') NOT NULL,
  `old_payment_date` date NOT NULL,
  `new_amount` decimal(10,2) NOT NULL,
  `new_payment_method` enum('cash','upi','bank','other') NOT NULL,
  `new_payment_date` date NOT NULL,
  `edit_reason` text NOT NULL COMMENT 'Reason for editing payment',
  `edited_by` int(10) UNSIGNED NOT NULL,
  `edited_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_health_metrics`
--

CREATE TABLE `system_health_metrics` (
  `metric_id` int(11) NOT NULL,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `metric_unit` varchar(20) DEFAULT NULL COMMENT 'percentage, MB, seconds, etc.',
  `status` enum('healthy','warning','critical') NOT NULL DEFAULT 'healthy',
  `recorded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_health_metrics`
--

INSERT INTO `system_health_metrics` (`metric_id`, `metric_name`, `metric_value`, `metric_unit`, `status`, `recorded_at`) VALUES
(1, 'disk_space_used', 0.00, 'percentage', 'healthy', '2025-12-03 03:49:50'),
(2, 'database_status', 1.00, 'boolean', 'healthy', '2025-12-03 03:49:50'),
(3, 'avg_response_time', 0.00, 'seconds', 'healthy', '2025-12-03 03:49:50'),
(4, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:10:56'),
(5, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:13:05'),
(6, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:13:06'),
(7, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:13:09'),
(8, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:13:34'),
(9, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:14:40'),
(10, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:15:38'),
(11, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:15:51'),
(12, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:15:51'),
(13, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:16:51'),
(14, 'disk_space_used', 60.85, 'percentage', 'healthy', '2025-12-03 04:33:30'),
(15, 'disk_space_used', 60.74, 'percentage', 'healthy', '2025-12-05 11:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `log_type` enum('error','warning','info','security') NOT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL DEFAULT 'low',
  `message` text NOT NULL,
  `details` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `line_number` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `updated_at`, `updated_by`) VALUES
(1, 'platform_name', 'GetToKnow', 'string', 'Platform display name', 1, '2026-01-11 18:09:23', NULL),
(2, 'platform_version', '4.0', 'string', 'Current platform version', 0, '2026-01-11 18:09:23', NULL),
(3, 'max_communities_per_admin', '1', 'number', 'Maximum communities per Group Admin in Phase 1', 1, '2026-01-11 18:09:23', NULL),
(4, 'session_timeout', '30', 'number', 'Session timeout in minutes', 1, '2026-01-11 18:09:23', NULL),
(5, 'enable_activity_logs', 'true', 'boolean', 'Enable activity logging', 1, '2026-01-11 18:09:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `plain_password` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','group_admin') NOT NULL DEFAULT 'group_admin',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `mobile_number`, `password_hash`, `plain_password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, '9999999999', '$2y$10$ZQxZ5Hzqby.YY7BVKzI1Gef/i3loAzY3M1nfMYfV7ZwJMk0l3Ct66', '123456', 'System Admin', 'admin@zatana.in', 'admin', 'active', '2025-11-28 18:51:03', '2026-01-11 17:34:50', '2026-01-11 17:34:50'),
(3, '7899015076', '$2y$10$xqxDBuxXv3xAB1V6kCy7P.GrD1DiUsmIXnYjxogzP.WMpH7Ko4eLq', 'ghj12345+A', 'Testing', '', 'group_admin', 'active', '2025-11-28 19:40:11', '2026-01-11 17:36:06', '2026-01-11 17:36:06'),
(5, '9810154881', '$2y$10$Rzigc4kVJfMZ9Y4TAwu9cOoV9pZlTjbfYXRkQNc7wUxaIFyZZL66y', '123456', 'A.D.Thomas', '', 'group_admin', 'active', '2025-11-30 17:49:50', '2026-01-11 16:15:16', '2026-01-11 16:15:16');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_commission_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_commission_summary` (
`event_id` int(10) unsigned
,`event_name` varchar(150)
,`level_1_value` varchar(100)
,`commission_type` enum('early','standard','extra_books')
,`payment_count` bigint(21)
,`total_payment_amount` decimal(32,2)
,`total_commission_earned` decimal(32,2)
,`avg_commission_percent` decimal(9,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_lottery_event_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_lottery_event_summary` (
`event_id` int(10) unsigned
,`event_name` varchar(150)
,`community_name` varchar(150)
,`total_books` int(10) unsigned
,`total_tickets` int(10) unsigned
,`total_predicted_amount` decimal(12,2)
,`books_distributed` bigint(21)
,`total_collected` decimal(32,2)
,`status` enum('draft','active','completed','cancelled')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `view_transaction_campaign_summary`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`u717011923_gettoknow_db`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_transaction_campaign_summary`  AS SELECT `tc`.`campaign_id` AS `campaign_id`, `tc`.`campaign_name` AS `campaign_name`, `c`.`community_name` AS `community_name`, `tc`.`total_members` AS `total_members`, `tc`.`total_expected_amount` AS `total_expected_amount`, count(case when `cm`.`payment_status` = 'paid' then 1 end) AS `paid_members`, count(case when `cm`.`payment_status` = 'partial' then 1 end) AS `partial_members`, count(case when `cm`.`payment_status` = 'unpaid' then 1 end) AS `unpaid_members`, coalesce(sum(`cm`.`total_paid`),0) AS `total_collected`, `tc`.`status` AS `status`, `tc`.`created_at` AS `created_at` FROM ((`transaction_campaigns` `tc` left join `communities` `c` on(`tc`.`community_id` = `c`.`community_id`)) left join `campaign_members` `cm` on(`tc`.`campaign_id` = `cm`.`campaign_id`)) GROUP BY `tc`.`campaign_id` ;
-- Error reading data for table u717011923_gettoknow_db.view_transaction_campaign_summary: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `u717011923_gettoknow_db`.`view_transaction_campaign_summary`' at line 1

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_winners_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_winners_summary` (
`event_id` int(10) unsigned
,`event_name` varchar(150)
,`prize_position` enum('1st','2nd','3rd','consolation')
,`ticket_number` int(10) unsigned
,`book_number` int(10) unsigned
,`distribution_path` varchar(255)
,`winner_name` varchar(100)
,`winner_contact` varchar(15)
,`added_at` timestamp
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_feature_key` (`feature_key`);

--
-- Indexes for table `alert_notifications`
--
ALTER TABLE `alert_notifications`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_is_resolved` (`is_resolved`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_acknowledged_by` (`acknowledged_by`);

--
-- Indexes for table `book_distribution`
--
ALTER TABLE `book_distribution`
  ADD PRIMARY KEY (`distribution_id`),
  ADD UNIQUE KEY `unique_book_dist` (`book_id`),
  ADD KEY `level_1_value_id` (`level_1_value_id`),
  ADD KEY `level_2_value_id` (`level_2_value_id`),
  ADD KEY `level_3_value_id` (`level_3_value_id`),
  ADD KEY `distributed_by` (`distributed_by`),
  ADD KEY `idx_book` (`book_id`),
  ADD KEY `idx_mobile` (`mobile_number`),
  ADD KEY `fk_returned_by` (`returned_by`),
  ADD KEY `idx_is_returned` (`is_returned`);

--
-- Indexes for table `commission_earned`
--
ALTER TABLE `commission_earned`
  ADD PRIMARY KEY (`commission_id`),
  ADD KEY `payment_collection_id` (`payment_collection_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_level_1` (`level_1_value`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_commission_event_level` (`event_id`,`level_1_value`),
  ADD KEY `idx_distribution_id` (`distribution_id`);

--
-- Indexes for table `commission_settings`
--
ALTER TABLE `commission_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_event_commission` (`event_id`),
  ADD KEY `idx_event` (`event_id`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`community_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `community_features`
--
ALTER TABLE `community_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_community_feature` (`community_id`,`feature_id`),
  ADD KEY `enabled_by` (`enabled_by`),
  ADD KEY `disabled_by` (`disabled_by`),
  ADD KEY `idx_community` (`community_id`),
  ADD KEY `idx_feature` (`feature_id`),
  ADD KEY `idx_enabled` (`is_enabled`);

--
-- Indexes for table `community_settings`
--
ALTER TABLE `community_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_community_setting` (`community_id`,`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_community` (`community_id`);

--
-- Indexes for table `database_connection_logs`
--
ALTER TABLE `database_connection_logs`
  ADD PRIMARY KEY (`connection_id`),
  ADD KEY `idx_connection_status` (`connection_status`),
  ADD KEY `idx_checked_at` (`checked_at`);

--
-- Indexes for table `deletion_requests`
--
ALTER TABLE `deletion_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_request_type` (`request_type`),
  ADD KEY `idx_requested_by` (`requested_by`),
  ADD KEY `idx_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `distribution_levels`
--
ALTER TABLE `distribution_levels`
  ADD PRIMARY KEY (`level_id`),
  ADD UNIQUE KEY `unique_level` (`event_id`,`level_number`),
  ADD KEY `idx_event` (`event_id`);

--
-- Indexes for table `distribution_level_values`
--
ALTER TABLE `distribution_level_values`
  ADD PRIMARY KEY (`value_id`),
  ADD KEY `idx_level` (`level_id`),
  ADD KEY `idx_parent` (`parent_value_id`);

--
-- Indexes for table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`feature_id`),
  ADD UNIQUE KEY `feature_key` (`feature_key`),
  ADD KEY `idx_feature_key` (`feature_key`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `group_admin_assignments`
--
ALTER TABLE `group_admin_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_assignment` (`user_id`,`community_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_community` (`community_id`);

--
-- Indexes for table `lottery_books`
--
ALTER TABLE `lottery_books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `unique_book` (`event_id`,`book_number`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_status` (`book_status`);

--
-- Indexes for table `lottery_events`
--
ALTER TABLE `lottery_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_community` (`community_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_event_status` (`community_id`,`status`);

--
-- Indexes for table `lottery_winners`
--
ALTER TABLE `lottery_winners`
  ADD PRIMARY KEY (`winner_id`),
  ADD UNIQUE KEY `unique_event_ticket` (`event_id`,`ticket_number`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_prize_position` (`prize_position`),
  ADD KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `idx_winners_event_position` (`event_id`,`prize_position`);

--
-- Indexes for table `payment_collections`
--
ALTER TABLE `payment_collections`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_distribution` (`distribution_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_collected_by` (`collected_by`),
  ADD KEY `idx_payment_date_range` (`payment_date`,`collected_by`);

--
-- Indexes for table `payment_edit_history`
--
ALTER TABLE `payment_edit_history`
  ADD PRIMARY KEY (`edit_id`),
  ADD KEY `edited_by` (`edited_by`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_edited_at` (`edited_at`);

--
-- Indexes for table `system_health_metrics`
--
ALTER TABLE `system_health_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `idx_metric_name` (`metric_name`),
  ADD KEY `idx_recorded_at` (`recorded_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `mobile_number` (`mobile_number`),
  ADD KEY `idx_mobile` (`mobile_number`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `alert_notifications`
--
ALTER TABLE `alert_notifications`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `book_distribution`
--
ALTER TABLE `book_distribution`
  MODIFY `distribution_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `commission_earned`
--
ALTER TABLE `commission_earned`
  MODIFY `commission_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `commission_settings`
--
ALTER TABLE `commission_settings`
  MODIFY `setting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `community_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `community_features`
--
ALTER TABLE `community_features`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `community_settings`
--
ALTER TABLE `community_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `database_connection_logs`
--
ALTER TABLE `database_connection_logs`
  MODIFY `connection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `deletion_requests`
--
ALTER TABLE `deletion_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `distribution_levels`
--
ALTER TABLE `distribution_levels`
  MODIFY `level_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `distribution_level_values`
--
ALTER TABLE `distribution_level_values`
  MODIFY `value_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `feature_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `group_admin_assignments`
--
ALTER TABLE `group_admin_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lottery_books`
--
ALTER TABLE `lottery_books`
  MODIFY `book_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=811;

--
-- AUTO_INCREMENT for table `lottery_events`
--
ALTER TABLE `lottery_events`
  MODIFY `event_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lottery_winners`
--
ALTER TABLE `lottery_winners`
  MODIFY `winner_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `payment_collections`
--
ALTER TABLE `payment_collections`
  MODIFY `payment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `payment_edit_history`
--
ALTER TABLE `payment_edit_history`
  MODIFY `edit_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_health_metrics`
--
ALTER TABLE `system_health_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Structure for view `view_commission_summary`
--
DROP TABLE IF EXISTS `view_commission_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u717011923_gettoknow_db`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_commission_summary`  AS SELECT `ce`.`event_id` AS `event_id`, `le`.`event_name` AS `event_name`, `ce`.`level_1_value` AS `level_1_value`, `ce`.`commission_type` AS `commission_type`, count(0) AS `payment_count`, sum(`ce`.`payment_amount`) AS `total_payment_amount`, sum(`ce`.`commission_amount`) AS `total_commission_earned`, avg(`ce`.`commission_percent`) AS `avg_commission_percent` FROM (`commission_earned` `ce` join `lottery_events` `le` on(`ce`.`event_id` = `le`.`event_id`)) GROUP BY `ce`.`event_id`, `ce`.`level_1_value`, `ce`.`commission_type` ;

-- --------------------------------------------------------

--
-- Structure for view `view_lottery_event_summary`
--
DROP TABLE IF EXISTS `view_lottery_event_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u717011923_gettoknow_db`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_lottery_event_summary`  AS SELECT `le`.`event_id` AS `event_id`, `le`.`event_name` AS `event_name`, `c`.`community_name` AS `community_name`, `le`.`total_books` AS `total_books`, `le`.`total_tickets` AS `total_tickets`, `le`.`total_predicted_amount` AS `total_predicted_amount`, count(distinct `bd`.`distribution_id`) AS `books_distributed`, coalesce(sum(`pc`.`amount_paid`),0) AS `total_collected`, `le`.`status` AS `status`, `le`.`created_at` AS `created_at` FROM ((((`lottery_events` `le` left join `communities` `c` on(`le`.`community_id` = `c`.`community_id`)) left join `lottery_books` `lb` on(`le`.`event_id` = `lb`.`event_id`)) left join `book_distribution` `bd` on(`lb`.`book_id` = `bd`.`book_id`)) left join `payment_collections` `pc` on(`bd`.`distribution_id` = `pc`.`distribution_id`)) GROUP BY `le`.`event_id` ;

-- --------------------------------------------------------

--
-- Structure for view `view_winners_summary`
--
DROP TABLE IF EXISTS `view_winners_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u717011923_gettoknow_db`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_winners_summary`  AS SELECT `lw`.`event_id` AS `event_id`, `le`.`event_name` AS `event_name`, `lw`.`prize_position` AS `prize_position`, `lw`.`ticket_number` AS `ticket_number`, `lw`.`book_number` AS `book_number`, `lw`.`distribution_path` AS `distribution_path`, `lw`.`winner_name` AS `winner_name`, `lw`.`winner_contact` AS `winner_contact`, `lw`.`added_at` AS `added_at` FROM (`lottery_winners` `lw` join `lottery_events` `le` on(`lw`.`event_id` = `le`.`event_id`)) ORDER BY CASE `lw`.`prize_position` WHEN '1st' THEN 1 WHEN '2nd' THEN 2 WHEN '3rd' THEN 3 WHEN 'consolation' THEN 4 END ASC, `lw`.`added_at` ASC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `book_distribution`
--
ALTER TABLE `book_distribution`
  ADD CONSTRAINT `book_distribution_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `lottery_books` (`book_id`),
  ADD CONSTRAINT `book_distribution_ibfk_2` FOREIGN KEY (`level_1_value_id`) REFERENCES `distribution_level_values` (`value_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `book_distribution_ibfk_3` FOREIGN KEY (`level_2_value_id`) REFERENCES `distribution_level_values` (`value_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `book_distribution_ibfk_4` FOREIGN KEY (`level_3_value_id`) REFERENCES `distribution_level_values` (`value_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `book_distribution_ibfk_5` FOREIGN KEY (`distributed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `commission_earned`
--
ALTER TABLE `commission_earned`
  ADD CONSTRAINT `commission_earned_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `lottery_events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commission_earned_ibfk_2` FOREIGN KEY (`payment_collection_id`) REFERENCES `payment_collections` (`payment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commission_earned_ibfk_3` FOREIGN KEY (`book_id`) REFERENCES `lottery_books` (`book_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_commission_distribution` FOREIGN KEY (`distribution_id`) REFERENCES `book_distribution` (`distribution_id`) ON DELETE CASCADE;

--
-- Constraints for table `commission_settings`
--
ALTER TABLE `commission_settings`
  ADD CONSTRAINT `commission_settings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `lottery_events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `community_features`
--
ALTER TABLE `community_features`
  ADD CONSTRAINT `community_features_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_features_ibfk_2` FOREIGN KEY (`feature_id`) REFERENCES `features` (`feature_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_features_ibfk_3` FOREIGN KEY (`enabled_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `community_features_ibfk_4` FOREIGN KEY (`disabled_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `community_settings`
--
ALTER TABLE `community_settings`
  ADD CONSTRAINT `community_settings_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_settings_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `distribution_levels`
--
ALTER TABLE `distribution_levels`
  ADD CONSTRAINT `distribution_levels_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `lottery_events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `distribution_level_values`
--
ALTER TABLE `distribution_level_values`
  ADD CONSTRAINT `distribution_level_values_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `distribution_levels` (`level_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `distribution_level_values_ibfk_2` FOREIGN KEY (`parent_value_id`) REFERENCES `distribution_level_values` (`value_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_admin_assignments`
--
ALTER TABLE `group_admin_assignments`
  ADD CONSTRAINT `group_admin_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_admin_assignments_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_admin_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `lottery_books`
--
ALTER TABLE `lottery_books`
  ADD CONSTRAINT `lottery_books_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `lottery_events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `lottery_events`
--
ALTER TABLE `lottery_events`
  ADD CONSTRAINT `lottery_events_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`),
  ADD CONSTRAINT `lottery_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `lottery_winners`
--
ALTER TABLE `lottery_winners`
  ADD CONSTRAINT `lottery_winners_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `lottery_events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lottery_winners_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment_collections`
--
ALTER TABLE `payment_collections`
  ADD CONSTRAINT `payment_collections_ibfk_1` FOREIGN KEY (`distribution_id`) REFERENCES `book_distribution` (`distribution_id`),
  ADD CONSTRAINT `payment_collections_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment_edit_history`
--
ALTER TABLE `payment_edit_history`
  ADD CONSTRAINT `payment_edit_history_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payment_collections` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_edit_history_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
