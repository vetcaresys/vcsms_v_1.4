-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 09, 2025 at 04:25 AM
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
-- Database: `vcs_dbb`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `residence` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `appointment_start` time DEFAULT NULL,
  `appointment_end` time DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled') DEFAULT 'pending',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `clinic_id`, `pet_id`, `owner_id`, `service_id`, `doctor_id`, `residence`, `phone`, `message`, `updated_by`, `appointment_date`, `appointment_start`, `appointment_end`, `status`, `updated_at`) VALUES
(1, 1, 1, NULL, 1, NULL, 'makawa aloran', '09815166616', 'please ko kay sige ug pangiti', NULL, '2025-10-30 00:00:00', NULL, NULL, 'cancelled', '2025-10-29 11:39:10'),
(2, 1, 1, NULL, 1, NULL, 'makawa aloran', '09815166616', 'ga tae tae na', NULL, '2025-10-30 00:00:00', NULL, NULL, 'cancelled', '2025-10-29 11:41:06'),
(3, 1, 1, NULL, 1, NULL, 'makawa aloran', '09815166616', 'ga tae tae na', NULL, '2025-10-30 00:00:00', NULL, NULL, 'cancelled', '2025-10-29 11:41:04'),
(4, 1, 1, NULL, 1, NULL, 'makawa aloran', '09815166616', 'namatay na', NULL, '2025-10-30 00:00:00', NULL, NULL, 'pending', '2025-10-29 11:45:41');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `clinic_id` int(11) NOT NULL,
  `main_clinic_id` int(11) DEFAULT NULL,
  `parent_clinic_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `clinic_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `business_permit` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `resubmit_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`clinic_id`, `main_clinic_id`, `parent_clinic_id`, `user_id`, `clinic_name`, `address`, `contact_info`, `latitude`, `longitude`, `logo`, `business_permit`, `status`, `resubmit_token`) VALUES
(1, NULL, NULL, 3, 'Mino', 'punta panaon misamis occidental', '09423423423', 8.3548611, 123.8480186, '1761708134_556484593_802733699379088_2545144305936702891_n.jpg', 'uploads/permits/69018666b0897_566456297_4071436243170233_3811006004424076965_n.jpg', 'approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `clinic_schedules`
--

CREATE TABLE `clinic_schedules` (
  `schedule_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_schedules`
--

INSERT INTO `clinic_schedules` (`schedule_id`, `clinic_id`, `open_time`, `close_time`, `status`, `day_of_week`) VALUES
(1, 1, '10:00:00', '17:00:00', 'open', 'Monday');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_services`
--

CREATE TABLE `clinic_services` (
  `service_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_services`
--

INSERT INTO `clinic_services` (`service_id`, `clinic_id`, `service_name`, `duration`, `price`) VALUES
(1, 1, 'Vaccination', '30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_assignments`
--

CREATE TABLE `doctor_assignments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_requests`
--

CREATE TABLE `doctor_requests` (
  `request_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `requested_by_clinic` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `response_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_visits`
--

CREATE TABLE `doctor_visits` (
  `visit_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL,
  `status` enum('unread','read','resolved') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('available','low_stock','out_of_stock') DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_activity_log`
--

CREATE TABLE `inventory_activity_log` (
  `log_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `action_type` enum('add','restock','edit','delete') NOT NULL,
  `quantity_added` int(11) DEFAULT 0,
  `previous_quantity` int(11) DEFAULT 0,
  `new_quantity` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `species` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'alive',
  `date_of_death` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `owner_id`, `pet_name`, `species`, `photo`, `breed`, `birth_date`, `description`, `status`, `date_of_death`) VALUES
(1, 2, 'chiwawa', NULL, '1761708800_bf4014293e92954aa106cb10e5c415f8.png', 'buldog', '2025-10-28', 'gikalibanga', 'alive', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pet_records`
--

CREATE TABLE `pet_records` (
  `record_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `template_id` int(11) NOT NULL,
  `record_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`record_data`)),
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `date_recorded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `record_fields`
--

CREATE TABLE `record_fields` (
  `field_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `record_inventory_usage`
--

CREATE TABLE `record_inventory_usage` (
  `usage_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `date_used` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `record_templates`
--

CREATE TABLE `record_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `record_templates`
--

INSERT INTO `record_templates` (`template_id`, `template_name`, `description`, `fields`, `created_at`) VALUES
(1, 'Consultation', 'General veterinary consultation form', '{\r\n    \"fields\": [\r\n      {\"label\":\"Date of Consultation\",\"type\":\"date\"},\r\n      {\"label\":\"Pet Weight (kg)\",\"type\":\"number\"},\r\n      {\"label\":\"Temperature (°C)\",\"type\":\"number\"},\r\n      {\"label\":\"Symptoms / Observations\",\"type\":\"textarea\"},\r\n      {\"label\":\"Diagnosis\",\"type\":\"textarea\"},\r\n      {\"label\":\"Prescribed Treatment / Medication\",\"type\":\"textarea\"},\r\n      {\"label\":\"Follow-up Instructions\",\"type\":\"textarea\"},\r\n      {\"label\":\"Next Visit Date\",\"type\":\"date\"},\r\n      {\"label\":\"Attending Doctor\",\"type\":\"text\"}\r\n    ]\r\n  }', '2025-10-06 12:10:54'),
(2, 'Vaccination', 'Record of vaccination details', '{\r\n    \"fields\": [\r\n      {\"label\":\"Vaccine Name\",\"type\":\"text\"},\r\n      {\"label\":\"Batch Number\",\"type\":\"text\"},\r\n      {\"label\":\"Manufacturer\",\"type\":\"text\"},\r\n      {\"label\":\"Date Given\",\"type\":\"date\"},\r\n      {\"label\":\"Next Due Date\",\"type\":\"date\"},\r\n      {\"label\":\"Veterinarian\",\"type\":\"text\"},\r\n      {\"label\":\"Remarks\",\"type\":\"textarea\"}\r\n    ]\r\n  }', '2025-10-06 12:10:54'),
(3, 'Laboratory', 'Laboratory test results record', '{\r\n    \"fields\": [\r\n      {\"label\":\"Test Type\",\"type\":\"text\"},\r\n      {\"label\":\"Sample Collected Date\",\"type\":\"date\"},\r\n      {\"label\":\"Result Summary\",\"type\":\"textarea\"},\r\n      {\"label\":\"Detailed Findings\",\"type\":\"textarea\"},\r\n      {\"label\":\"Reference Range / Normal Values\",\"type\":\"textarea\"},\r\n      {\"label\":\"Conducted By (Technician/Doctor)\",\"type\":\"text\"},\r\n      {\"label\":\"Remarks\",\"type\":\"textarea\"}\r\n    ]\r\n  }', '2025-10-06 12:10:54'),
(4, 'Surgery', 'Record for pet surgical operations', '{\r\n    \"fields\": [\r\n      {\"label\":\"Surgery Type\",\"type\":\"text\"},\r\n      {\"label\":\"Date of Surgery\",\"type\":\"date\"},\r\n      {\"label\":\"Surgeon Name\",\"type\":\"text\"},\r\n      {\"label\":\"Anesthesia Used\",\"type\":\"text\"},\r\n      {\"label\":\"Duration of Surgery (minutes)\",\"type\":\"number\"},\r\n      {\"label\":\"Pre-operative Condition\",\"type\":\"textarea\"},\r\n      {\"label\":\"Surgical Procedure Notes\",\"type\":\"textarea\"},\r\n      {\"label\":\"Post-operative Care Instructions\",\"type\":\"textarea\"},\r\n      {\"label\":\"Medications Prescribed\",\"type\":\"textarea\"},\r\n      {\"label\":\"Follow-up Date\",\"type\":\"date\"}\r\n    ]\r\n  }', '2025-10-06 12:10:54'),
(5, 'Hospitalization', 'Record for pets admitted in the clinic', '{\r\n    \"fields\": [\r\n      {\"label\":\"Admission Date\",\"type\":\"date\"},\r\n      {\"label\":\"Discharge Date\",\"type\":\"date\"},\r\n      {\"label\":\"Diagnosis\",\"type\":\"textarea\"},\r\n      {\"label\":\"Treatment Given\",\"type\":\"textarea\"},\r\n      {\"label\":\"Medications Administered\",\"type\":\"textarea\"},\r\n      {\"label\":\"Feeding Schedule / Diet\",\"type\":\"textarea\"},\r\n      {\"label\":\"Temperature Monitoring\",\"type\":\"textarea\"},\r\n      {\"label\":\"Attending Doctor\",\"type\":\"text\"},\r\n      {\"label\":\"Remarks / Notes\",\"type\":\"textarea\"}\r\n    ]\r\n  }', '2025-10-06 12:10:54');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` enum('staff','doctor') DEFAULT 'staff',
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `clinic_id`, `name`, `role`, `contact_number`, `email`, `password`, `profile_picture`, `is_verified`, `verification_token`) VALUES
(1, 1, 'zhiena', 'staff', '09324232342', 'embodomarkrey@gmail.com', '$2y$10$9oLqYrayVqCSUeW8lm/chuOQBDhnZ3XPDgLai5xv4D07NwEeTYU9a', '1761707940_559191893_31729607833349223_6750564299817428804_n.mp4', 1, NULL),
(2, 1, 'rica telesio', 'doctor', '09324232346', 'jellygrace91@gmail.com', '$2y$10$5xNa0EEsfPDthcUl9Iwg7esGQyvhS72QuWC4JwdtEtx0rTvrfQtcW', '1761708032_564462415_32390814380509761_8916522858338528827_n.mp4', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff','clinic_owner','pet_owner') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `contact_number`, `address`, `profile_picture`, `reset_token_hash`, `reset_token_expires_at`, `is_verified`, `verification_token`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$3XFk2r/.PssDC.D34izoSue5d.L8G0qjy.7AGMXAaLt3cU0W3nAM.', 'admin', '09887778888', 'Makawa, Aloran, Misamis Occidental', NULL, NULL, NULL, 0, 'd27724e7d45f994437de1c11e7657ff3', '2025-09-25 23:12:36'),
(2, 'ray poras', 'raymart.poras@gmail.com', '$2y$10$QMpHx.SAuqH0zRFKS6tMrOHN3yu27bO1PsilI0w3ZXLCbPsxoJDFe', 'pet_owner', '09123113123', 'punta panaon miss occ', NULL, NULL, NULL, 1, NULL, '2025-10-29 03:07:03'),
(3, 'loe ates', 'eraoflorenciaforsale@gmail.com', '$2y$10$jd1s6DSfTHCksp5oCAf/2OMw7V15SDsoMQvMYbBwx4zuXxnaF.Rqy', 'clinic_owner', '09324232342', 'punta panaon misamis occidental', NULL, NULL, NULL, 1, NULL, '2025-10-29 03:13:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `fk_appointments_doctor` (`doctor_id`),
  ADD KEY `fk_appointments_owner` (`owner_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_parent_clinic` (`parent_clinic_id`);

--
-- Indexes for table `clinic_schedules`
--
ALTER TABLE `clinic_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `clinic_services`
--
ALTER TABLE `clinic_services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `doctor_assignments`
--
ALTER TABLE `doctor_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `doctor_requests`
--
ALTER TABLE `doctor_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `requested_by_clinic` (`requested_by_clinic`);

--
-- Indexes for table `doctor_visits`
--
ALTER TABLE `doctor_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `inventory_activity_log`
--
ALTER TABLE `inventory_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `pet_records`
--
ALTER TABLE `pet_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `record_fields`
--
ALTER TABLE `record_fields`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `record_inventory_usage`
--
ALTER TABLE `record_inventory_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `record_templates`
--
ALTER TABLE `record_templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clinic_schedules`
--
ALTER TABLE `clinic_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clinic_services`
--
ALTER TABLE `clinic_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_assignments`
--
ALTER TABLE `doctor_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_requests`
--
ALTER TABLE `doctor_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_visits`
--
ALTER TABLE `doctor_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_activity_log`
--
ALTER TABLE `inventory_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pet_records`
--
ALTER TABLE `pet_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `record_fields`
--
ALTER TABLE `record_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `record_inventory_usage`
--
ALTER TABLE `record_inventory_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `record_templates`
--
ALTER TABLE `record_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `clinic_services` (`service_id`),
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointments_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `clinics`
--
ALTER TABLE `clinics`
  ADD CONSTRAINT `clinics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_parent_clinic` FOREIGN KEY (`parent_clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE SET NULL;

--
-- Constraints for table `clinic_schedules`
--
ALTER TABLE `clinic_schedules`
  ADD CONSTRAINT `clinic_schedules_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE CASCADE;

--
-- Constraints for table `clinic_services`
--
ALTER TABLE `clinic_services`
  ADD CONSTRAINT `clinic_services_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `doctor_assignments`
--
ALTER TABLE `doctor_assignments`
  ADD CONSTRAINT `doctor_assignments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `doctor_assignments_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `doctor_requests`
--
ALTER TABLE `doctor_requests`
  ADD CONSTRAINT `doctor_requests_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `doctor_requests_ibfk_2` FOREIGN KEY (`requested_by_clinic`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `doctor_visits`
--
ALTER TABLE `doctor_visits`
  ADD CONSTRAINT `doctor_visits_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_visits_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `inventory_activity_log`
--
ALTER TABLE `inventory_activity_log`
  ADD CONSTRAINT `inventory_activity_log_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`),
  ADD CONSTRAINT `inventory_activity_log_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `pet_records`
--
ALTER TABLE `pet_records`
  ADD CONSTRAINT `pet_records_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `record_templates` (`template_id`);

--
-- Constraints for table `record_fields`
--
ALTER TABLE `record_fields`
  ADD CONSTRAINT `record_fields_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `pet_records` (`record_id`) ON DELETE CASCADE;

--
-- Constraints for table `record_inventory_usage`
--
ALTER TABLE `record_inventory_usage`
  ADD CONSTRAINT `record_inventory_usage_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `pet_records` (`record_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `record_inventory_usage_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
