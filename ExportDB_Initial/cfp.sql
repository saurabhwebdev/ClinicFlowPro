-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2024 at 11:31 AM
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
-- Database: `cfp`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 30,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `patient_id`, `title`, `date`, `time`, `duration`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'Test Appointment', '2025-01-03', '11:00:00', 30, 'completed', '', '2024-12-26 17:16:28', '2024-12-26 21:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_settings`
--

CREATE TABLE `clinic_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `clinic_name` varchar(100) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `working_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_days`)),
  `working_hours_start` time DEFAULT NULL,
  `working_hours_end` time DEFAULT NULL,
  `default_appointment_duration` int(11) DEFAULT 30,
  `doctor_name` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `awards_recognition` text DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `tax_registration_number` varchar(50) DEFAULT NULL,
  `holidays` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`holidays`)),
  `time_zone` varchar(100) DEFAULT NULL,
  `appointment_reminder_before` int(11) DEFAULT 24,
  `cancellation_policy` text DEFAULT NULL,
  `social_media_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media_links`)),
  `payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_methods`)),
  `invoice_prefix` varchar(10) DEFAULT NULL,
  `invoice_footer_text` text DEFAULT NULL,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `email_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_settings`
--

INSERT INTO `clinic_settings` (`id`, `user_id`, `clinic_name`, `logo_path`, `email`, `phone`, `website`, `address`, `country`, `currency`, `working_days`, `working_hours_start`, `working_hours_end`, `default_appointment_duration`, `doctor_name`, `license_number`, `degree`, `specialization`, `awards_recognition`, `signature_path`, `gst_number`, `tax_registration_number`, `holidays`, `time_zone`, `appointment_reminder_before`, `cancellation_policy`, `social_media_links`, `payment_methods`, `invoice_prefix`, `invoice_footer_text`, `sms_notifications`, `email_notifications`, `created_at`, `updated_at`) VALUES
(1, 4, 'Curadent', 'uploads/clinic/logo_1735229828_DFP (1).png', 'hellocuradent@gmail.com', '8087098711', 'https://www.curadent.in', '301, next to Karnataka Bank, Kasturi Nagar, Bengaluru, Karnataka 560043', 'India', 'INR', '[1,2,3,4,5,6]', '11:00:00', '19:00:00', 30, 'Kritilaxmi Jha', 'LISC12345', 'MDS', 'Prosthodontics', 'Sample Award 1', 'uploads/clinic/signature_1735229828_sign.jpg', 'GSTIN123456789', 'TAXIN123456', '{\"2024-12-31\":\"\",\"2025-01-01\":\"\",\"2025-01-14\":\"\"}', 'Asia/Kolkata', 24, 'let us know 24 hour before cancellation.', '{\"facebook\":\"https:\\/\\/facebook.com\",\"instagram\":\"https:\\/\\/facebook.com\",\"twitter\":\"https:\\/\\/facebook.com\"}', '[\"cash\",\"card\",\"upi\",\"bank_transfer\",\"insurance\"]', 'INV-', 'Thanks for choosing us.', 0, 1, '2024-12-26 16:17:08', '2024-12-26 22:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `min_quantity` int(11) DEFAULT 0,
  `max_quantity` int(11) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `last_restock_date` date DEFAULT NULL,
  `status` enum('active','low_stock','out_of_stock','discontinued') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `user_id`, `name`, `sku`, `category`, `description`, `quantity`, `unit`, `min_quantity`, `max_quantity`, `unit_cost`, `selling_price`, `supplier`, `location`, `expiry_date`, `last_restock_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 5, 'Dental Floss', 'CDI-001', 'Dental Supplies', 'This is a sample description.', 50, 'Pieces', 10, 50, 120.00, 150.00, 'Manipal Dentist Instruments', 'Shelf A-1', NULL, NULL, 'active', 'This is a sample note and instruction.', '2024-12-27 09:53:12', '2024-12-27 09:53:12');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','sale','adjustment','return') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `status` enum('draft','sent','paid','cancelled') DEFAULT 'draft',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('percentage','fixed') DEFAULT 'fixed',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `user_id`, `patient_id`, `appointment_id`, `invoice_number`, `status`, `subtotal`, `tax_rate`, `tax_amount`, `discount_type`, `discount_value`, `discount_amount`, `total_amount`, `payment_method`, `payment_date`, `due_date`, `notes`, `items`, `created_at`, `updated_at`) VALUES
(2, 4, 1, NULL, 'INV-202412-0001', 'sent', 8000.00, 12.00, 960.00, 'percentage', 1.00, 80.00, 8880.00, NULL, NULL, '2025-01-25', 'Sample Invoice', '[{\"description\":\"Item 1\",\"quantity\":\"4\",\"unit_price\":\"2000\"}]', '2024-12-26 21:42:41', '2024-12-27 14:17:24'),
(3, 4, 1, NULL, 'INV-202412-0002', 'paid', 1200.00, 12.00, 144.00, 'percentage', 2.00, 24.00, 1320.00, NULL, NULL, '2025-01-25', '', '[{\"description\":\"dsad\",\"quantity\":\"1\",\"unit_price\":\"1200\"}]', '2024-12-26 21:56:59', '2024-12-27 14:16:36'),
(4, 4, 1, NULL, 'INV-202412-0003', 'paid', 1200.00, 12.00, 144.00, 'percentage', 2.00, 24.00, 1320.00, NULL, NULL, '2025-01-01', '', '[{\"description\":\"item 3\",\"quantity\":\"1\",\"unit_price\":\"1200\"}]', '2024-12-26 21:58:01', '2024-12-26 22:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('percentage','fixed') DEFAULT 'fixed',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `address` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `address`, `medical_history`, `notes`, `created_at`, `updated_at`, `user_id`) VALUES
(1, 'Saurabh', 'Thakur', 'worlddj0@gmail.com', '8087098711', '1992-07-07', 'male', 'West Bengal', 'No History', 'No Notes', '2024-12-26 15:12:02', '2024-12-26 15:12:02', 4);

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `medications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`medications`)),
  `instructions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `user_id`, `patient_id`, `appointment_id`, `diagnosis`, `medications`, `instructions`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 1, NULL, 'This is a test diagnosis', '[{\"name\":\"Dolo\",\"dosage\":\"650\",\"frequency\":\"3 times a day\",\"duration\":\"7 days\"}]', 'This is a test instruction.', 'This is a test note.', '2024-12-26 19:51:17', '2024-12-26 19:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `subscription_status` enum('trial','active','expired','cancelled') DEFAULT 'trial',
  `trial_ends_at` datetime DEFAULT NULL,
  `subscription_ends_at` datetime DEFAULT NULL,
  `subscription_plan_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `is_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `subscription_status`, `trial_ends_at`, `subscription_ends_at`, `subscription_plan_id`, `created_at`, `updated_at`) VALUES
(4, 'worlddj0@gmail.com', '$2y$10$wW6KwH4p84YIgErYRqU.p.chXGAkqTLVJlzPxBdZ3LexrkjiP7ti2', 'Saurabh', 'Thakur', 1, NULL, NULL, NULL, 'active', '2025-01-09 19:48:49', '2025-12-26 15:31:23', 'I-6F55RYVYMYNW', '2024-12-26 14:18:49', '2024-12-26 14:31:23'),
(5, 'iamsaurabhthakur29@gmail.com', '$2y$10$Fpj./3DKSRjRlwaqZeTTveYOWeqwkMSwrkn9sAnJ8RpziR82IA1.a', 'Test', 'User', 1, NULL, NULL, NULL, 'trial', '2025-01-10 04:24:19', NULL, NULL, '2024-12-26 22:54:19', '2024-12-26 22:54:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `clinic_settings`
--
ALTER TABLE `clinic_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

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
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clinic_settings`
--
ALTER TABLE `clinic_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);

--
-- Constraints for table `clinic_settings`
--
ALTER TABLE `clinic_settings`
  ADD CONSTRAINT `clinic_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
