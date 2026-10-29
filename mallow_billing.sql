-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 29, 2026 at 02:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mallow_billing`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-9986a4a8117ba25bb905888fd04e02ef', 'i:2;', 1793277853),
('laravel-cache-9986a4a8117ba25bb905888fd04e02ef:timer', 'i:1793277853;', 1793277853);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `merchant_id`, `name`, `email`, `created_at`, `updated_at`) VALUES
(1, 1, 'Arun Kumar', 'arun@example.com', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(2, 1, 'Priya Devi', 'priya@example.com', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(3, 1, 'Karthik Raj', 'karthik@example.com', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(4, 1, 'Divya S', 'divya@example.com', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(5, 1, 'Vijay Kumar', 'vijay@example.com', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(6, 1, 'Proration Test Customer', 'proration-test@example.com', '2026-10-28 03:23:30', '2026-10-28 03:23:30'),
(7, 1, 'Invoice Items Test', 'invoice-items-test@example.com', '2026-10-28 03:43:10', '2026-10-28 03:43:10'),
(8, 1, 'prem', 'prem@gmail.com', '2026-10-28 19:57:51', '2026-10-28 19:57:51');

-- --------------------------------------------------------

--
-- Table structure for table `daily_usage`
--

CREATE TABLE `daily_usage` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `segment_id` bigint(20) UNSIGNED NOT NULL,
  `usage_date` date NOT NULL,
  `total_units` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_usage`
--

INSERT INTO `daily_usage` (`id`, `subscription_id`, `segment_id`, `usage_date`, `total_units`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-10-28', 100, NULL, '2026-10-28 02:54:53');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` datetime NOT NULL,
  `period_end` datetime NOT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL,
  `status` enum('draft','issued','paid','void') NOT NULL DEFAULT 'draft',
  `issued_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `merchant_id`, `subscription_id`, `period_start`, `period_end`, `subtotal`, `total`, `currency`, `status`, `issued_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 2599.00, 2599.00, 'INR', 'draft', NULL, '2026-10-28 03:17:00', '2026-10-28 03:17:00'),
(2, 1, 6, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 1515.13, 1515.13, 'INR', 'draft', NULL, '2026-10-28 03:29:37', '2026-10-28 03:29:37'),
(3, 1, 7, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 999.00, 999.00, 'INR', 'draft', NULL, '2026-10-28 03:44:18', '2026-10-28 03:44:18');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `segment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `item_type` enum('base_fee','overage') NOT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 1.0000,
  `unit_price` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `segment_id`, `description`, `item_type`, `quantity`, `unit_price`, `amount`, `created_at`, `updated_at`) VALUES
(1, 3, 8, 'Plan #1 base fee (prorated)', 'base_fee', 1.0000, 999.0000, 999.00, '2026-10-28 03:44:18', '2026-10-28 03:44:18');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `merchants`
--

CREATE TABLE `merchants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `api_key_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merchants`
--

INSERT INTO `merchants` (`id`, `name`, `status`, `created_at`, `updated_at`, `api_key_hash`) VALUES
(1, 'Acme Corp', 'active', '2026-10-28 06:12:04', '2026-10-29 07:04:11', '$2y$12$p75Ih.LRUwfEirXIzyeHLudnSm1rhA/cTGJ6c46F1FTj58NzqoX6a'),
(2, 'Smarzi Tech Solutions', 'active', '2026-10-28 09:59:11', '2026-10-28 19:34:30', '$2y$12$w34fIlSdl5KPUzi9SLPUMe/uCI6Aehe8cSvZ6HoO1GL2q30GIuDYi'),
(5, 'Acme Corp1', 'active', '2026-10-28 19:23:23', '2026-10-28 19:23:23', '$2y$12$I8wjB9WZXYQUudUqPpxVgeT11yC3APGb1hvTDvjhlNJ7CxR03Kn3y');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_price` decimal(12,2) NOT NULL,
  `billing_cycle` enum('monthly','yearly') NOT NULL,
  `included_units` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `overage_rate` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `billing_interval_count` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `billing_interval_unit` varchar(10) NOT NULL DEFAULT 'month'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `merchant_id`, `name`, `base_price`, `billing_cycle`, `included_units`, `overage_rate`, `currency`, `created_at`, `updated_at`, `is_active`, `billing_interval_count`, `billing_interval_unit`) VALUES
(1, 1, 'Starter', 999.00, 'monthly', 1000, 1.0000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 1, 'month'),
(2, 1, 'Professional', 1999.00, 'monthly', 5000, 0.5000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 1, 'month'),
(3, 1, 'Medium', 1500.00, 'monthly', 7000, 0.5000, 'INR', '2026-10-28 19:59:25', '2026-10-28 19:59:50', 1, 1, 'month');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('02kWP1uyS24e7k62MJgeLOLewDjwMsCAptxG0OCs', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ0lCdldMY3FINXRRck1DYk1tMFcwVFo2Mllnck1zZWhvWUFOV1FhWCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8yL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fX0=', 1793235983),
('2uK9QWNdN752v4l7ZzeBXo8ju8IwACr8BCJzQSwI', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT3RzQk5tQXNyeEtta0ptSjNibUo5Zk9tZ1RKUTE2QkRENUpCdjBYdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czoxODoibWVyY2hhbnQuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czoyMToiZGFzaGJvYXJkX21lcmNoYW50X2lkIjtpOjE7fQ==', 1793265294),
('B3QlmlOJAz03M09071kRU1plIBI2utVEZJdvbykY', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWW15UkdLQm52clNISWQ0ZUhPZHFzNU9MY2RXVnhSdjY5Nm1vZW1ISiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czoxODoibWVyY2hhbnQuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czoyMToiZGFzaGJvYXJkX21lcmNoYW50X2lkIjtpOjE7fQ==', 1793265117),
('b9EWw4nUgIdnHPcerOvdyxm6Fex6aLTPaSfIg5Mp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicnZZMGtQRXh0SGNWaVpVRXlwQUtyT2JaR1dpQm9nZG9MR2FXSUxkMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czoxODoibWVyY2hhbnQuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czoyMToiZGFzaGJvYXJkX21lcmNoYW50X2lkIjtpOjE7fQ==', 1793277936),
('Hrrd7pmcmX0FdaaEn7Yz39CQvCHBqvM8hYRP6jYR', NULL, '::1', 'curl/8.21.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNm05SWI0WjR1OFNtd3ZnUmdHbDU4cUFmeGVYVzBVWnY1R1FobzQ2RCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793277047),
('jcZ6AbrbebBRQRCHWgyvP0SMaqT2xONX4YIu1ujc', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRlFpTDV6bkc4eDFiZENJeWFoWFpoRjdKYTBQZGt0d2pVR0FnVmh2cyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793235484),
('KOGRm574vuHWwoMVEgEzLKed2sNr1EOvxA2yYlk9', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTnhqRmNYRjBBRTZSV08wRm8yb1NjczQycnp3TUxPblRlcDJyOUh5cCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fX0=', 1793277088),
('o833paDXDaRzKzbGRZ4JuwD01rCfSYWogH6oNQah', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQWtBT1RaNlU1cXVicVNkRTRqR2hWVDVLV3N0cFlGb3pBUFY3QlIwYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793263715),
('rA17rEFkKSi5zj2IfMlFxxZA75BJB74TDtyC8fLr', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWjB3OTN2N3lmbHBsSEZLanZ5RExTS1RzQVRiUGpwYmhidEZGWDhjciI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fX0=', 1793277031),
('rLlPcE7TMRcHCnCxrlgZrA6kO0mm3C1yZbZq3hTG', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoianA2WkJYQjI1cFY2NnRqVnVXREZwZTJNeUlxY3ZqblNnT05rVmc4eCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8yL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MjE6ImRhc2hib2FyZF9tZXJjaGFudF9pZCI7aToxO30=', 1793235960),
('VrtdNEIyiOdq6XQLUxxfBy1IJgrmD1ji0S7ZTvGX', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiS1FhNlhaVnhXT1B4enVkUmpsVVhHQXIzSmJEZ3c4eW9wUmw2T3FSbCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czoxODoibWVyY2hhbnQuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czoyMToiZGFzaGJvYXJkX21lcmNoYW50X2lkIjtpOjE7fQ==', 1793252553),
('wNZwEjIOyvoB4UW0J5pLvm8gfYtheO8kvnXCm8KR', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoia2htSlE2WEFEVG04MzExSVB2WHNEV000Q1A4c21CWXJaQVNBSkpLQiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793265264),
('wQRg6tl4JUfi67IB1BSgUJ0kkt3FnqOHqwkbQpup', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMEpDTkE0TjRlZU55cjhpZGVxUzA4bWtQZlBsdnJlMmd2d0RGbU9mVSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793252234),
('y7CUq36S4f9Syc56Wu11uFONlIEbv1f51BIOEenv', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYnlES2RMd2toeTQ5U2RzbWY3aVZSWTdQT1BFZHhjcHRiVVJnWld5byI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6MjE6ImRhc2hib2FyZF9tZXJjaGFudF9pZCI7aToyO30=', 1793236010),
('yBTpII2G3N5pfLsXQWrWLeRNV7Ext0F4Meegrg5M', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiN2FNU0hVR01QZWxJeVJZZ2ZmTHBUZWRydXlFeThRamlPdlR2R2puSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793277794),
('yUrGv3hp0DQIxxcZlLh1KwU2hyWuHiwbsjuBCkW1', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNWZzRlIzd1RCS0ZzQjJVb0ZDOGl4NHdzVG04MDBHSk9zaGExQm93SyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czoxODoibWVyY2hhbnQuZGFzaGJvYXJkIjt9czoyMToiZGFzaGJvYXJkX21lcmNoYW50X2lkIjtpOjE7fQ==', 1793240921),
('ZdEs9RZUQ3p1KS9cQ88AYO5voHLvngepS0P8Hbeh', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOXI3VEVZc0hWTEZmWmk2MTdMRlJ0VXJGM0pFQW9YTDg3MEx4dWU0NiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9sb2NhbGhvc3QvbWFsbG93LWJpbGxpbmcvcHVibGljL21lcmNoYW50cy8xL2xvZ2luIjtzOjU6InJvdXRlIjtzOjE0OiJtZXJjaGFudC5sb2dpbiI7fXM6MjE6ImRhc2hib2FyZF9tZXJjaGFudF9pZCI7aToxO30=', 1793278289);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','cancelled','expired') NOT NULL DEFAULT 'active',
  `started_at` datetime NOT NULL,
  `current_period_start` datetime NOT NULL,
  `current_period_end` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `billing_interval_count` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `billing_interval_unit` varchar(10) NOT NULL DEFAULT 'month'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `merchant_id`, `customer_id`, `plan_id`, `status`, `started_at`, `current_period_start`, `current_period_end`, `created_at`, `updated_at`, `billing_interval_count`, `billing_interval_unit`) VALUES
(1, 1, 1, 1, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 'month'),
(2, 1, 2, 1, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 'month'),
(3, 1, 3, 2, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 'month'),
(4, 1, 4, 1, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 'month'),
(5, 1, 5, 2, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 06:12:04', '2026-10-28 06:12:04', 1, 'month'),
(6, 1, 6, 1, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 03:23:47', '2026-10-28 03:23:47', 1, 'month'),
(7, 1, 7, 1, 'active', '2026-10-01 00:00:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 03:43:41', '2026-10-28 03:43:41', 1, 'month'),
(8, 1, 8, 2, 'active', '2026-10-29 01:27:00', '2026-10-01 00:00:00', '2026-11-01 00:00:00', '2026-10-28 19:58:19', '2026-10-29 03:41:49', 1, 'month');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_segments`
--

CREATE TABLE `subscription_segments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `effective_from` datetime NOT NULL,
  `effective_to` datetime NOT NULL,
  `base_price` decimal(12,2) NOT NULL,
  `included_units` bigint(20) UNSIGNED NOT NULL,
  `overage_rate` decimal(12,4) NOT NULL,
  `currency` char(3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_segments`
--

INSERT INTO `subscription_segments` (`id`, `subscription_id`, `plan_id`, `effective_from`, `effective_to`, `base_price`, `included_units`, `overage_rate`, `currency`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 999.00, 1000, 1.0000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(2, 2, 1, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 999.00, 1000, 1.0000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(3, 3, 2, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 1999.00, 5000, 0.5000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(4, 4, 1, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 999.00, 1000, 1.0000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(5, 5, 2, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 1999.00, 5000, 0.5000, 'INR', '2026-10-28 06:12:04', '2026-10-28 06:12:04'),
(6, 6, 1, '2026-10-01 00:00:00', '2026-10-16 00:00:00', 999.00, 1000, 1.0000, 'INR', '2026-10-28 03:23:55', '2026-10-28 03:23:55'),
(7, 6, 2, '2026-10-16 00:00:00', '2026-11-01 00:00:00', 1999.00, 5000, 0.5000, 'INR', '2026-10-28 03:24:03', '2026-10-28 03:24:03'),
(8, 7, 1, '2026-10-01 00:00:00', '2026-11-01 00:00:00', 999.00, 1000, 1.0000, 'INR', '2026-10-28 03:44:00', '2026-10-28 03:44:00'),
(9, 8, 1, '2026-10-29 01:27:00', '2026-10-29 09:10:00', 999.00, 1000, 1.0000, 'INR', '2026-10-28 19:58:19', '2026-10-29 03:41:49'),
(10, 8, 2, '2026-10-29 09:10:00', '2026-11-01 00:00:00', 1999.00, 5000, 0.5000, 'INR', '2026-10-29 03:41:49', '2026-10-29 03:41:49');

-- --------------------------------------------------------

--
-- Table structure for table `usage_events`
--

CREATE TABLE `usage_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `merchant_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `segment_id` bigint(20) UNSIGNED NOT NULL,
  `idempotency_key` varchar(100) NOT NULL,
  `units` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `occurred_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usage_events`
--

INSERT INTO `usage_events` (`id`, `merchant_id`, `subscription_id`, `segment_id`, `idempotency_key`, `units`, `occurred_at`, `created_at`) VALUES
(1, 1, 1, 1, 'demo-usage-001', 2500, '2026-10-10 10:00:00', '2026-10-28 06:12:04'),
(2, 1, 2, 2, 'demo-usage-002', 1800, '2026-10-12 11:00:00', '2026-10-28 06:12:04'),
(3, 1, 3, 3, 'demo-usage-003', 4000, '2026-10-15 12:00:00', '2026-10-28 06:12:04'),
(4, 1, 4, 4, 'demo-usage-004', 700, '2026-10-18 13:00:00', '2026-10-28 06:12:04'),
(5, 1, 5, 5, 'demo-usage-005', 6000, '2026-10-20 14:00:00', '2026-10-28 06:12:04'),
(6, 1, 1, 1, 'test-usage-001', 100, '2026-10-28 10:00:00', '2026-10-28 02:36:31'),
(7, 1, 6, 7, 'proration-overage-test-001', 6000, '2026-10-20 10:00:00', '2026-10-28 03:31:36'),
(8, 1, 7, 8, 'invoice-items-overage-test-001', 1500, '2026-10-20 10:00:00', '2026-10-28 03:45:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_email` (`merchant_id`,`email`),
  ADD KEY `idx_customers_merchant` (`merchant_id`);

--
-- Indexes for table `daily_usage`
--
ALTER TABLE `daily_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_daily_usage` (`segment_id`,`usage_date`),
  ADD KEY `idx_daily_subscription_date` (`subscription_id`,`usage_date`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invoice_period` (`subscription_id`,`period_start`,`period_end`),
  ADD UNIQUE KEY `invoices_subscription_period_unique` (`subscription_id`,`period_start`,`period_end`),
  ADD KEY `idx_invoices_merchant_status` (`merchant_id`,`status`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `segment_id` (`segment_id`),
  ADD KEY `idx_invoice_items_invoice` (`invoice_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `merchants`
--
ALTER TABLE `merchants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `merchants_api_key_hash_unique` (`api_key_hash`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plans_merchant` (`merchant_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_id` (`merchant_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `idx_subscriptions_customer` (`customer_id`,`status`),
  ADD KEY `idx_subscriptions_period` (`status`,`current_period_end`);

--
-- Indexes for table `subscription_segments`
--
ALTER TABLE `subscription_segments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `idx_segments_period` (`subscription_id`,`effective_from`,`effective_to`);

--
-- Indexes for table `usage_events`
--
ALTER TABLE `usage_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usage_idempotency` (`merchant_id`,`idempotency_key`),
  ADD KEY `idx_usage_segment_time` (`segment_id`,`occurred_at`),
  ADD KEY `idx_usage_subscription_time` (`subscription_id`,`occurred_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `daily_usage`
--
ALTER TABLE `daily_usage`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `merchants`
--
ALTER TABLE `merchants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subscription_segments`
--
ALTER TABLE `subscription_segments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `usage_events`
--
ALTER TABLE `usage_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`);

--
-- Constraints for table `daily_usage`
--
ALTER TABLE `daily_usage`
  ADD CONSTRAINT `daily_usage_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
  ADD CONSTRAINT `daily_usage_ibfk_2` FOREIGN KEY (`segment_id`) REFERENCES `subscription_segments` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`segment_id`) REFERENCES `subscription_segments` (`id`);

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `plans_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`);

--
-- Constraints for table `subscription_segments`
--
ALTER TABLE `subscription_segments`
  ADD CONSTRAINT `subscription_segments_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
  ADD CONSTRAINT `subscription_segments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`);

--
-- Constraints for table `usage_events`
--
ALTER TABLE `usage_events`
  ADD CONSTRAINT `usage_events_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  ADD CONSTRAINT `usage_events_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
  ADD CONSTRAINT `usage_events_ibfk_3` FOREIGN KEY (`segment_id`) REFERENCES `subscription_segments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
